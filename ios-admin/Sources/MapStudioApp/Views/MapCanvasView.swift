import SwiftUI

public struct MapCanvasView: View {
    @ObservedObject var store: MapStudioStore
    
    // Zoom and Pan State
    @State private var zoomScale: CGFloat = 1.0
    @State private var currentZoomDelta: CGFloat = 1.0
    @State private var panOffset: CGSize = .zero
    @State private var currentPanDelta: CGSize = .zero
    
    // Active dragging vertex
    @State private var draggingVertexIndex: Int? = nil
    @State private var draggingPosition: CGPoint? = nil
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        GeometryReader { proxy in
            let viewportSize = proxy.size
            let canvasAspect: CGFloat = 600.0 / 340.0
            
            // Calculate base mountain frame maintaining 600:340 aspect
            let baseSize: CGSize = {
                if viewportSize.width / viewportSize.height > canvasAspect {
                    // Height constrained
                    let h = viewportSize.height
                    return CGSize(width: h * canvasAspect, height: h)
                } else {
                    // Width constrained
                    let w = viewportSize.width
                    return CGSize(width: w, height: w / canvasAspect)
                }
            }()
            
            let totalScale = max(1.0, min(8.0, zoomScale * currentZoomDelta))
            let totalOffset = CGSize(
                width: panOffset.width + currentPanDelta.width,
                height: panOffset.height + currentPanDelta.height
            )
            
            ZStack {
                // Background void
                StudioTheme.canvasBackground.ignoresSafeArea()
                
                // Centered Mountain Canvas
                ZStack {
                    // 1. Mountain Map Imagery
                    if let platformImg = store.imageLoader.displayedImage {
                        #if canImport(UIKit)
                        Image(uiImage: platformImg)
                            .resizable()
                            .interpolation(.high)
                            .aspectRatio(contentMode: .fit)
                            .frame(width: baseSize.width, height: baseSize.height)
                        #elseif canImport(AppKit)
                        Image(nsImage: platformImg)
                            .resizable()
                            .aspectRatio(contentMode: .fit)
                            .frame(width: baseSize.width, height: baseSize.height)
                        #endif
                    } else {
                        Rectangle()
                            .fill(StudioTheme.cardBackground)
                            .frame(width: baseSize.width, height: baseSize.height)
                            .overlay(
                                ProgressView("Loading Mountain…")
                                    .tint(.white)
                                    .foregroundColor(.white)
                            )
                    }
                    
                    // 2. Vector Map Geometry Overlay
                    Canvas { context, size in
                        renderSectors(context: context, canvasSize: size)
                        renderSegments(context: context, canvasSize: size)
                        renderEditorActiveOverlay(context: context, canvasSize: size)
                    }
                    .frame(width: baseSize.width, height: baseSize.height)
                    
                    // 3. Interactive Handles Overlay (Vertices & Midpoints)
                    interactiveHandlesOverlay(canvasSize: baseSize)
                }
                .frame(width: baseSize.width, height: baseSize.height)
                .scaleEffect(totalScale)
                .offset(totalOffset)
                .gesture(canvasMagnificationGesture)
                .simultaneousGesture(canvasPanGesture)
                .simultaneousGesture(canvasTapGesture(canvasSize: baseSize, totalScale: totalScale, totalOffset: totalOffset, viewportSize: viewportSize))
                
                // 4. Floating HUD (Zoom controls & HD badge)
                VStack {
                    HStack {
                        Spacer()
                        if store.imageLoader.isLoadingHighRes {
                            HStack(spacing: 6) {
                                ProgressView().tint(.white).scaleEffect(0.8)
                                Text("Loading HD Map…")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(.white)
                            }
                            .padding(.horizontal, 10)
                            .padding(.vertical, 6)
                            .background(Color.black.opacity(0.75))
                            .cornerRadius(20)
                            .padding(.top, 16)
                            .padding(.trailing, 16)
                        }
                    }
                    Spacer()
                    
                    // Bottom-left Zoom Controls
                    HStack(spacing: 8) {
                        Button {
                            withAnimation(.spring(response: 0.3)) {
                                zoomScale = min(8.0, zoomScale * 1.3)
                                store.imageLoader.notifyZoomScale(zoomScale, baseURL: MapStudioAPIClient.shared.baseURLString)
                            }
                        } label: {
                            Image(systemName: "plus")
                                .font(.headline)
                                .frame(width: 38, height: 38)
                                .background(StudioTheme.panelGlass)
                                .foregroundColor(.white)
                                .cornerRadius(8)
                                .overlay(RoundedRectangle(cornerRadius: 8).stroke(StudioTheme.borderSubtle, lineWidth: 1))
                        }
                        
                        Button {
                            withAnimation(.spring(response: 0.3)) {
                                zoomScale = max(1.0, zoomScale / 1.3)
                                if zoomScale == 1.0 { panOffset = .zero }
                            }
                        } label: {
                            Image(systemName: "minus")
                                .font(.headline)
                                .frame(width: 38, height: 38)
                                .background(StudioTheme.panelGlass)
                                .foregroundColor(.white)
                                .cornerRadius(8)
                                .overlay(RoundedRectangle(cornerRadius: 8).stroke(StudioTheme.borderSubtle, lineWidth: 1))
                        }
                        
                        Button {
                            withAnimation(.spring(response: 0.3)) {
                                zoomScale = 1.0
                                panOffset = .zero
                            }
                        } label: {
                            Text("Fit")
                                .font(.caption)
                                .fontWeight(.bold)
                                .frame(width: 38, height: 38)
                                .background(StudioTheme.panelGlass)
                                .foregroundColor(StudioTheme.liftColor)
                                .cornerRadius(8)
                                .overlay(RoundedRectangle(cornerRadius: 8).stroke(StudioTheme.borderSubtle, lineWidth: 1))
                        }
                        
                        Spacer()
                    }
                    .padding(.leading, 16)
                    .padding(.bottom, 20)
                }
            }
        }
    }
    
    // MARK: - Canvas Rendering (Core Graphics / SwiftUI Canvas)
    
    private func renderSectors(context: GraphicsContext, canvasSize: CGSize) {
        guard store.showSectorPolygons else { return }
        
        for sector in store.sectors {
            let pts = sector.parsedBoundary
            guard pts.count >= 3 else { continue }
            
            var path = Path()
            let firstPt = MapCoordinateSpace.mapToScreen(point: pts[0], in: canvasSize)
            path.move(to: firstPt)
            for i in 1..<pts.count {
                let p = MapCoordinateSpace.mapToScreen(point: pts[i], in: canvasSize)
                path.addLine(to: p)
            }
            path.closeSubpath()
            
            let col = Color(hex: sector.color)
            context.fill(path, with: .color(col.opacity(0.18)))
            
            let strokeStyle = StrokeStyle(lineWidth: 2, lineCap: .round, lineJoin: .round, dash: [6, 4])
            context.stroke(path, with: .color(col), style: strokeStyle)
        }
    }
    
    private func renderSegments(context: GraphicsContext, canvasSize: CGSize) {
        let editingSegId: Int? = {
            if case .editSegment(let id) = store.editorMode { return id }
            return nil
        }()
        
        for seg in store.segments {
            // If currently editing this segment, the active overlay draws it with handles
            if let activeId = editingSegId, seg.id == activeId { continue }
            
            // Filter checks
            if store.filterType == "lift" && !seg.isLift { continue }
            if store.filterType == "slope" && seg.isLift { continue }
            if store.filterType == "unassigned" && seg.sector != 0 { continue }
            
            let pts = seg.parsedPoints
            guard pts.count >= 2 else { continue }
            
            var path = Path()
            path.move(to: MapCoordinateSpace.mapToScreen(point: pts[0], in: canvasSize))
            for i in 1..<pts.count {
                path.addLine(to: MapCoordinateSpace.mapToScreen(point: pts[i], in: canvasSize))
            }
            
            let isSelected = store.selectedSegment?.id == seg.id
            let baseColor: Color
            if seg.isLift {
                baseColor = StudioTheme.liftColor
            } else {
                baseColor = StudioTheme.colorForSlopeType(seg.type, fallbackDifficulty: seg.difficulty)
            }
            
            // Halo if selected
            if isSelected {
                context.stroke(
                    path,
                    with: .color(Color.white.opacity(0.65)),
                    style: StrokeStyle(lineWidth: 12, lineCap: .round, lineJoin: .round)
                )
            }
            
            if seg.isLift {
                // Lift: Dashed amber line
                let style = StrokeStyle(lineWidth: 4.5, lineCap: .round, lineJoin: .round, dash: [8, 5])
                context.stroke(path, with: .color(baseColor), style: style)
                
                // Draw Base Station 'S' and Top Station 'E'
                let baseScreen = MapCoordinateSpace.mapToScreen(point: pts[0], in: canvasSize)
                let topScreen = MapCoordinateSpace.mapToScreen(point: pts[pts.count - 1], in: canvasSize)
                
                context.fill(Path(ellipseIn: CGRect(x: baseScreen.x - 5, y: baseScreen.y - 5, width: 10, height: 10)), with: .color(StudioTheme.baseStationColor))
                context.fill(Path(ellipseIn: CGRect(x: topScreen.x - 5, y: topScreen.y - 5, width: 10, height: 10)), with: .color(StudioTheme.topStationColor))
                
                // Draw Midstations if any
                for mid in seg.parsedMidstations {
                    let midScreen = MapCoordinateSpace.mapToScreen(point: mid, in: canvasSize)
                    var diamond = Path()
                    diamond.move(to: CGPoint(x: midScreen.x, y: midScreen.y - 6))
                    diamond.addLine(to: CGPoint(x: midScreen.x + 6, y: midScreen.y))
                    diamond.addLine(to: CGPoint(x: midScreen.x, y: midScreen.y + 6))
                    diamond.addLine(to: CGPoint(x: midScreen.x - 6, y: midScreen.y))
                    diamond.closeSubpath()
                    context.fill(diamond, with: .color(StudioTheme.midstationColor))
                }
            } else {
                // Slope: Solid (or dashed for crosscountry)
                let isDashed = seg.type.lowercased() == "crosscountry" || seg.type.lowercased() == "luge"
                let style = StrokeStyle(
                    lineWidth: 4.5,
                    lineCap: .round,
                    lineJoin: .round,
                    dash: isDashed ? [6, 4] : []
                )
                context.stroke(path, with: .color(baseColor), style: style)
            }
        }
    }
    
    private func renderEditorActiveOverlay(context: GraphicsContext, canvasSize: CGSize) {
        guard store.editorMode != .browse && !store.editorPoints.isEmpty else { return }
        
        let pts = store.editorPoints
        var path = Path()
        path.move(to: MapCoordinateSpace.mapToScreen(point: pts[0], in: canvasSize))
        for i in 1..<pts.count {
            path.addLine(to: MapCoordinateSpace.mapToScreen(point: pts[i], in: canvasSize))
        }
        
        if case .drawSectorBoundary = store.editorMode {
            path.closeSubpath()
            context.fill(path, with: .color(StudioTheme.blueDifficulty.opacity(0.2)))
            context.stroke(path, with: .color(StudioTheme.blueDifficulty), style: StrokeStyle(lineWidth: 3, lineCap: .round, lineJoin: .round, dash: [6, 4]))
        } else {
            let isLift = store.editorType.lowercased() == "lift"
            let accentColor = isLift ? StudioTheme.liftColor : StudioTheme.colorForSlopeType(store.editorType, fallbackDifficulty: store.editorDifficulty)
            
            // Halo
            context.stroke(path, with: .color(Color.white.opacity(0.4)), style: StrokeStyle(lineWidth: 10, lineCap: .round, lineJoin: .round))
            
            // Line
            let dash: [CGFloat] = isLift ? [8, 5] : []
            context.stroke(path, with: .color(accentColor), style: StrokeStyle(lineWidth: 5, lineCap: .round, lineJoin: .round, dash: dash))
        }
        
        // Draw Magnetic Snap indicator ring if active
        if let snap = store.currentSnapTarget {
            let snapScreen = MapCoordinateSpace.mapToScreen(point: snap.snappedPoint, in: canvasSize)
            let snapRect = CGRect(x: snapScreen.x - 14, y: snapScreen.y - 14, width: 28, height: 28)
            context.stroke(Path(ellipseIn: snapRect), with: .color(StudioTheme.borderActive), style: StrokeStyle(lineWidth: 2.5, dash: [4, 4]))
        }
    }
    
    // MARK: - Interactive Handles (SwiftUI Views for Vertex & Midpoint Drags)
    
    @ViewBuilder
    private func interactiveHandlesOverlay(canvasSize: CGSize) -> some View {
        if store.editorMode != .browse {
            ZStack {
                // 1. Midpoint '+' Handles (Click or drag to bend line)
                if store.editorPoints.count >= 2 {
                    ForEach(0..<(store.editorPoints.count - 1), id: \.self) { idx in
                        let p1 = MapCoordinateSpace.mapToScreen(point: store.editorPoints[idx], in: canvasSize)
                        let p2 = MapCoordinateSpace.mapToScreen(point: store.editorPoints[idx + 1], in: canvasSize)
                        let mid = CGPoint(x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2)
                        
                        Circle()
                            .fill(StudioTheme.borderActive)
                            .frame(width: 18, height: 18)
                            .overlay(Text("+").font(.system(size: 14, weight: .bold)).foregroundColor(.black))
                            .shadow(radius: 3)
                            .position(mid)
                            .onTapGesture {
                                let newMapPt = MapCoordinateSpace.screenToMap(screenPoint: mid, in: canvasSize)
                                store.insertVertex(at: idx + 1, point: newMapPt)
                            }
                    }
                }
                
                // 2. Vertex Handles
                ForEach(0..<store.editorPoints.count, id: \.self) { idx in
                    let screenPt = MapCoordinateSpace.mapToScreen(point: store.editorPoints[idx], in: canvasSize)
                    let isStart = idx == 0
                    let isEnd = idx == store.editorPoints.count - 1
                    let isSelected = store.selectedVertexIndex == idx
                    
                    let handleColor: Color = {
                        if isStart { return StudioTheme.baseStationColor }
                        if isEnd { return StudioTheme.topStationColor }
                        return Color.white
                    }()
                    
                    ZStack {
                        Circle()
                            .fill(handleColor)
                            .frame(width: 22, height: 22)
                            .shadow(color: .black.opacity(0.5), radius: 3)
                        
                        if isStart {
                            Text("S").font(.system(size: 11, weight: .black)).foregroundColor(.black)
                        } else if isEnd {
                            Text("E").font(.system(size: 11, weight: .black)).foregroundColor(.white)
                        } else {
                            Circle().fill(Color.black).frame(width: 8, height: 8)
                        }
                        
                        if isSelected {
                            Circle()
                                .stroke(StudioTheme.borderActive, lineWidth: 3)
                                .frame(width: 32, height: 32)
                        }
                    }
                    .position(screenPt)
                    .gesture(
                        DragGesture(minimumDistance: 1, coordinateSpace: .local)
                            .onChanged { val in
                                draggingVertexIndex = idx
                                var mapPt = MapCoordinateSpace.screenToMap(screenPoint: val.location, in: canvasSize)
                                if store.snapEnabled {
                                    if let target = MapCoordinateSpace.findSnapTarget(
                                        for: mapPt,
                                        in: store.segments,
                                        excludingSegmentId: store.selectedSegment?.id,
                                        canvasSize: canvasSize
                                    ) {
                                        mapPt = target.snappedPoint
                                        store.currentSnapTarget = target
                                    } else {
                                        store.currentSnapTarget = nil
                                    }
                                }
                                store.updateVertex(at: idx, to: mapPt)
                            }
                            .onEnded { _ in
                                draggingVertexIndex = nil
                                store.currentSnapTarget = nil
                                store.pushUndoState()
                            }
                    )
                    .onTapGesture {
                        store.selectedVertexIndex = idx
                    }
                    .contextMenu {
                        Button(role: .destructive) {
                            store.deleteVertex(at: idx)
                        } label: {
                            Label("Delete Vertex", systemImage: "trash")
                        }
                    }
                }
            }
        }
    }
    
    // MARK: - Gestures
    
    private var canvasMagnificationGesture: some Gesture {
        MagnificationGesture()
            .onChanged { delta in
                currentZoomDelta = delta
            }
            .onEnded { delta in
                zoomScale = max(1.0, min(8.0, zoomScale * delta))
                currentZoomDelta = 1.0
                store.imageLoader.notifyZoomScale(zoomScale, baseURL: MapStudioAPIClient.shared.baseURLString)
            }
    }
    
    private var canvasPanGesture: some Gesture {
        DragGesture(minimumDistance: 10)
            .onChanged { val in
                // Only pan if not currently dragging a vertex
                if draggingVertexIndex == nil {
                    currentPanDelta = val.translation
                }
            }
            .onEnded { val in
                if draggingVertexIndex == nil {
                    panOffset.width += val.translation.width
                    panOffset.height += val.translation.height
                    currentPanDelta = .zero
                }
            }
    }
    
    private func canvasTapGesture(canvasSize: CGSize, totalScale: CGFloat, totalOffset: CGSize, viewportSize: CGSize) -> some Gesture {
        SpatialTapGesture()
            .onEnded { val in
                // Convert viewport touch to mountain canvas coordinate
                let localX = (val.location.x - viewportSize.width / 2 - totalOffset.width) / totalScale + canvasSize.width / 2
                let localY = (val.location.y - viewportSize.height / 2 - totalOffset.height) / totalScale + canvasSize.height / 2
                let tapPoint = CGPoint(x: localX, y: localY)
                
                guard localX >= 0 && localX <= canvasSize.width && localY >= 0 && localY <= canvasSize.height else {
                    return
                }
                
                var mapPt = MapCoordinateSpace.screenToMap(screenPoint: tapPoint, in: canvasSize)
                
                if store.editorMode.isDrawing || (store.editorMode != .browse && store.appendMode) {
                    // Check snap
                    if store.snapEnabled {
                        if let snap = MapCoordinateSpace.findSnapTarget(
                            for: mapPt,
                            in: store.segments,
                            excludingSegmentId: store.selectedSegment?.id,
                            canvasSize: canvasSize
                        ) {
                            mapPt = snap.snappedPoint
                        }
                    }
                    store.addPoint(mapPt)
                } else if store.editorMode == .browse {
                    // Hit test segments to select
                    hitTestSegments(at: mapPt)
                }
            }
    }
    
    private func hitTestSegments(at mapPt: StudioPoint) {
        let thresholdMapUnits = 14.0
        var bestSeg: StudioSegment? = nil
        var bestDist = thresholdMapUnits
        
        for seg in store.segments {
            for pt in seg.parsedPoints {
                let dy = pt.y - mapPt.y
                let dx = pt.x - mapPt.x
                let dist = sqrt(dy * dy + dx * dx)
                if dist < bestDist {
                    bestDist = dist
                    bestSeg = seg
                }
            }
        }
        
        if let tapped = bestSeg {
            store.startEditingSegment(tapped)
            store.isInspectorPresented = true
        }
    }
}
