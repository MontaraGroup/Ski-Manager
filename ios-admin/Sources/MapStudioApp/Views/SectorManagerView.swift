import SwiftUI

public struct SectorManagerView: View {
    @ObservedObject var store: MapStudioStore
    @Environment(\.dismiss) private var dismiss
    
    @State private var showNewSectorForm: Bool = false
    @State private var newSectorName: String = ""
    @State private var newSectorColor: Color = StudioTheme.blueDifficulty
    @State private var deletingSector: StudioSector? = nil
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        NavigationStack {
            ZStack {
                StudioTheme.surfaceBackground.ignoresSafeArea()
                
                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                        // Top actions (Auto-Assign & + Add Sector)
                        HStack(spacing: 10) {
                            Button {
                                Task {
                                    await store.autoAssignSectors()
                                }
                            } label: {
                                Label("Auto-Assign", systemImage: "wand.and.stars")
                                    .font(.caption)
                                    .fontWeight(.bold)
                                    .foregroundColor(.white)
                                    .padding(.horizontal, 12)
                                    .padding(.vertical, 8)
                                    .background(StudioTheme.cardBackground)
                                    .cornerRadius(8)
                                    .overlay(
                                        RoundedRectangle(cornerRadius: 8)
                                            .stroke(StudioTheme.borderSubtle, lineWidth: 1)
                                    )
                            }
                            
                            Spacer()
                            
                            Button {
                                showNewSectorForm.toggle()
                            } label: {
                                Label("Add Sector", systemImage: "plus")
                                    .font(.caption)
                                    .fontWeight(.bold)
                                    .foregroundColor(.white)
                                    .padding(.horizontal, 12)
                                    .padding(.vertical, 8)
                                    .background(StudioTheme.baseStationColor)
                                    .cornerRadius(8)
                            }
                        }
                        
                        // Inline New Sector Creation Card
                        if showNewSectorForm {
                            VStack(alignment: .leading, spacing: 12) {
                                Text("NEW MOUNTAIN SECTOR")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                
                                HStack(spacing: 10) {
                                    TextField("Sector Name (e.g. North Bowl)", text: $newSectorName)
                                        .textFieldStyle(.plain)
                                        .padding(10)
                                        .background(StudioTheme.canvasBackground)
                                        .cornerRadius(8)
                                        .foregroundColor(.white)
                                    
                                    ColorPicker("", selection: $newSectorColor)
                                        .labelsHidden()
                                }
                                
                                HStack {
                                    Spacer()
                                    Button("Cancel") {
                                        showNewSectorForm = false
                                        newSectorName = ""
                                    }
                                    .foregroundColor(StudioTheme.textMuted)
                                    .font(.caption)
                                    
                                    Button("Create") {
                                        let name = newSectorName.trimmingCharacters(in: .whitespaces)
                                        guard !name.isEmpty else { return }
                                        let hex = newSectorColor.toHex() ?? "#3B82F6"
                                        Task {
                                            await store.createSector(name: name, color: hex)
                                            newSectorName = ""
                                            showNewSectorForm = false
                                        }
                                    }
                                    .font(.caption)
                                    .fontWeight(.bold)
                                    .foregroundColor(.white)
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 6)
                                    .background(StudioTheme.baseStationColor)
                                    .cornerRadius(6)
                                }
                            }
                            .padding(14)
                            .background(StudioTheme.cardBackground)
                            .cornerRadius(12)
                            .overlay(
                                RoundedRectangle(cornerRadius: 12)
                                    .stroke(StudioTheme.borderMedium, lineWidth: 1)
                            )
                        }
                        
                        // Sectors List
                        Text("SECTORS (\(store.sectors.count))")
                            .font(.caption2)
                            .fontWeight(.bold)
                            .foregroundColor(StudioTheme.textMuted)
                        
                        LazyVStack(spacing: 10) {
                            ForEach(store.sectors) { sec in
                                let runCount = store.segments.filter { $0.sector == sec.id }.count
                                let hasBoundary = sec.parsedBoundary.count >= 3
                                let secColor = Color(hex: sec.color)
                                
                                HStack(spacing: 12) {
                                    // Sector Color Swatch
                                    ZStack {
                                        RoundedRectangle(cornerRadius: 8)
                                            .fill(secColor.opacity(0.2))
                                            .frame(width: 38, height: 38)
                                            .overlay(
                                                RoundedRectangle(cornerRadius: 8)
                                                    .stroke(secColor, lineWidth: 2)
                                            )
                                        
                                        Image(systemName: "square.dashed")
                                            .foregroundColor(secColor)
                                            .font(.caption)
                                    }
                                    
                                    VStack(alignment: .leading, spacing: 3) {
                                        HStack(spacing: 6) {
                                            Text(sec.name)
                                                .font(.headline)
                                                .foregroundColor(.white)
                                            Text("#\(sec.id)")
                                                .font(.caption2)
                                                .fontDesign(.monospaced)
                                                .foregroundColor(StudioTheme.textMuted)
                                        }
                                        
                                        Text("\(runCount) runs • \(hasBoundary ? "✓ Boundary set" : "No boundary")")
                                            .font(.caption)
                                            .foregroundColor(StudioTheme.textSecondary)
                                    }
                                    
                                    Spacer()
                                    
                                    // Action: Draw/Edit Boundary Polygon
                                    Button {
                                        dismiss()
                                        store.startEditingSectorBoundary(sec)
                                    } label: {
                                        Image(systemName: "pencil.and.outline")
                                            .foregroundColor(hasBoundary ? StudioTheme.borderActive : StudioTheme.textMuted)
                                            .padding(8)
                                            .background(StudioTheme.cardBackground)
                                            .clipShape(Circle())
                                    }
                                    
                                    // Action: Toggle Visibility
                                    Button {
                                        Task {
                                            await store.toggleSectorVisibility(sectorId: sec.id)
                                        }
                                    } label: {
                                        Image(systemName: sec.visible ? "eye.fill" : "eye.slash.fill")
                                            .foregroundColor(sec.visible ? StudioTheme.baseStationColor : StudioTheme.textMuted)
                                            .padding(8)
                                            .background(StudioTheme.cardBackground)
                                            .clipShape(Circle())
                                    }
                                    
                                    // Action: Delete
                                    Button {
                                        deletingSector = sec
                                    } label: {
                                        Image(systemName: "trash")
                                            .foregroundColor(StudioTheme.topStationColor)
                                            .padding(8)
                                            .background(StudioTheme.cardBackground)
                                            .clipShape(Circle())
                                    }
                                }
                                .padding(12)
                                .background(StudioTheme.cardBackground)
                                .cornerRadius(12)
                                .overlay(
                                    RoundedRectangle(cornerRadius: 12)
                                        .stroke(StudioTheme.borderSubtle, lineWidth: 1)
                                )
                            }
                        }
                    }
                    .padding(16)
                }
            }
            .navigationTitle("Mountain Sectors")
            #if os(iOS)
            .navigationBarTitleDisplayMode(.inline)
            #endif
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Close") {
                        dismiss()
                    }
                    .foregroundColor(StudioTheme.textMuted)
                }
            }
            .confirmationDialog("Delete Sector?", isPresented: Binding(
                get: { deletingSector != nil },
                set: { if !$0 { deletingSector = nil } }
            ), titleVisibility: .visible) {
                if let sec = deletingSector {
                    Button("Delete \"\(sec.name)\"", role: .destructive) {
                        Task {
                            await store.deleteSector(sectorId: sec.id)
                            deletingSector = nil
                        }
                    }
                    Button("Cancel", role: .cancel) {
                        deletingSector = nil
                    }
                }
            } message: {
                Text("Segments in this sector will be reset to Default (0).")
            }
        }
    }
}

// MARK: - Color to Hex Helper
extension Color {
    func toHex() -> String? {
        #if canImport(UIKit)
        let uiColor = UIColor(self)
        var r: CGFloat = 0, g: CGFloat = 0, b: CGFloat = 0, a: CGFloat = 0
        guard uiColor.getRed(&r, green: &g, blue: &b, alpha: &a) else { return nil }
        return String(format: "#%02lX%02lX%02lX", lroundf(Float(r * 255)), lroundf(Float(g * 255)), lroundf(Float(b * 255)))
        #else
        return nil
        #endif
    }
}
