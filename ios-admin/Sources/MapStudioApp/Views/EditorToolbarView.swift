import SwiftUI

public struct EditorToolbarView: View {
    @ObservedObject var store: MapStudioStore
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        HStack {
            if store.editorMode == .browse {
                idleToolbar
            } else {
                activeEditorToolbar
            }
        }
        .padding(10)
        .background(StudioTheme.panelGlass)
        .cornerRadius(14)
        .overlay(
            RoundedRectangle(cornerRadius: 14)
                .stroke(StudioTheme.borderMedium, lineWidth: 1)
        )
        .shadow(color: .black.opacity(0.4), radius: 12, y: 4)
    }
    
    // MARK: - Idle State Toolbar
    
    private var idleToolbar: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                // Resort Map Pill
                Button {
                    store.isMapPickerPresented = true
                } label: {
                    HStack(spacing: 6) {
                        Image(systemName: "mountain.2.fill")
                            .foregroundColor(StudioTheme.liftColor)
                        Text(store.currentMapConfig?.name ?? store.currentMapKey)
                            .font(.subheadline)
                            .fontWeight(.bold)
                            .foregroundColor(.white)
                        Image(systemName: "chevron.down")
                            .font(.caption2)
                            .foregroundColor(StudioTheme.textMuted)
                    }
                    .padding(.horizontal, 10)
                    .padding(.vertical, 6)
                    .background(StudioTheme.cardBackground)
                    .cornerRadius(8)
                }
                
                Divider().frame(height: 20).background(StudioTheme.borderSubtle)
                
                // + New Lift
                Button {
                    store.startNewLift()
                } label: {
                    Label("+ Lift", systemImage: "arrow.up.and.down.and.arrow.left.and.right")
                        .font(.caption)
                        .fontWeight(.bold)
                        .padding(.horizontal, 10)
                        .padding(.vertical, 6)
                        .background(StudioTheme.liftColor.opacity(0.2))
                        .foregroundColor(StudioTheme.liftColor)
                        .cornerRadius(8)
                }
                
                // + New Slope
                Button {
                    store.startNewSlope()
                } label: {
                    Label("+ Slope", systemImage: "figure.skiing.downhill")
                        .font(.caption)
                        .fontWeight(.bold)
                        .padding(.horizontal, 10)
                        .padding(.vertical, 6)
                        .background(StudioTheme.greenDifficulty.opacity(0.2))
                        .foregroundColor(StudioTheme.greenDifficulty)
                        .cornerRadius(8)
                }
                
                // Snap Toggle
                Button {
                    store.snapEnabled.toggle()
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "magnet")
                        Text("Snap: \(store.snapEnabled ? "ON" : "OFF")")
                    }
                    .font(.caption)
                    .fontWeight(.semibold)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 6)
                    .background(store.snapEnabled ? StudioTheme.cardBackground : Color.clear)
                    .foregroundColor(store.snapEnabled ? .white : StudioTheme.textMuted)
                    .cornerRadius(8)
                }
                
                // Sector Polygons Toggle
                Button {
                    store.showSectorPolygons.toggle()
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "square.dashed")
                        Text("Sectors: \(store.showSectorPolygons ? "ON" : "OFF")")
                    }
                    .font(.caption)
                    .fontWeight(.semibold)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 6)
                    .background(store.showSectorPolygons ? StudioTheme.cardBackground : Color.clear)
                    .foregroundColor(store.showSectorPolygons ? StudioTheme.blueDifficulty : StudioTheme.textMuted)
                    .cornerRadius(8)
                }
                
                // Sector Manager Sheet
                Button {
                    store.isSectorManagerPresented = true
                } label: {
                    Label("Sectors", systemImage: "square.stack.3d.up.fill")
                        .font(.caption)
                        .fontWeight(.semibold)
                        .padding(.horizontal, 8)
                        .padding(.vertical, 6)
                        .background(StudioTheme.cardBackground)
                        .foregroundColor(.white)
                        .cornerRadius(8)
                }
            }
        }
    }
    
    // MARK: - Active Editor State Toolbar
    
    private var activeEditorToolbar: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                // Mode Badge
                let badgeText: String = {
                    switch store.editorMode {
                    case .drawLift: return "NEW LIFT"
                    case .drawSlope: return "NEW SLOPE"
                    case .editSegment: return "EDITING RUN"
                    case .drawSectorBoundary: return "SECTOR BOUNDARY"
                    case .browse: return ""
                    }
                }()
                
                Text(badgeText)
                    .font(.system(size: 11, weight: .black))
                    .foregroundColor(.black)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(store.editorType.lowercased() == "lift" ? StudioTheme.liftColor : StudioTheme.greenDifficulty)
                    .cornerRadius(6)
                
                // Live Length & Points HUD
                HStack(spacing: 6) {
                    Text("\(store.liveLengthMeters) m")
                        .font(.system(size: 13, weight: .bold, design: .monospaced))
                        .foregroundColor(StudioTheme.liftColor)
                    Text("|")
                        .foregroundColor(StudioTheme.textMuted)
                    Text("\(store.editorPoints.count) pts")
                        .font(.system(size: 12, weight: .medium, design: .monospaced))
                        .foregroundColor(StudioTheme.borderActive)
                }
                .padding(.horizontal, 8)
                .padding(.vertical, 4)
                .background(StudioTheme.cardBackground)
                .cornerRadius(6)
                
                // Segment Name TextField
                TextField("Run Name", text: $store.editorName)
                    .textFieldStyle(.plain)
                    .font(.subheadline)
                    .frame(width: 130)
                    .padding(6)
                    .background(StudioTheme.canvasBackground)
                    .cornerRadius(6)
                    .foregroundColor(.white)
                
                // Reverse S <-> E Direction
                Button {
                    store.reverseDirection()
                } label: {
                    Image(systemName: "arrow.left.arrow.right")
                        .font(.caption)
                        .foregroundColor(.white)
                        .padding(6)
                        .background(StudioTheme.cardBackground)
                        .cornerRadius(6)
                }
                
                // Undo
                Button {
                    store.undo()
                } label: {
                    Image(systemName: "arrow.uturn.backward")
                        .font(.caption)
                        .foregroundColor(StudioTheme.liftColor)
                        .padding(6)
                        .background(StudioTheme.cardBackground)
                        .cornerRadius(6)
                }
                
                // Save Button
                Button {
                    Task {
                        await store.saveCurrentEdit()
                    }
                } label: {
                    HStack(spacing: 4) {
                        Image(systemName: "checkmark")
                        Text("Save")
                    }
                    .font(.caption)
                    .fontWeight(.bold)
                    .padding(.horizontal, 12)
                    .padding(.vertical, 6)
                    .background(StudioTheme.baseStationColor)
                    .foregroundColor(.white)
                    .cornerRadius(6)
                }
                
                // Cancel Button
                Button {
                    store.cancelEditing()
                } label: {
                    Image(systemName: "xmark")
                        .font(.caption)
                        .foregroundColor(StudioTheme.topStationColor)
                        .padding(6)
                        .background(StudioTheme.cardBackground)
                        .cornerRadius(6)
                }
            }
        }
    }
}
