import SwiftUI

public struct MapPickerView: View {
    @ObservedObject var store: MapStudioStore
    @Environment(\.dismiss) private var dismiss
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        NavigationStack {
            ZStack {
                StudioTheme.canvasBackground.ignoresSafeArea()
                
                ScrollView {
                    LazyVStack(spacing: 12) {
                        ForEach(Array(store.availableMaps.values.sorted(by: { $0.name < $1.name }))) { mapConfig in
                            let isSelected = mapConfig.key == store.currentMapKey
                            
                            Button {
                                Task {
                                    await store.selectMountain(key: mapConfig.key)
                                    dismiss()
                                }
                            } label: {
                                HStack(spacing: 14) {
                                    // Thumbnail / Mountain Icon
                                    ZStack {
                                        RoundedRectangle(cornerRadius: 10)
                                            .fill(StudioTheme.cardBackground)
                                            .frame(width: 54, height: 54)
                                        
                                        Image(systemName: "mountain.2.fill")
                                            .font(.title2)
                                            .foregroundColor(isSelected ? StudioTheme.liftColor : StudioTheme.textMuted)
                                    }
                                    
                                    VStack(alignment: .leading, spacing: 4) {
                                        HStack(spacing: 8) {
                                            Text(mapConfig.name)
                                                .font(.headline)
                                                .foregroundColor(StudioTheme.textPrimary)
                                            
                                            if isSelected {
                                                Text("ACTIVE")
                                                    .font(.system(size: 10, weight: .bold))
                                                    .foregroundColor(.black)
                                                    .padding(.horizontal, 6)
                                                    .padding(.vertical, 2)
                                                    .background(StudioTheme.liftColor)
                                                    .cornerRadius(4)
                                            }
                                        }
                                        
                                        Text(mapConfig.location)
                                            .font(.subheadline)
                                            .foregroundColor(StudioTheme.textSecondary)
                                        
                                        Text("Canvas: \(Int(mapConfig.width)) × \(Int(mapConfig.height))")
                                            .font(.caption2)
                                            .foregroundColor(StudioTheme.textMuted)
                                    }
                                    
                                    Spacer()
                                    
                                    if isSelected {
                                        Image(systemName: "checkmark.circle.fill")
                                            .foregroundColor(StudioTheme.liftColor)
                                            .font(.title3)
                                    } else {
                                        Image(systemName: "chevron.right")
                                            .foregroundColor(StudioTheme.textMuted)
                                            .font(.caption)
                                    }
                                }
                                .padding(14)
                                .background(isSelected ? StudioTheme.cardBackground : StudioTheme.surfaceBackground)
                                .cornerRadius(12)
                                .overlay(
                                    RoundedRectangle(cornerRadius: 12)
                                        .stroke(isSelected ? StudioTheme.liftColor.opacity(0.6) : StudioTheme.borderSubtle, lineWidth: isSelected ? 1.5 : 1)
                                )
                            }
                            .buttonStyle(.plain)
                        }
                    }
                    .padding(16)
                }
            }
            .navigationTitle("Select Mountain")
            #if os(iOS)
            .navigationBarTitleDisplayMode(.inline)
            #endif
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Done") {
                        dismiss()
                    }
                    .foregroundColor(StudioTheme.liftColor)
                }
            }
        }
    }
}
