import Foundation

public enum MapStudioAPIError: LocalizedError {
    case invalidURL
    case unauthenticated
    case forbidden(String)
    case serverError(Int, String)
    case decodingError(Error)
    case networkError(Error)
    
    public var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "Invalid server URL."
        case .unauthenticated:
            return "Your session has ended. Please sign in again."
        case .forbidden(let message):
            return message
        case .serverError(let code, let message):
            return "\(message) (HTTP \(code))"
        case .decodingError(let error):
            return "Failed to parse server response: \(error.localizedDescription)"
        case .networkError(let error):
            return "Network connection failed: \(error.localizedDescription)"
        }
    }
}

public final class MapStudioAPIClient: @unchecked Sendable {
    public static let shared = MapStudioAPIClient()
    
    private let session: URLSession
    private let tokenKey = "mapstudio_auth_token"
    private let baseServerKey = "mapstudio_base_server"
    
    public var baseURLString: String {
        get {
            UserDefaults.standard.string(forKey: baseServerKey) ?? "https://staging.ski-manager.net"
        }
        set {
            UserDefaults.standard.set(newValue, forKey: baseServerKey)
        }
    }
    
    public var token: String? {
        get {
            UserDefaults.standard.string(forKey: tokenKey)
        }
        set {
            if let val = newValue {
                UserDefaults.standard.set(val, forKey: tokenKey)
            } else {
                UserDefaults.standard.removeObject(forKey: tokenKey)
            }
        }
    }
    
    public init(session: URLSession = .shared) {
        self.session = session
    }
    
    // MARK: - Core Request Dispatcher
    
    private func makeRequest(
        path: String,
        method: String = "GET",
        queryItems: [URLQueryItem]? = nil,
        bodyData: Data? = nil,
        contentType: String = "application/json"
    ) throws -> URLRequest {
        guard var components = URLComponents(string: baseURLString.trimmingCharacters(in: CharacterSet(charactersIn: "/"))) else {
            throw MapStudioAPIError.invalidURL
        }
        
        let cleanedPath = path.starts(with: "/") ? path : "/\(path)"
        components.path = components.path + cleanedPath
        if let query = queryItems, !query.isEmpty {
            components.queryItems = query
        }
        
        guard let url = components.url else {
            throw MapStudioAPIError.invalidURL
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue(contentType, forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        
        if let token = self.token {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        
        if let body = bodyData {
            request.httpBody = body
        }
        
        return request
    }
    
    private func execute<T: Codable>(request: URLRequest) async throws -> T {
        let (data, response): (Data, URLResponse)
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw MapStudioAPIError.networkError(error)
        }
        
        guard let httpResponse = response as? HTTPURLResponse else {
            throw MapStudioAPIError.serverError(-1, "Invalid server response.")
        }
        
        if httpResponse.statusCode == 401 {
            self.token = nil
            throw MapStudioAPIError.unauthenticated
        }
        
        if httpResponse.statusCode == 403 {
            var msg = "Forbidden: Admin access required."
            if let rawJson = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
               let errMsg = rawJson["message"] as? String ?? rawJson["error"] as? String {
                msg = errMsg
            }
            throw MapStudioAPIError.forbidden(msg)
        }
        
        if httpResponse.statusCode >= 400 {
            var msg = "Request failed."
            if let rawJson = try? JSONSerialization.jsonObject(with: data) as? [String: Any] {
                if let m = rawJson["message"] as? String {
                    msg = m
                } else if let e = rawJson["error"] as? String {
                    msg = e
                }
            }
            throw MapStudioAPIError.serverError(httpResponse.statusCode, msg)
        }
        
        do {
            let decoder = JSONDecoder()
            let envelope = try decoder.decode(StudioApiResponse<T>.self, from: data)
            if let dataVal = envelope.data {
                return dataVal
            } else if let direct = try? decoder.decode(T.self, from: data) {
                return direct
            } else {
                throw MapStudioAPIError.serverError(httpResponse.statusCode, envelope.message ?? "Empty data in server payload.")
            }
        } catch let decErr {
            // Check if top-level T can decode directly
            if let direct = try? JSONDecoder().decode(T.self, from: data) {
                return direct
            }
            throw MapStudioAPIError.decodingError(decErr)
        }
    }
    
    // MARK: - Auth
    
    public func login(identifier: String, password: String) async throws -> StudioSessionData {
        let formParams = "email=\(identifier.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? identifier)&password=\(password.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? password)"
        let bodyData = formParams.data(using: .utf8)
        
        let req = try makeRequest(
            path: "/api/v1/app/auth/login",
            method: "POST",
            bodyData: bodyData,
            contentType: "application/x-www-form-urlencoded"
        )
        
        let sessionData: StudioSessionData = try await execute(request: req)
        
        guard sessionData.user.isAdmin else {
            self.token = nil
            throw MapStudioAPIError.forbidden("Access denied: Account '\(sessionData.user.username)' is not an administrator.")
        }
        
        self.token = sessionData.token
        return sessionData
    }
    
    public func logout() async {
        if let req = try? makeRequest(path: "/api/v1/app/auth/logout", method: "POST") {
            _ = try? await session.data(for: req)
        }
        self.token = nil
    }
    
    // MARK: - Maps & Segments API
    
    public func fetchMaps() async throws -> StudioMapsPayload {
        let req = try makeRequest(path: "/api/v1/app/admin/map/maps")
        return try await execute(request: req)
    }
    
    public func fetchSegments(map: String? = nil) async throws -> StudioSegmentsPayload {
        var queryItems: [URLQueryItem]? = nil
        if let mapKey = map {
            queryItems = [URLQueryItem(name: "map", value: mapKey)]
        }
        let req = try makeRequest(path: "/api/v1/app/admin/map", queryItems: queryItems)
        return try await execute(request: req)
    }
    
    public func changeMap(mapKey: String) async throws {
        let body = ["map": mapKey]
        let data = try JSONSerialization.data(withJSONObject: body)
        let req = try makeRequest(path: "/api/v1/app/admin/map/change-map", method: "POST", bodyData: data)
        let _: [String: AnyCodable] = try await execute(request: req)
    }
    
    // MARK: - Segment Mutations
    
    public func saveSegment(_ seg: StudioSegment) async throws -> StudioSegment {
        var dict: [String: Any] = [
            "name": seg.name,
            "type": seg.type,
            "sector": seg.sector,
            "length_meters": seg.lengthMeters,
            "points": seg.points
        ]
        if let diff = seg.difficulty, !diff.isEmpty {
            dict["difficulty"] = diff
        }
        if seg.id > 0 {
            dict["id"] = seg.id
        }
        
        let data = try JSONSerialization.data(withJSONObject: dict)
        let req = try makeRequest(path: "/api/v1/app/admin/map/segment", method: "POST", bodyData: data)
        
        struct SaveResponse: Codable {
            var success: Bool?
            var id: Int?
            var segment: StudioSegment?
        }
        
        let res: SaveResponse = try await execute(request: req)
        if let s = res.segment {
            return s
        }
        var updated = seg
        if let newId = res.id {
            updated.id = newId
        }
        return updated
    }
    
    public func deleteSegment(id: Int) async throws {
        let req = try makeRequest(path: "/api/v1/app/admin/map/segment/delete/\(id)", method: "POST")
        let _: [String: AnyCodable] = try await execute(request: req)
    }
    
    public func saveMidstations(segmentId: Int, midstations: [[Double]]) async throws {
        let body = ["midstations": midstations]
        let data = try JSONSerialization.data(withJSONObject: body)
        let req = try makeRequest(path: "/api/v1/app/admin/map/segment/midstation/\(segmentId)", method: "POST", bodyData: data)
        let _: [String: AnyCodable] = try await execute(request: req)
    }
    
    // MARK: - Sector Mutations
    
    public func createSector(name: String, color: String, boundaryPoints: [[Double]] = []) async throws -> StudioSector {
        var body: [String: Any] = [
            "name": name,
            "color": color
        ]
        if !boundaryPoints.isEmpty {
            body["boundary_points"] = boundaryPoints
        }
        let data = try JSONSerialization.data(withJSONObject: body)
        let req = try makeRequest(path: "/api/v1/app/admin/map/sector/create-with-boundary", method: "POST", bodyData: data)
        
        struct CreateSectorResponse: Codable {
            var success: Bool?
            var id: Int?
            var sector: StudioSector?
        }
        let res: CreateSectorResponse = try await execute(request: req)
        if let sec = res.sector {
            return sec
        }
        return StudioSector(id: res.id ?? 0, name: name, color: color, boundaryPoints: boundaryPoints)
    }
    
    public func saveSectorBoundary(sectorId: Int, boundaryPoints: [[Double]]) async throws {
        let body = ["id": sectorId, "boundary_points": boundaryPoints] as [String : Any]
        let data = try JSONSerialization.data(withJSONObject: body)
        let req = try makeRequest(path: "/api/v1/app/admin/map/sector/boundary/\(sectorId)", method: "POST", bodyData: data)
        let _: [String: AnyCodable] = try await execute(request: req)
    }
    
    public func toggleSector(sectorId: Int) async throws -> Bool {
        let req = try makeRequest(path: "/api/v1/app/admin/map/sector/toggle/\(sectorId)", method: "POST")
        struct ToggleResponse: Codable {
            var success: Bool?
            var visible: Bool?
        }
        let res: ToggleResponse = try await execute(request: req)
        return res.visible ?? true
    }
    
    public func deleteSector(sectorId: Int) async throws {
        let req = try makeRequest(path: "/api/v1/app/admin/map/sector/delete/\(sectorId)", method: "POST")
        let _: [String: AnyCodable] = try await execute(request: req)
    }
    
    public func autoAssignSectors() async throws -> Int {
        let req = try makeRequest(path: "/api/v1/app/admin/map/sector/auto-assign", method: "POST")
        struct AutoAssignResponse: Codable {
            var success: Bool?
            var assigned: Int?
        }
        let res: AutoAssignResponse = try await execute(request: req)
        return res.assigned ?? 0
    }
}

// Helper Codable for dynamic dictionaries
public struct AnyCodable: Codable {}
