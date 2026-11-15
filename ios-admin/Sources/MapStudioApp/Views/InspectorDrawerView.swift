import SwiftUI

public struct InspectorDrawerView: View {
    @ObservedObject var store: MapStudioStore
    @Environment(\.dismiss) private var dismiss
    @State private var showDeleteConfirm: Bool = false
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        NavigationStack {
            ZStack {
                StudioTheme.surfaceBackground.ignoresSafeArea()
                
                ScrollView {
                    VStack(alignment: .leading, spacing: 20) {
                        // Title & Type Header
                        HStack {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(store.editorName.isEmpty ? "Segment Inspector" : store.editorName)
                                    .font(.title3)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textPrimary)
                                
                                if let seg = store.selectedSegment {
                                    Text("ID: #\(seg.id) • \(seg.type.capitalized)")
                                        .font(.caption)
                                        .foregroundColor(StudioTheme.textMuted)
                                }
                            }
                            Spacer()
                            
                            // Type Badge
                            let isLift = store.editorType.lowercased() == "lift"
                            Text(isLift ? "LIFT" : store.editorDifficulty.uppercased())
                                .font(.system(size: 11, weight: .bold))
                                .foregroundColor(.white)
                                .padding(.horizontal, 8)
                                .padding(.vertical, 4)
                                .background(isLift ? StudioTheme.liftColor : StudioTheme.colorForDifficulty(store.editorDifficulty))
                                .cornerRadius(6)
                        }
                        
                        Divider().background(StudioTheme.borderSubtle)
                        
                        // Name Field
                        VStack(alignment: .leading, spacing: 6) {
                            Text("SEGMENT NAME")
                                .font(.caption2)
                                .fontWeight(.bold)
                                .foregroundColor(StudioTheme.textMuted)
                            
                            TextField("Name", text: $store.editorName)
                                .textFieldStyle(.plain)
                                .padding(10)
                                .background(StudioTheme.cardBackground)
                                .cornerRadius(8)
                                .foregroundColor(.white)
                        }
                        
                        // Sector Picker
                        VStack(alignment: .leading, spacing: 6) {
                            Text("MOUNTAIN SECTOR")
                                .font(.caption2)
                                .fontWeight(.bold)
                                .foregroundColor(StudioTheme.textMuted)
                            
                            Picker("Sector", selection: $store.editorSector) {
                                Text("Default (0)").tag(0)
                                ForEach(store.sectors) { sec in
                                    Text("\(sec.name) (#\(sec.id))").tag(sec.id)
                                }
                            }
                            .pickerStyle(.menu)
                            .padding(8)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(StudioTheme.cardBackground)
                            .cornerRadius(8)
                            .foregroundColor(.white)
                        }
                        
                        // Slopes: Type & Difficulty
                        if store.editorType.lowercased() != "lift" {
                            VStack(alignment: .leading, spacing: 6) {
                                Text("SLOPE TYPE")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                
                                Picker("Slope Type", selection: $store.editorType) {
                                    Text("Downhill").tag("downhill")
                                    Text("Cross-Country").tag("crosscountry")
                                    Text("Snow Park").tag("snowpark")
                                    Text("Luge").tag("luge")
                                }
                                .pickerStyle(.menu)
                                .padding(8)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .background(StudioTheme.cardBackground)
                                .cornerRadius(8)
                                .foregroundColor(.white)
                            }
                            
                            VStack(alignment: .leading, spacing: 6) {
                                Text("DIFFICULTY GRADE")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                
                                Picker("Difficulty", selection: $store.editorDifficulty) {
                                    Text("● Green (Beginner)").tag("green")
                                    Text("■ Blue (Intermediate)").tag("blue")
                                    Text("◆ Black (Advanced)").tag("black")
                                    Text("◆◆ Double Black (Expert)").tag("double_black")
                                    Text("Park / Freestyle").tag("park")
                                }
                                .pickerStyle(.menu)
                                .padding(8)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .background(StudioTheme.cardBackground)
                                .cornerRadius(8)
                                .foregroundColor(.white)
                            }
                        }
                        
                        // Length & Geometry Stat Readouts
                        HStack(spacing: 12) {
                            VStack(alignment: .leading, spacing: 4) {
                                Text("LENGTH")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                Text("\(store.liveLengthMeters) meters")
                                    .font(.system(size: 16, weight: .bold, design: .monospaced))
                                    .foregroundColor(StudioTheme.liftColor)
                            }
                            .padding(12)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(StudioTheme.cardBackground)
                            .cornerRadius(8)
                            
                            VStack(alignment: .leading, spacing: 4) {
                                Text("VERTICES")
                                    .font(.caption2)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                Text("\(store.editorPoints.count) points")
                                    .font(.system(size: 16, weight: .bold, design: .monospaced))
                                    .foregroundColor(StudioTheme.borderActive)
                            }
                            .padding(12)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(StudioTheme.cardBackground)
                            .cornerRadius(8)
                        }
                        
                        // Reverse Direction
                        Button {
                            store.reverseDirection()
                        } label: {
                            Label("Reverse Stations (S ↔ E)", systemImage: "arrow.left.arrow.right")
                                .font(.subheadline)
                                .fontWeight(.semibold)
                                .foregroundColor(.white)
                                .frame(maxWidth: .infinity)
                                .padding(12)
                                .background(StudioTheme.cardBackground)
                                .cornerRadius(8)
                        }
                        
                        // Delete Segment Button
                        if let seg = store.selectedSegment, seg.id > 0 {
                            Button(role: .destructive) {
                                showDeleteConfirm = true
                            } label: {
                                Label("Delete Segment", systemImage: "trash")
                                    .font(.subheadline)
                                    .fontWeight(.semibold)
                                    .foregroundColor(StudioTheme.topStationColor)
                                    .frame(maxWidth: .infinity)
                                    .padding(12)
                                    .background(StudioTheme.topStationColor.opacity(0.12))
                                    .cornerRadius(8)
                                    .overlay(
                                        RoundedRectangle(cornerRadius: 8)
                                            .stroke(StudioTheme.topStationColor.opacity(0.3), lineWidth: 1)
                                    )
                            }
                            .confirmationDialog("Delete Segment?", isPresented: $showDeleteConfirm, titleVisibility: .visible) {
                                Button("Delete \"\(store.editorName)\"", role: .destructive) {
                                    Task {
                                        await store.deleteSegment(seg)
                                        dismiss()
                                    }
                                }
                                Button("Cancel", role: .cancel) {}
                            } message: {
                                Text("This will permanently remove this segment from the resort map.")
                            }
                        }
                    }
                    .padding(20)
                }
            }
            .navigationTitle("Run Inspector")
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
                
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            await store.saveCurrentEdit()
                            dismiss()
                        }
                    }
                    .fontWeight(.bold)
                    .foregroundColor(StudioTheme.baseStationColor)
                }
            }
        }
    }
}
