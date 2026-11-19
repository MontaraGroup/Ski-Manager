import SwiftUI

public struct LoginView: View {
    @ObservedObject var store: MapStudioStore
    @AppStorage("mapstudio_server_env") private var serverEnvironment: ServerEnv = .production
    @State private var customServerURL: String = ""
    @State private var identifier: String = ""
    @State private var password: String = ""
    @State private var isLoading: Bool = false
    @State private var errorMessage: String? = nil
    
    enum ServerEnv: String, CaseIterable, Identifiable {
        case staging = "Staging"
        case production = "Production"
        case custom = "Custom"
        
        var id: String { rawValue }
        
        var urlString: String {
            switch self {
            case .staging: return "https://staging.ski-manager.net"
            case .production: return "https://ski-manager.net"
            case .custom: return ""
            }
        }
    }
    
    public init(store: MapStudioStore) {
        self.store = store
    }
    
    public var body: some View {
        ZStack {
            StudioTheme.canvasBackground.ignoresSafeArea()
            
            ScrollView {
                VStack(spacing: 28) {
                    Spacer(minLength: 40)
                    
                    // Brand Header
                    VStack(spacing: 12) {
                        Image(systemName: "compass.drawing")
                            .font(.system(size: 56, weight: .semibold))
                            .foregroundStyle(
                                LinearGradient(
                                    colors: [StudioTheme.liftColor, StudioTheme.blueDifficulty],
                                    startPoint: .topLeading,
                                    endPoint: .bottomTrailing
                                )
                            )
                            .shadow(color: StudioTheme.liftColor.opacity(0.35), radius: 16)
                        
                        Text("Ski Manager Map Studio")
                            .font(.system(size: 28, weight: .bold))
                            .foregroundColor(StudioTheme.textPrimary)
                        
                        Text("Administrator Trail & Mountain Mapping Suite")
                            .font(.subheadline)
                            .foregroundColor(StudioTheme.textSecondary)
                    }
                    
                    // Login Card
                    VStack(alignment: .leading, spacing: 20) {
                        // Environment Selector
                        VStack(alignment: .leading, spacing: 8) {
                            Text("SERVER ENVIRONMENT")
                                .font(.caption)
                                .fontWeight(.bold)
                                .foregroundColor(StudioTheme.textMuted)
                            
                            Picker("Environment", selection: $serverEnvironment) {
                                ForEach(ServerEnv.allCases) { env in
                                    Text(env.rawValue).tag(env)
                                }
                            }
                            .pickerStyle(.segmented)
                            
                            if serverEnvironment == .custom {
                                TextField("https://example.com", text: $customServerURL)
                                    .textFieldStyle(.plain)
                                    .padding(10)
                                    .background(StudioTheme.canvasBackground)
                                    .cornerRadius(8)
                                    .overlay(
                                        RoundedRectangle(cornerRadius: 8)
                                            .stroke(StudioTheme.borderSubtle, lineWidth: 1)
                                    )
                                    .foregroundColor(.white)
                                    #if os(iOS)
                                    .textInputAutocapitalization(.never)
                                    #endif
                            }
                        }
                        
                        // Credentials
                        VStack(alignment: .leading, spacing: 14) {
                            VStack(alignment: .leading, spacing: 6) {
                                Text("ADMIN EMAIL OR USERNAME")
                                    .font(.caption)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                
                                TextField("admin@ski-manager.net", text: $identifier)
                                    .textFieldStyle(.plain)
                                    .padding(12)
                                    .background(StudioTheme.canvasBackground)
                                    .cornerRadius(8)
                                    .overlay(
                                        RoundedRectangle(cornerRadius: 8)
                                            .stroke(StudioTheme.borderSubtle, lineWidth: 1)
                                    )
                                    .foregroundColor(.white)
                                    #if os(iOS)
                                    .textInputAutocapitalization(.never)
                                    .keyboardType(.emailAddress)
                                    #endif
                                    .disableAutocorrection(true)
                            }
                            
                            VStack(alignment: .leading, spacing: 6) {
                                Text("PASSWORD")
                                    .font(.caption)
                                    .fontWeight(.bold)
                                    .foregroundColor(StudioTheme.textMuted)
                                
                                SecureField("••••••••••••", text: $password)
                                    .textFieldStyle(.plain)
                                    .padding(12)
                                    .background(StudioTheme.canvasBackground)
                                    .cornerRadius(8)
                                    .overlay(
                                        RoundedRectangle(cornerRadius: 8)
                                            .stroke(StudioTheme.borderSubtle, lineWidth: 1)
                                    )
                                    .foregroundColor(.white)
                            }
                        }
                        
                        // Error Alert Banner
                        if let error = errorMessage {
                            HStack(alignment: .top, spacing: 10) {
                                Image(systemName: "exclamationmark.triangle.fill")
                                    .foregroundColor(StudioTheme.topStationColor)
                                Text(error)
                                    .font(.footnote)
                                    .foregroundColor(StudioTheme.topStationColor)
                            }
                            .padding(12)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(StudioTheme.topStationColor.opacity(0.12))
                            .cornerRadius(8)
                            .overlay(
                                RoundedRectangle(cornerRadius: 8)
                                    .stroke(StudioTheme.topStationColor.opacity(0.3), lineWidth: 1)
                            )
                        }
                        
                        // Sign In Button
                        Button(action: handleLogin) {
                            HStack {
                                if isLoading {
                                    ProgressView()
                                        .tint(.black)
                                        .padding(.trailing, 4)
                                }
                                Text(isLoading ? "Signing In…" : "Sign In to Map Studio")
                                    .font(.headline)
                                    .foregroundColor(.black)
                            }
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 14)
                            .background(StudioTheme.liftColor)
                            .cornerRadius(10)
                            .shadow(color: StudioTheme.liftColor.opacity(0.3), radius: 8, y: 3)
                        }
                        .disabled(isLoading || identifier.isEmpty || password.isEmpty)
                        .opacity((isLoading || identifier.isEmpty || password.isEmpty) ? 0.6 : 1.0)
                    }
                    .padding(24)
                    .background(StudioTheme.cardBackground)
                    .cornerRadius(16)
                    .overlay(
                        RoundedRectangle(cornerRadius: 16)
                            .stroke(StudioTheme.borderMedium, lineWidth: 1)
                    )
                    .frame(maxWidth: 440)
                    .padding(.horizontal, 20)
                    
                    Spacer(minLength: 40)
                }
            }
        }
    }
    
    private func handleLogin() {
        errorMessage = nil
        isLoading = true
        
        let client = MapStudioAPIClient.shared
        let targetURL = serverEnvironment == .custom ? customServerURL : serverEnvironment.urlString
        guard !targetURL.isEmpty else {
            errorMessage = "Please enter a valid server URL."
            isLoading = false
            return
        }
        client.baseURLString = targetURL
        
        let cleanId = identifier.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !cleanId.isEmpty else {
            errorMessage = "Please enter your administrator username or email."
            isLoading = false
            return
        }
        
        Task {
            do {
                let sessionData = try await client.login(identifier: cleanId, password: password)
                await MainActor.run {
                    self.store.currentUser = sessionData.user
                    self.store.isAuthenticated = true
                    self.isLoading = false
                }
                await store.loadInitialData()
            } catch {
                await MainActor.run {
                    self.errorMessage = error.localizedDescription
                    self.isLoading = false
                }
            }
        }
    }
}
