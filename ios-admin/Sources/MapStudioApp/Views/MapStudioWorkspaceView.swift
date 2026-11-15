import SwiftUI

public struct MapStudioWorkspaceView: View {
    @ObservedObject var store: MapStudioStore
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        ZStack {
            // Main Mountain Canvas
            MapCanvasView(store: store)
                .ignoresSafeArea()
            
            // Top Workspace Overlay (Toolbar & Status Banner)
            VStack(spacing: 8) {
                // Floating Action Toolbar
                EditorToolbarView(store: store)
                    .padding(.horizontal, 16)
                    .padding(.top, 4)
                
                // Status / Save Pill (if saving, offline, or toast)
                HStack {
                    syncStatusPill
                    Spacer()
                }
                .padding(.horizontal, 20)
                
                Spacer()
            }
            .safeAreaPadding(.top)
            
            // Toast Notification Overlay
            if let toast = store.toastMessage {
                VStack {
                    Spacer()
                    HStack(spacing: 8) {
                        Image(systemName: "info.circle.fill")
                            .foregroundColor(StudioTheme.liftColor)
                        Text(toast)
                            .font(.subheadline)
                            .fontWeight(.semibold)
                            .foregroundColor(.white)
                    }
                    .padding(.horizontal, 16)
                    .padding(.vertical, 10)
                    .background(Color.black.opacity(0.85))
                    .cornerRadius(24)
                    .overlay(
                        RoundedRectangle(cornerRadius: 24)
                            .stroke(StudioTheme.borderMedium, lineWidth: 1)
                    )
                    .shadow(radius: 12)
                    .padding(.bottom, 28)
                    .transition(.move(edge: .bottom).combined(with: .opacity))
                }
                .safeAreaPadding(.bottom)
                .animation(.spring(response: 0.3), value: store.toastMessage)
            }
        }
        .sheet(isPresented: $store.isMapPickerPresented) {
            MapPickerView(store: store)
                .presentationDetents([.medium, .large])
        }
        .sheet(isPresented: $store.isInspectorPresented) {
            InspectorDrawerView(store: store)
                .presentationDetents([.medium, .large])
        }
        .sheet(isPresented: $store.isSectorManagerPresented) {
            SectorManagerView(store: store)
                .presentationDetents([.medium, .large])
        }
    }
    
    @ViewBuilder
    private var syncStatusPill: some View {
        switch store.saveStatus {
        case .saving:
            HStack(spacing: 6) {
                ProgressView().tint(.white).scaleEffect(0.7)
                Text("Saving to server…")
                    .font(.caption2)
                    .foregroundColor(.white)
            }
            .padding(.horizontal, 10)
            .padding(.vertical, 5)
            .background(Color.black.opacity(0.7))
            .cornerRadius(14)
        case .saved:
            EmptyView()
        case .failed(let msg):
            HStack(spacing: 6) {
                Image(systemName: "exclamationmark.triangle.fill")
                    .foregroundColor(StudioTheme.topStationColor)
                Text("Save failed: \(msg)")
                    .font(.caption2)
                    .foregroundColor(StudioTheme.topStationColor)
            }
            .padding(.horizontal, 10)
            .padding(.vertical, 5)
            .background(StudioTheme.topStationColor.opacity(0.15))
            .cornerRadius(14)
        case .offlinePending(let count):
            Button {
                Task {
                    await store.drainOfflineQueue()
                }
            } label: {
                HStack(spacing: 6) {
                    Image(systemName: "wifi.slash")
                        .foregroundColor(StudioTheme.liftColor)
                    Text("\(count) offline draft changes (Tap to Retry)")
                        .font(.caption2)
                        .fontWeight(.bold)
                        .foregroundColor(StudioTheme.liftColor)
                }
                .padding(.horizontal, 10)
                .padding(.vertical, 5)
                .background(StudioTheme.cardBackground)
                .cornerRadius(14)
                .overlay(
                    RoundedRectangle(cornerRadius: 14)
                        .stroke(StudioTheme.liftColor.opacity(0.4), lineWidth: 1)
                )
            }
        }
    }
}
