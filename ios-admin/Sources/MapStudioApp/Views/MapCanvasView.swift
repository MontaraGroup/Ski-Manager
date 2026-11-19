import SwiftUI

public struct MapCanvasView: View {
    @ObservedObject var store: MapStudioStore
    
    // Zoom and Pan State
    private let minZoomScale: CGFloat = 1.0
    private let maxZoomScale: CGFloat = 36.0
    
    @State private var zoomScale: CGFloat = 1.0
    @State private var activeZoomDelta: CGFloat = 1.0
    @State private var panOffset: CGSize = .zero
    @State private var activePanDelta: CGSize = .zero
    
    // Active dragging vertex
    @State private var draggingVertexIndex: Int? = nil
    @State private var dragInitialVertexPoint: StudioPoint? = nil
    
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
                    // Viewport is wider than 600:340 -> height constrained
                    let h = viewportSize.height
                    return CGSize(width: h * canvasAspect, height: h)
                } else {
                    // Viewport is taller than 600:340 -> width constrained
                    let w = viewportSize.width
                    return CGSize(width: w, height: w / canvasAspect)
                }
            }()
            
            let totalScale = max(minZoomScale, min(maxZoomScale, zoomScale * activeZoomDelta))
            let totalOffset = CGSize(
                width: panOffset.width + activePanDelta.width,
                height: panOffset.height + activePanDelta.height
            )
            
            ZStack {
                // Background void filling full screen
                StudioTheme.canvasBackground
                    .ignoresSafeArea()
                
                // Centered Mountain Canvas Container
                ZStack {
                    // 1. Mountain Map Imagery (Exactly matches 600x340 CRS coordinate space)
                    if let platformImg = store.imageLoader.displayedImage {
                        #if canImport(UIKit)
                        Image(uiImage: platformImg)
                            .resizable()
                            .interpolation(.high)
                            .frame(width: baseSize.width, height: baseSize.height)
                        #elseif canImport(AppKit)
                        Image(nsImage: platformImg)
                            .resizable()
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
                        renderSectors(context: context, canvasSize: size, totalScale: totalScale)
                        renderSegments(context: context, canvasSize: size, totalScale: totalScale)
                        renderEditorActiveOverlay(context: context, canvasSize: size, totalScale: totalScale)
                    }
                    .frame(width: baseSize.width, height: baseSize.height)
                    
                    // 3. Interactive Handles Overlay (Vertices & Midpoints)
                    interactiveHandlesOverlay(canvasSize: baseSize, totalScale: totalScale)
                }
                .frame(width: baseSize.width, height: baseSize.height)
                .scaleEffect(totalScale)
                .offset(totalOffset)
                .gesture(
                    SimultaneousGesture(
                        canvasMagnificationGesture(baseSize: baseSize, viewportSize: viewportSize),
                        canvasPanGesture(baseSize: baseSize, viewportSize: viewportSize)
                    )
                )
                .simultaneousGesture(canvasTapGesture(canvasSize: baseSize, totalScale: totalScale, totalOffset: totalOffset, viewportSize: viewportSize))
                .onTapGesture(count: 2) {
                    withAnimation(.spring(response: 0.35)) {
                        if zoomScale > 1.2 {
                            zoomScale = 1.0
                            panOffset = .zero
                        } else {
                            zoomScale = 3.0
                            store.imageLoader.notifyZoomScale(3.0, baseURL: MapStudioAPIClient.shared.baseURLString)
                        }
                    }
                }
                
                // 4. Floating HUD (Zoom controls, zoom level & HD badge)
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
                    
                    // Bottom-left Zoom Controls & Multiplier Indicator
                    HStack(spacing: 8) {
                        Button {
                            withAnimation(.spring(response: 0.3)) {
                                zoomScale = min(maxZoomScale, zoomScale * 1.5)
                                clampPanOffset(baseSize: baseSize, viewportSize: viewportSize)
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
                                zoomScale = max(minZoomScale, zoomScale / 1.5)
                                if zoomScale <= 1.05 {
                                    zoomScale = 1.0
                                    panOffset = .zero
                                } else {
                                    clampPanOffset(baseSize: baseSize, viewportSize: viewportSize)
                                }
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
                        
                        // Zoom multiplier badge
                        Text(String(format: "%.1fx", totalScale))
                            .font(.caption)
                            .fontWeight(.bold)
                            .foregroundColor(.white)
                            .padding(.horizontal, 10)
                            .frame(height: 38)
                            .background(StudioTheme.panelGlass)
                            .cornerRadius(8)
                            .overlay(RoundedRectangle(cornerRadius: 8).stroke(StudioTheme.borderSubtle, lineWidth: 1))
                        
                        Spacer()
                    }
                    .padding(.leading, 16)
                    .padding(.bottom, 16)
                    .safeAreaPadding(.bottom)
                }
            }
        }
    }
    
    // MARK: - Canvas Rendering (Core Graphics / SwiftUI Canvas)
    
    private func renderSectors(context: GraphicsContext, canvasSize: CGSize, totalScale: CGFloat) {
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
            
            let sWidth = max(1.0, 2.0 / totalScale)
            let sDash: [CGFloat] = [max(1.0, 6.0 / totalScale), max(1.0, 4.0 / totalScale)]
            let strokeStyle = StrokeStyle(lineWidth: sWidth, lineCap: .round, lineJoin: .round, dash: sDash)
            context.stroke(path, with: .color(col), style: strokeStyle)
        }
    }
    
    private func renderSegments(context: GraphicsContext, canvasSize: CGSize, totalScale: CGFloat) {
        let editingSegId: Int? = {
            if case .editSegment(let id) = store.editorMode { return id }
            return nil
        }()
        
        let liftLineWidth = max(1.2, min(7.0, 3.8 + 0.6 * log2(totalScale))) / totalScale
        let liftDash: [CGFloat] = [max(1.0, 9.0 / totalScale), max(1.0, 5.0 / totalScale)]
        let slopeLineWidth = max(1.2, min(7.5, 3.6 + 0.8 * log2(totalScale))) / totalScale
        let stationRadius = max(1.0, 6.0 / totalScale)
        let midRadius = max(1.0, 6.0 / totalScale)
        
        for seg in store.segments {
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
                let haloWidth = max(3.0, 11.0 / totalScale)
                context.stroke(
                    path,
                    with: .color(Color.white.opacity(0.65)),
                    style: StrokeStyle(lineWidth: haloWidth, lineCap: .round, lineJoin: .round)
                )
            }
            
            if seg.isLift {
                // Lift: Dashed amber line
                let style = StrokeStyle(lineWidth: liftLineWidth, lineCap: .round, lineJoin: .round, dash: liftDash)
                context.stroke(path, with: .color(baseColor), style: style)
                
                // Draw Base Station 'S' and Top Station 'E'
                let baseScreen = MapCoordinateSpace.mapToScreen(point: pts[0], in: canvasSize)
                let topScreen = MapCoordinateSpace.mapToScreen(point: pts[pts.count - 1], in: canvasSize)
                
                let baseRect = CGRect(x: baseScreen.x - stationRadius, y: baseScreen.y - stationRadius, width: stationRadius * 2, height: stationRadius * 2)
                let topRect = CGRect(x: topScreen.x - stationRadius, y: topScreen.y - stationRadius, width: stationRadius * 2, height: stationRadius * 2)
                
                context.fill(Path(ellipseIn: baseRect), with: .color(StudioTheme.baseStationColor))
                context.fill(Path(ellipseIn: topRect), with: .color(StudioTheme.topStationColor))
                
                // Draw Midstations if any
                for mid in seg.parsedMidstations {
                    let midScreen = MapCoordinateSpace.mapToScreen(point: mid, in: canvasSize)
                    var diamond = Path()
                    diamond.move(to: CGPoint(x: midScreen.x, y: midScreen.y - midRadius))
                    diamond.addLine(to: CGPoint(x: midScreen.x + midRadius, y: midScreen.y))
                    diamond.addLine(to: CGPoint(x: midScreen.x, y: midScreen.y + midRadius))
                    diamond.addLine(to: CGPoint(x: midScreen.x - midRadius, y: midScreen.y))
                    diamond.closeSubpath()
                    context.fill(diamond, with: .color(StudioTheme.midstationColor))
                }
            } else {
                // Slope: Solid (or dashed for crosscountry)
                let isDashed = seg.type.lowercased() == "crosscountry" || seg.type.lowercased() == "luge"
                let slopeDash: [CGFloat] = isDashed ? [max(1.0, 6.0 / totalScale), max(1.0, 4.0 / totalScale)] : []
                let style = StrokeStyle(
                    lineWidth: slopeLineWidth,
                    lineCap: .round,
                    lineJoin: .round,
                    dash: slopeDash
                )
                context.stroke(path, with: .color(baseColor), style: style)
            }
        }
    }
    
    private func renderEditorActiveOverlay(context: GraphicsContext, canvasSize: CGSize, totalScale: CGFloat) {
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
            let bWidth = max(1.0, 3.0 / totalScale)
            let bDash: [CGFloat] = [max(1.0, 6.0 / totalScale), max(1.0, 4.0 / totalScale)]
            context.stroke(path, with: .color(StudioTheme.blueDifficulty), style: StrokeStyle(lineWidth: bWidth, lineCap: .round, lineJoin: .round, dash: bDash))
        } else {
            let isLift = store.editorType.lowercased() == "lift"
            let accentColor = isLift ? StudioTheme.liftColor : StudioTheme.colorForSlopeType(store.editorType, fallbackDifficulty: store.editorDifficulty)
            
            // Halo
            let activeHalo = max(3.0, 11.0 / totalScale)
            context.stroke(path, with: .color(Color.white.opacity(0.4)), style: StrokeStyle(lineWidth: activeHalo, lineCap: .round, lineJoin: .round))
            
            // Line
            let activeLineWidth = max(1.5, min(8.0, 4.2 + 0.8 * log2(totalScale))) / totalScale
            let dash: [CGFloat] = isLift ? [max(1.0, 8.0 / totalScale), max(1.0, 5.0 / totalScale)] : []
            context.stroke(path, with: .color(accentColor), style: StrokeStyle(lineWidth: activeLineWidth, lineCap: .round, lineJoin: .round, dash: dash))
        }
        
        // Draw Magnetic Snap indicator ring if active
        if let snap = store.currentSnapTarget {
            let snapScreen = MapCoordinateSpace.mapToScreen(point: snap.snappedPoint, in: canvasSize)
            let snapRadius = max(3.0, 14.0 / totalScale)
            let snapRect = CGRect(x: snapScreen.x - snapRadius, y: snapScreen.y - snapRadius, width: snapRadius * 2, height: snapRadius * 2)
            let ringWidth = max(1.0, 2.5 / totalScale)
            let ringDash: [CGFloat] = [max(1.0, 4.0 / totalScale), max(1.0, 4.0 / totalScale)]
            context.stroke(Path(ellipseIn: snapRect), with: .color(StudioTheme.borderActive), style: StrokeStyle(lineWidth: ringWidth, dash: ringDash))
        }
    }
    
    // MARK: - Interactive Handles (SwiftUI Views for Vertex & Midpoint Drags)
    
    @ViewBuilder
    private func interactiveHandlesOverlay(canvasSize: CGSize, totalScale: CGFloat) -> some View {
        if store.editorMode != .browse {
            ZStack {
                // 1. Midpoint '+' Handles (Click to insert vertex)
                if store.editorPoints.count >= 2 {
                    ForEach(0..<(store.editorPoints.count - 1), id: \.self) { idx in
                        let p1 = MapCoordinateSpace.mapToScreen(point: store.editorPoints[idx], in: canvasSize)
                        let p2 = MapCoordinateSpace.mapToScreen(point: store.editorPoints[idx + 1], in: canvasSize)
                        let mid = CGPoint(x: (p1.x + p2.x) / 2, y: (p1.y + p2.y) / 2)
                        
                        Circle()
                            .fill(StudioTheme.borderActive)
                            .frame(width: 20, height: 20)
                            .overlay(Text("+").font(.system(size: 14, weight: .bold)).foregroundColor(.black))
                            .shadow(radius: 2)
                            .scaleEffect(1.0 / totalScale)
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
                            .frame(width: 24, height: 24)
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
                                .frame(width: 34, height: 34)
                        }
                    }
                    .frame(width: 44, height: 44)
                    .contentShape(Rectangle())
                    .scaleEffect(1.0 / totalScale)
                    .position(screenPt)
                    .gesture(
                        DragGesture(minimumDistance: 1)
                            .onChanged { val in
                                if draggingVertexIndex != idx {
                                    draggingVertexIndex = idx
                                    dragInitialVertexPoint = store.editorPoints[idx]
                                }
                                guard let startPt = dragInitialVertexPoint else { return }
                                
                                let deltaCanvasX = val.translation.width / totalScale
                                let deltaCanvasY = val.translation.height / totalScale
                                let deltaMapX = deltaCanvasX * (600.0 / canvasSize.width)
                                let deltaMapY = -deltaCanvasY * (340.0 / canvasSize.height)
                                
                                var mapPt = StudioPoint(
                                    y: max(0, min(340, startPt.y + deltaMapY)),
                                    x: max(0, min(600, startPt.x + deltaMapX))
                                )
                                
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
                                dragInitialVertexPoint = nil
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
    
    // MARK: - Gestures & Pan Clamping
    
    private func clampPanOffset(baseSize: CGSize, viewportSize: CGSize) {
        if zoomScale <= 1.05 {
            withAnimation(.spring(response: 0.25)) {
                panOffset = .zero
            }
            return
        }
        
        let overflowX = max(0, (baseSize.width * zoomScale - viewportSize.width) / 2)
        let overflowY = max(0, (baseSize.height * zoomScale - viewportSize.height) / 2)
        let maxPanX = overflowX + 60
        let maxPanY = overflowY + 60
        
        withAnimation(.spring(response: 0.25)) {
            panOffset.width = min(maxPanX, max(-maxPanX, panOffset.width))
            panOffset.height = min(maxPanY, max(-maxPanY, panOffset.height))
        }
    }
    
    private func canvasMagnificationGesture(baseSize: CGSize, viewportSize: CGSize) -> some Gesture {
        MagnificationGesture()
            .onChanged { delta in
                activeZoomDelta = delta
            }
            .onEnded { delta in
                zoomScale = max(minZoomScale, min(maxZoomScale, zoomScale * delta))
                activeZoomDelta = 1.0
                clampPanOffset(baseSize: baseSize, viewportSize: viewportSize)
                store.imageLoader.notifyZoomScale(zoomScale, baseURL: MapStudioAPIClient.shared.baseURLString)
            }
    }
    
    private func canvasPanGesture(baseSize: CGSize, viewportSize: CGSize) -> some Gesture {
        DragGesture(minimumDistance: 6)
            .onChanged { val in
                guard draggingVertexIndex == nil else { return }
                activePanDelta = val.translation
            }
            .onEnded { val in
                guard draggingVertexIndex == nil else { return }
                panOffset.width += val.translation.width
                panOffset.height += val.translation.height
                activePanDelta = .zero
                clampPanOffset(baseSize: baseSize, viewportSize: viewportSize)
            }
    }
    
    private func canvasTapGesture(canvasSize: CGSize, totalScale: CGFloat, totalOffset: CGSize, viewportSize: CGSize) -> some Gesture {
        SpatialTapGesture()
            .onEnded { val in
                guard draggingVertexIndex == nil else { return }
                
                // Convert viewport touch to mountain canvas coordinate
                let localX = (val.location.x - viewportSize.width / 2 - totalOffset.width) / totalScale + canvasSize.width / 2
                let localY = (val.location.y - viewportSize.height / 2 - totalOffset.height) / totalScale + canvasSize.height / 2
                let tapPoint = CGPoint(x: localX, y: localY)
                
                guard localX >= 0 && localX <= canvasSize.width && localY >= 0 && localY <= canvasSize.height else {
                    return
                }
                
                var mapPt = MapCoordinateSpace.screenToMap(screenPoint: tapPoint, in: canvasSize)
                
                if store.editorMode.isDrawing || (store.editorMode != .browse && store.appendMode) {
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
                    hitTestSegments(at: mapPt, totalScale: totalScale)
                }
            }
    }
    
    private func hitTestSegments(at mapPt: StudioPoint, totalScale: CGFloat) {
        let thresholdMapUnits = max(2.0, 14.0 / totalScale)
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
