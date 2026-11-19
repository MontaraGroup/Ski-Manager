import Foundation

public enum PendingOpType: String, Codable {
    case saveSegment
    case deleteSegment
    case saveMidstations
    case saveSectorBoundary
}

public struct PendingOperation: Identifiable, Codable, Equatable {
    public var id: UUID
    public var type: PendingOpType
    public var timestamp: Date
    public var segment: StudioSegment?
    public var segmentId: Int?
    public var sectorId: Int?
    public var points: [[Double]]?
    
    public init(
        id: UUID = UUID(),
        type: PendingOpType,
        timestamp: Date = Date(),
        segment: StudioSegment? = nil,
        segmentId: Int? = nil,
        sectorId: Int? = nil,
        points: [[Double]]? = nil
    ) {
        self.id = id
        self.type = type
        self.timestamp = timestamp
        self.segment = segment
        self.segmentId = segmentId
        self.sectorId = sectorId
        self.points = points
    }
}

public final class DraftSyncQueue: @unchecked Sendable {
    public static let shared = DraftSyncQueue()
    
    private let queueFilePath: URL
    private let lock = NSLock()
    
    public init() {
        let appSupport = FileManager.default.urls(for: .applicationSupportDirectory, in: .userDomainMask).first!
        let dir = appSupport.appendingPathComponent("MapStudioDrafts", isDirectory: true)
        try? FileManager.default.createDirectory(at: dir, withIntermediateDirectories: true)
        self.queueFilePath = dir.appendingPathComponent("pending_ops.json")
    }
    
    public func loadPendingOps() -> [PendingOperation] {
        lock.lock()
        defer { lock.unlock() }
        
        guard let data = try? Data(contentsOf: queueFilePath),
              let ops = try? JSONDecoder().decode([PendingOperation].self, from: data) else {
            return []
        }
        return ops
    }
    
    public func enqueue(_ op: PendingOperation) {
        lock.lock()
        defer { lock.unlock() }
        
        var ops: [PendingOperation] = []
        if let data = try? Data(contentsOf: queueFilePath),
           let existing = try? JSONDecoder().decode([PendingOperation].self, from: data) {
            ops = existing
        }
        ops.append(op)
        if let encoded = try? JSONEncoder().encode(ops) {
            try? encoded.write(to: queueFilePath, options: .atomic)
        }
    }
    
    public func remove(id: UUID) {
        lock.lock()
        defer { lock.unlock() }
        
        guard let data = try? Data(contentsOf: queueFilePath),
              var ops = try? JSONDecoder().decode([PendingOperation].self, from: data) else {
            return
        }
        ops.removeAll { $0.id == id }
        if let encoded = try? JSONEncoder().encode(ops) {
            try? encoded.write(to: queueFilePath, options: .atomic)
        }
    }
    
    public func clear() {
        lock.lock()
        defer { lock.unlock() }
        try? FileManager.default.removeItem(at: queueFilePath)
    }
    
    public var count: Int {
        loadPendingOps().count
    }
}
