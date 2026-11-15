import SwiftUI
#if canImport(UIKit)
import UIKit
public typealias PlatformImage = UIImage
#elseif canImport(AppKit)
import AppKit
public typealias PlatformImage = NSImage
#endif

@MainActor
public final class ProgressiveMapImageLoader: ObservableObject {
    @Published public var displayedImage: PlatformImage? = nil
    @Published public var loadedTier: ImageTier = .none
    @Published public var isLoadingHighRes: Bool = false
    
    public enum ImageTier: String, Comparable {
        case none = "None"
        case low = "Low"
        case med = "Medium"
        case full = "Full HD"
        
        public static func < (lhs: ImageTier, rhs: ImageTier) -> Bool {
            let order: [ImageTier] = [.none, .low, .med, .full]
            let li = order.firstIndex(of: lhs) ?? 0
            let ri = order.firstIndex(of: rhs) ?? 0
            return li < ri
        }
    }
    
    private var currentConfig: StudioMapConfig? = nil
    private let cache = NSCache<NSString, PlatformImage>()
    
    public init() {
        cache.countLimit = 20
    }
    
    public func loadMap(config: StudioMapConfig, baseURL: String) {
        if currentConfig?.key == config.key && displayedImage != nil {
            return
        }
        self.currentConfig = config
        self.displayedImage = nil
        self.loadedTier = .none
        self.isLoadingHighRes = false
        
        let urls = config.urls
        let lowUrlStr = resolveUrl(urls.low, fallback: urls.fallbackLow, baseURL: baseURL)
        let medUrlStr = resolveUrl(urls.med, fallback: urls.fallbackMed, baseURL: baseURL)
        
        // 1. Immediately load low
        Task {
            if let lowImg = await fetchImage(urlString: lowUrlStr) {
                if currentConfig?.key == config.key && loadedTier < .low {
                    self.displayedImage = lowImg
                    self.loadedTier = .low
                }
            }
            
            // 2. Seamlessly upgrade to medium
            if let medImg = await fetchImage(urlString: medUrlStr) {
                if currentConfig?.key == config.key && loadedTier < .med {
                    self.displayedImage = medImg
                    self.loadedTier = .med
                }
            }
        }
    }
    
    public func notifyZoomScale(_ scale: CGFloat, baseURL: String) {
        guard let config = currentConfig, scale >= 1.5, loadedTier < .full, !isLoadingHighRes else {
            return
        }
        
        let urls = config.urls
        let fullUrlStr = resolveUrl(urls.full, fallback: urls.fallbackFull, baseURL: baseURL)
        self.isLoadingHighRes = true
        
        Task {
            if let fullImg = await fetchImage(urlString: fullUrlStr) {
                if self.currentConfig?.key == config.key {
                    self.displayedImage = fullImg
                    self.loadedTier = .full
                }
            }
            self.isLoadingHighRes = false
        }
    }
    
    private func resolveUrl(_ primary: String, fallback: String, baseURL: String) -> String {
        let path = primary.isEmpty ? fallback : primary
        if path.starts(with: "http://") || path.starts(with: "https://") {
            return path
        }
        let cleanBase = baseURL.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        let cleanPath = path.starts(with: "/") ? path : "/\(path)"
        return cleanBase + cleanPath
    }
    
    private func fetchImage(urlString: String) async -> PlatformImage? {
        if let cached = cache.object(forKey: urlString as NSString) {
            return cached
        }
        
        guard let url = URL(string: urlString) else { return nil }
        guard let (data, res) = try? await URLSession.shared.data(from: url),
              let http = res as? HTTPURLResponse, http.statusCode == 200 else {
            return nil
        }
        
        #if canImport(UIKit)
        if let img = UIImage(data: data) {
            cache.setObject(img, forKey: urlString as NSString)
            return img
        }
        #elseif canImport(AppKit)
        if let img = NSImage(data: data) {
            cache.setObject(img, forKey: urlString as NSString)
            return img
        }
        #endif
        return nil
    }
}
