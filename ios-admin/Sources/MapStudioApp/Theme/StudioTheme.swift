import SwiftUI

public enum StudioTheme {
    // MARK: - Core Accents
    public static let liftColor = Color(hex: "#F59E0B")
    public static let baseStationColor = Color(hex: "#10B981")
    public static let topStationColor = Color(hex: "#EF4444")
    public static let midstationColor = Color(hex: "#A855F7")
    
    // MARK: - Slope Difficulties
    public static let greenDifficulty = Color(hex: "#22C55E")
    public static let blueDifficulty = Color(hex: "#3B82F6")
    public static let blackDifficulty = Color(hex: "#1E293B")
    public static let doubleBlackDifficulty = Color(hex: "#DC2626")
    public static let parkDifficulty = Color(hex: "#A855F7")
    public static let crossCountryColor = Color(hex: "#14B8A6")
    public static let lugeColor = Color(hex: "#EC4899")
    
    // MARK: - Surface & UI
    public static let canvasBackground = Color(hex: "#090D16")
    public static let surfaceBackground = Color(hex: "#131C2E")
    public static let cardBackground = Color(hex: "#1E293B")
    public static let panelGlass = Color(hex: "#0F172A").opacity(0.88)
    public static let borderSubtle = Color.white.opacity(0.12)
    public static let borderMedium = Color.white.opacity(0.22)
    public static let borderActive = Color(hex: "#38BDF8")
    
    public static let textPrimary = Color.white
    public static let textSecondary = Color.white.opacity(0.75)
    public static let textMuted = Color.white.opacity(0.48)
    
    public static func colorForDifficulty(_ difficulty: String?) -> Color {
        switch difficulty?.lowercased().replacingOccurrences(of: "-", with: "_") {
        case "green": return greenDifficulty
        case "blue": return blueDifficulty
        case "black": return blackDifficulty
        case "double_black": return doubleBlackDifficulty
        case "park", "snowpark", "terrain_park": return parkDifficulty
        default: return blueDifficulty
        }
    }
    
    public static func colorForSlopeType(_ slopeType: String?, fallbackDifficulty: String? = nil) -> Color {
        switch slopeType?.lowercased() {
        case "snowpark", "park": return parkDifficulty
        case "crosscountry": return crossCountryColor
        case "luge": return lugeColor
        default: return colorForDifficulty(fallbackDifficulty)
        }
    }
}

// MARK: - Color Hex Extension
extension Color {
    public init(hex: String) {
        let cleaned = hex.trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        var int: UInt64 = 0
        Scanner(string: cleaned).scanHexInt64(&int)
        let a, r, g, b: UInt64
        switch cleaned.count {
        case 3: // RGB (12-bit)
            (a, r, g, b) = (255, (int >> 8) * 17, (int >> 4 & 0xF) * 17, (int & 0xF) * 17)
        case 6: // RGB (24-bit)
            (a, r, g, b) = (255, int >> 16, int >> 8 & 0xFF, int & 0xFF)
        case 8: // ARGB (32-bit)
            (a, r, g, b) = (int >> 24, int >> 16 & 0xFF, int >> 8 & 0xFF, int & 0xFF)
        default:
            (a, r, g, b) = (255, 128, 128, 128)
        }
        self.init(
            .sRGB,
            red: Double(r) / 255.0,
            green: Double(g) / 255.0,
            blue: Double(b) / 255.0,
            opacity: Double(a) / 255.0
        )
    }
}
