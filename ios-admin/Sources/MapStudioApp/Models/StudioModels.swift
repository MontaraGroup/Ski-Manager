import Foundation

// MARK: - Map Point
public struct StudioPoint: Codable, Equatable, Hashable {
    public var y: Double // lat: 0 (bottom/base) to 340 (top/summit)
    public var x: Double // lng: 0 (left) to 600 (right)
    
    public init(y: Double, x: Double) {
        self.y = y
        self.x = x
    }
    
    public init(array: [Double]) {
        self.y = array.count > 0 ? array[0] : 0
        self.x = array.count > 1 ? array[1] : 0
    }
    
    public var asArray: [Double] {
        [roundTo4(y), roundTo4(x)]
    }
    
    private func roundTo4(_ val: Double) -> Double {
        (val * 10000.0).rounded() / 10000.0
    }
}

// MARK: - Map Segment
public struct StudioSegment: Identifiable, Codable, Equatable {
    public var id: Int
    public var name: String
    public var type: String // "lift", "downhill", "crosscountry", "snowpark", "luge", "slope"
    public var difficulty: String? // "green", "blue", "black", "double_black", "park"
    public var sector: Int
    public var lengthMeters: Int
    public var points: [[Double]] // [[y, x], ...]
    public var midstations: [[Double]]? // [[y, x], ...]
    public var active: Int
    public var isLocalDraft: Bool = false
    
    public init(
        id: Int,
        name: String,
        type: String,
        difficulty: String? = nil,
        sector: Int = 0,
        lengthMeters: Int = 0,
        points: [[Double]] = [],
        midstations: [[Double]]? = nil,
        active: Int = 1,
        isLocalDraft: Bool = false
    ) {
        self.id = id
        self.name = name
        self.type = type
        self.difficulty = difficulty
        self.sector = sector
        self.lengthMeters = lengthMeters
        self.points = points
        self.midstations = midstations
        self.active = active
        self.isLocalDraft = isLocalDraft
    }
    
    public var isLift: Bool {
        type.lowercased() == "lift"
    }
    
    public var parsedPoints: [StudioPoint] {
        points.map { StudioPoint(array: $0) }
    }
    
    public var parsedMidstations: [StudioPoint] {
        (midstations ?? []).map { StudioPoint(array: $0) }
    }
    
    enum CodingKeys: String, CodingKey {
        case id, name, type, difficulty, sector, points, midstations, active
        case lengthMeters = "length_meters"
    }
    
    public init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        if let intId = try? container.decode(Int.self, forKey: .id) {
            self.id = intId
        } else if let strId = try? container.decode(String.self, forKey: .id), let intVal = Int(strId) {
            self.id = intVal
        } else {
            self.id = 0
        }
        
        self.name = (try? container.decode(String.self, forKey: .name)) ?? "Unnamed"
        self.type = (try? container.decode(String.self, forKey: .type)) ?? "lift"
        self.difficulty = try? container.decodeIfPresent(String.self, forKey: .difficulty)
        
        if let secInt = try? container.decode(Int.self, forKey: .sector) {
            self.sector = secInt
        } else if let secStr = try? container.decode(String.self, forKey: .sector), let intVal = Int(secStr) {
            self.sector = intVal
        } else {
            self.sector = 0
        }
        
        if let lmInt = try? container.decode(Int.self, forKey: .lengthMeters) {
            self.lengthMeters = lmInt
        } else if let lmStr = try? container.decode(String.self, forKey: .lengthMeters), let intVal = Int(lmStr) {
            self.lengthMeters = intVal
        } else if let lmDbl = try? container.decode(Double.self, forKey: .lengthMeters) {
            self.lengthMeters = Int(lmDbl.rounded())
        } else {
            self.lengthMeters = 0
        }
        
        if let ptsArray = try? container.decode([[Double]].self, forKey: .points) {
            self.points = ptsArray
        } else if let ptsStr = try? container.decode(String.self, forKey: .points),
                  let data = ptsStr.data(using: .utf8),
                  let decoded = try? JSONDecoder().decode([[Double]].self, from: data) {
            self.points = decoded
        } else {
            self.points = []
        }
        
        if let midsArray = try? container.decode([[Double]].self, forKey: .midstations) {
            self.midstations = midsArray
        } else if let midsStr = try? container.decode(String.self, forKey: .midstations),
                  let data = midsStr.data(using: .utf8),
                  let decoded = try? JSONDecoder().decode([[Double]].self, from: data) {
            self.midstations = decoded
        } else {
            self.midstations = nil
        }
        
        if let actInt = try? container.decode(Int.self, forKey: .active) {
            self.active = actInt
        } else if let actBool = try? container.decode(Bool.self, forKey: .active) {
            self.active = actBool ? 1 : 0
        } else {
            self.active = 1
        }
        
        self.isLocalDraft = false
    }
}

// MARK: - Resort Sector
public struct StudioSector: Identifiable, Codable, Equatable {
    public var id: Int
    public var name: String
    public var color: String
    public var description: String?
    public var boundaryPoints: [[Double]] // [[y, x], ...]
    public var visible: Bool
    
    public init(
        id: Int,
        name: String,
        color: String = "#3B82F6",
        description: String? = nil,
        boundaryPoints: [[Double]] = [],
        visible: Bool = true
    ) {
        self.id = id
        self.name = name
        self.color = color
        self.description = description
        self.boundaryPoints = boundaryPoints
        self.visible = visible
    }
    
    public var parsedBoundary: [StudioPoint] {
        boundaryPoints.map { StudioPoint(array: $0) }
    }
    
    enum CodingKeys: String, CodingKey {
        case id, name, color, description, visible
        case boundaryPoints = "boundary_points"
    }
    
    public init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        if let intId = try? container.decode(Int.self, forKey: .id) {
            self.id = intId
        } else if let strId = try? container.decode(String.self, forKey: .id), let intVal = Int(strId) {
            self.id = intVal
        } else {
            self.id = 0
        }
        
        self.name = (try? container.decode(String.self, forKey: .name)) ?? "New Sector"
        self.color = (try? container.decode(String.self, forKey: .color)) ?? "#3B82F6"
        self.description = try? container.decodeIfPresent(String.self, forKey: .description)
        
        if let bpArray = try? container.decode([[Double]].self, forKey: .boundaryPoints) {
            self.boundaryPoints = bpArray
        } else if let bpStr = try? container.decode(String.self, forKey: .boundaryPoints),
                  let data = bpStr.data(using: .utf8),
                  let decoded = try? JSONDecoder().decode([[Double]].self, from: data) {
            self.boundaryPoints = decoded
        } else {
            self.boundaryPoints = []
        }
        
        if let visInt = try? container.decode(Int.self, forKey: .visible) {
            self.visible = visInt == 1
        } else if let visBool = try? container.decode(Bool.self, forKey: .visible) {
            self.visible = visBool
        } else {
            self.visible = true
        }
    }
}

// MARK: - Resort Map Config
public struct StudioMapUrls: Codable, Equatable {
    public var low: String
    public var med: String
    public var full: String
    public var fallbackLow: String
    public var fallbackMed: String
    public var fallbackFull: String
    
    enum CodingKeys: String, CodingKey {
        case low, med, full, fallbackLow, fallbackMed, fallbackFull
    }
}

public struct StudioMapConfig: Identifiable, Codable, Equatable {
    public var id: String { key }
    public var key: String
    public var name: String
    public var location: String
    public var width: Double
    public var height: Double
    public var image: String
    public var adminImage: String?
    public var urls: StudioMapUrls
    
    enum CodingKeys: String, CodingKey {
        case key, name, location, width, height, image, urls
        case adminImage = "admin_image"
    }

    public init(
        key: String,
        name: String,
        location: String,
        width: Double,
        height: Double,
        image: String,
        adminImage: String? = nil,
        urls: StudioMapUrls
    ) {
        self.key = key
        self.name = name
        self.location = location
        self.width = width
        self.height = height
        self.image = image
        self.adminImage = adminImage
        self.urls = urls
    }

    public init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        
        let decodedImage = (try? container.decode(String.self, forKey: .image)) ?? "/img/ParkCity.jpg"
        self.image = decodedImage
        self.adminImage = try? container.decodeIfPresent(String.self, forKey: .adminImage)
        
        if let decodedKey = try? container.decode(String.self, forKey: .key), !decodedKey.isEmpty {
            self.key = decodedKey
        } else {
            let fn = (decodedImage as NSString).lastPathComponent
            let baseName = (fn as NSString).deletingPathExtension
                .replacingOccurrences(of: "-Big", with: "")
                .replacingOccurrences(of: "_low", with: "")
                .replacingOccurrences(of: "_med", with: "")
            self.key = baseName.isEmpty ? "ParkCity" : baseName
        }
        
        self.name = (try? container.decode(String.self, forKey: .name)) ?? self.key
        self.location = (try? container.decode(String.self, forKey: .location)) ?? ""
        
        if let wDbl = try? container.decode(Double.self, forKey: .width) {
            self.width = wDbl
        } else if let wInt = try? container.decode(Int.self, forKey: .width) {
            self.width = Double(wInt)
        } else {
            self.width = 600.0
        }
        
        if let hDbl = try? container.decode(Double.self, forKey: .height) {
            self.height = hDbl
        } else if let hInt = try? container.decode(Int.self, forKey: .height) {
            self.height = Double(hInt)
        } else {
            self.height = 340.0
        }
        
        if let decodedUrls = try? container.decode(StudioMapUrls.self, forKey: .urls) {
            self.urls = decodedUrls
        } else {
            let activeImg = self.adminImage ?? self.image
            let basePath = (activeImg as NSString).deletingPathExtension
            self.urls = StudioMapUrls(
                low: "\(basePath)_low.jpg",
                med: "\(basePath)_med.jpg",
                full: activeImg,
                fallbackLow: "\(basePath)_low.jpg",
                fallbackMed: "\(basePath)_med.jpg",
                fallbackFull: activeImg
            )
        }
    }

    public static let defaultMaps: [String: StudioMapConfig] = [
        "ParkCity": StudioMapConfig(
            key: "ParkCity",
            name: "Park City",
            location: "Utah, USA",
            width: 600,
            height: 340,
            image: "/img/ParkCity.jpg",
            adminImage: "/img/ParkCity-Big.jpg",
            urls: StudioMapUrls(
                low: "/img/ParkCity-Big_low.jpg",
                med: "/img/ParkCity-Big_med.jpg",
                full: "/img/ParkCity-Big.jpg",
                fallbackLow: "/img/ParkCity-Big_low.jpg",
                fallbackMed: "/img/ParkCity-Big_med.jpg",
                fallbackFull: "/img/ParkCity-Big.jpg"
            )
        ),
        "DeerValley": StudioMapConfig(
            key: "DeerValley",
            name: "Deer Valley",
            location: "Utah, USA",
            width: 600,
            height: 340,
            image: "/img/DeerValley.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/DeerValley_low.jpg",
                med: "/img/DeerValley_med.jpg",
                full: "/img/DeerValley.jpg",
                fallbackLow: "/img/DeerValley_low.jpg",
                fallbackMed: "/img/DeerValley_med.jpg",
                fallbackFull: "/img/DeerValley.jpg"
            )
        ),
        "AspenSnowmass": StudioMapConfig(
            key: "AspenSnowmass",
            name: "Aspen Snowmass",
            location: "Colorado, USA",
            width: 600,
            height: 340,
            image: "/img/AspenSnowmass.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/AspenSnowmass_low.jpg",
                med: "/img/AspenSnowmass_med.jpg",
                full: "/img/AspenSnowmass.jpg",
                fallbackLow: "/img/AspenSnowmass_low.jpg",
                fallbackMed: "/img/AspenSnowmass_med.jpg",
                fallbackFull: "/img/AspenSnowmass.jpg"
            )
        ),
        "BigSkyCombo": StudioMapConfig(
            key: "BigSkyCombo",
            name: "Big Sky",
            location: "Montana, USA",
            width: 600,
            height: 340,
            image: "/img/BigSkyCombo.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/BigSkyCombo_low.jpg",
                med: "/img/BigSkyCombo_med.jpg",
                full: "/img/BigSkyCombo.jpg",
                fallbackLow: "/img/BigSkyCombo_low.jpg",
                fallbackMed: "/img/BigSkyCombo_med.jpg",
                fallbackFull: "/img/BigSkyCombo.jpg"
            )
        ),
        "Vail": StudioMapConfig(
            key: "Vail",
            name: "Vail",
            location: "Colorado, USA",
            width: 600,
            height: 340,
            image: "/img/Vail.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/Vail_low.jpg",
                med: "/img/Vail_med.jpg",
                full: "/img/Vail.jpg",
                fallbackLow: "/img/Vail_low.jpg",
                fallbackMed: "/img/Vail_med.jpg",
                fallbackFull: "/img/Vail.jpg"
            )
        ),
        "PalisadesTahoe": StudioMapConfig(
            key: "PalisadesTahoe",
            name: "Palisades Tahoe",
            location: "California, USA",
            width: 600,
            height: 340,
            image: "/img/PalisadesTahoe.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/PalisadesTahoe_low.jpg",
                med: "/img/PalisadesTahoe_med.jpg",
                full: "/img/PalisadesTahoe.jpg",
                fallbackLow: "/img/PalisadesTahoe_low.jpg",
                fallbackMed: "/img/PalisadesTahoe_med.jpg",
                fallbackFull: "/img/PalisadesTahoe.jpg"
            )
        ),
        "Killington": StudioMapConfig(
            key: "Killington",
            name: "Killington",
            location: "Vermont, USA",
            width: 600,
            height: 340,
            image: "/img/Killington.jpg",
            adminImage: nil,
            urls: StudioMapUrls(
                low: "/img/Killington_low.jpg",
                med: "/img/Killington_med.jpg",
                full: "/img/Killington.jpg",
                fallbackLow: "/img/Killington_low.jpg",
                fallbackMed: "/img/Killington_med.jpg",
                fallbackFull: "/img/Killington.jpg"
            )
        )
    ]
}

// MARK: - Admin Auth Models
public struct AdminUser: Codable, Equatable {
    public var id: Int
    public var username: String
    public var isAdmin: Bool
    
    enum CodingKeys: String, CodingKey {
        case id, username, isAdmin
    }
    
    public init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        self.id = try container.decode(Int.self, forKey: .id)
        self.username = try container.decode(String.self, forKey: .username)
        // If server returns explicit isAdmin boolean, use it. Otherwise, default to true for authenticated admin app access
        if let explicit = try? container.decode(Bool.self, forKey: .isAdmin) {
            self.isAdmin = explicit
        } else {
            self.isAdmin = true
        }
    }
}

public struct StudioSessionData: Codable {
    public var token: String
    public var user: AdminUser
    public var profileComplete: Bool?
}

// MARK: - API Response Envelope
public struct StudioApiResponse<T: Codable>: Codable {
    public var ok: Bool
    public var data: T?
    public var message: String?
}

public struct StudioMapsPayload: Codable {
    public var currentMap: String
    public var maps: [String: StudioMapConfig]
}

public struct StudioSegmentsPayload: Codable {
    public var resortMap: String
    public var mapConfig: StudioMapConfig
    public var sectors: [StudioSector]
    public var segments: [StudioSegment]
}

// MARK: - Editor Mode
public enum StudioEditorMode: Equatable {
    case browse
    case drawLift
    case drawSlope
    case editSegment(segmentId: Int)
    case drawSectorBoundary(sectorId: Int)
    
    public var isDrawing: Bool {
        switch self {
        case .drawLift, .drawSlope, .drawSectorBoundary: return true
        default: return false
        }
    }
}

// MARK: - Built-in Resort Segments & Sectors
extension StudioSegment {
    public static let defaultParkCitySegments: [StudioSegment] = {
        guard let data = DefaultParkCityData.rawJSON.data(using: .utf8),
              let decoded = try? JSONDecoder().decode([StudioSegment].self, from: data) else {
            return []
        }
        return decoded
    }()
}

extension StudioSector {
    public static let defaultParkCitySectors: [StudioSector] = [
        StudioSector(id: 6, name: "Park City Mountain", color: "#3B82F6", description: "Main mountain area", boundaryPoints: [], visible: true),
        StudioSector(id: 2, name: "Canyons Village", color: "#10B981", description: "Canyons side", boundaryPoints: [], visible: true)
    ]
}

