import SwiftUI

public struct RootView: View {
    @StateObject private var store = MapStudioStore.shared
    
    public init() {}
    
    public var body: some View {
        Group {
            if store.isAuthenticated {
                MapStudioWorkspaceView(store: store)
            } else {
                LoginView(store: store)
            }
        }
        .task {
            if store.isAuthenticated {
                await store.loadInitialData()
            }
        }
    }
}
