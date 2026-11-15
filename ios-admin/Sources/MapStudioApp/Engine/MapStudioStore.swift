import SwiftUI
import Combine

public enum SaveStatus: Equatable {
    case saved
    case saving
    case failed(String)
    case offlinePending(Int)
}

@MainActor
public final class MapStudioStore: ObservableObject {
    public static let shared = MapStudioStore()
    
    // MARK: - Auth State
    @Published public var isAuthenticated: Bool = false
    @Published public var currentUser: AdminUser? = nil
    
    // MARK: - Resort Maps & Segments
    @Published public var availableMaps: [String: StudioMapConfig] = [:]
    @Published public var currentMapKey: String = "ParkCity"
    @Published public var currentMapConfig: StudioMapConfig? = nil
    @Published public var segments: [StudioSegment] = []
    @Published public var sectors: [StudioSector] = []
    
    // MARK: - Active Selection & Inspector
    @Published public var selectedSegment: StudioSegment? = nil
    @Published public var selectedSector: StudioSector? = nil
    @Published public var isInspectorPresented: Bool = false
    @Published public var isSectorManagerPresented: Bool = false
    @Published public var isMapPickerPresented: Bool = false
    
    // MARK: - Editor State
    @Published public var editorMode: StudioEditorMode = .browse
    @Published public var editorType: String = "lift" // "lift" or slope type ("downhill", "crosscountry", etc.)
    @Published public var editorName: String = ""
    @Published public var editorSector: Int = 0
    @Published public var editorDifficulty: String = "blue"
    @Published public var editorPoints: [StudioPoint] = []
    @Published public var editorMidstations: [StudioPoint] = []
    @Published public var selectedVertexIndex: Int? = nil
    @Published public var appendMode: Bool = false
    @Published public var snapEnabled: Bool = true
    @Published public var currentSnapTarget: MapCoordinateSpace.SnapResult? = nil
    
    // MARK: - Undo/Redo Stacks
    private var undoStack: [[StudioPoint]] = []
    private var redoStack: [[StudioPoint]] = []
    
    // MARK: - Filters & Viewport Overlays
    @Published public var filterType: String = "all" // "all", "lift", "slope", "unassigned"
    @Published public var showSectorPolygons: Bool = false
    
    // MARK: - Sync & Network Status
    @Published public var saveStatus: SaveStatus = .saved
    @Published public var toastMessage: String? = nil
    
    public let imageLoader = ProgressiveMapImageLoader()
    private let apiClient = MapStudioAPIClient.shared
    private let syncQueue = DraftSyncQueue.shared
    
    public init() {
        self.availableMaps = StudioMapConfig.defaultMaps
        self.currentMapKey = "ParkCity"
        self.currentMapConfig = StudioMapConfig.defaultMaps["ParkCity"]
        self.segments = StudioSegment.defaultParkCitySegments
        self.sectors = StudioSector.defaultParkCitySectors
        
        if let cfg = self.currentMapConfig {
            imageLoader.loadMap(config: cfg, baseURL: apiClient.baseURLString)
        }
        
        if apiClient.token != nil {
            self.isAuthenticated = true
        }
    }
    
    // MARK: - Mountain Data Loading
    
    public func loadInitialData() async {
        if currentMapConfig == nil {
            self.availableMaps = StudioMapConfig.defaultMaps
            self.currentMapKey = "ParkCity"
            self.currentMapConfig = self.availableMaps["ParkCity"]
        }
        if let cfg = self.currentMapConfig {
            imageLoader.loadMap(config: cfg, baseURL: apiClient.baseURLString)
        }
        
        do {
            let mapsPayload = try await apiClient.fetchMaps()
            self.availableMaps = mapsPayload.maps
            self.currentMapKey = mapsPayload.currentMap
            
            await loadSegments(mapKey: self.currentMapKey)
            await drainOfflineQueue()
        } catch {
            if self.segments.isEmpty {
                self.segments = StudioSegment.defaultParkCitySegments
                self.sectors = StudioSector.defaultParkCitySectors
            }
            showToast("\(error.localizedDescription)")
        }
    }
    
    public func selectMountain(key: String) async {
        guard key != currentMapKey || currentMapConfig == nil else { return }
        self.currentMapKey = key
        cancelEditing()
        
        do {
            try await apiClient.changeMap(mapKey: key)
            await loadSegments(mapKey: key)
        } catch {
            // Still attempt to load segments directly with query param
            await loadSegments(mapKey: key)
        }
    }
    
    public func loadSegments(mapKey: String) async {
        do {
            let payload = try await apiClient.fetchSegments(map: mapKey)
            self.currentMapKey = payload.resortMap
            self.currentMapConfig = payload.mapConfig
            if !payload.sectors.isEmpty {
                self.sectors = payload.sectors
            }
            if !payload.segments.isEmpty {
                self.segments = payload.segments
            } else if mapKey == "ParkCity" && self.segments.isEmpty {
                self.segments = StudioSegment.defaultParkCitySegments
            }
            
            imageLoader.loadMap(config: payload.mapConfig, baseURL: apiClient.baseURLString)
        } catch {
            if self.segments.isEmpty && mapKey == "ParkCity" {
                self.segments = StudioSegment.defaultParkCitySegments
                self.sectors = StudioSector.defaultParkCitySectors
            }
            showToast("Showing mountain paths (\(self.segments.count) loaded)")
        }
    }
    
    // MARK: - Drawing & Editing Lifecycle
    
    public func startNewLift() {
        cancelEditing()
        self.editorMode = .drawLift
        self.editorType = "lift"
        self.editorName = nextSuggestedName(type: "lift")
        self.editorSector = 0
        self.editorPoints = []
        self.editorMidstations = []
        self.selectedVertexIndex = nil
        self.appendMode = true
        self.undoStack.removeAll()
        self.redoStack.removeAll()
    }
    
    public func startNewSlope(slopeType: String = "downhill", difficulty: String = "blue") {
        cancelEditing()
        self.editorMode = .drawSlope
        self.editorType = slopeType
        self.editorDifficulty = difficulty
        self.editorName = nextSuggestedName(type: slopeType)
        self.editorSector = 0
        self.editorPoints = []
        self.editorMidstations = []
        self.selectedVertexIndex = nil
        self.appendMode = true
        self.undoStack.removeAll()
        self.redoStack.removeAll()
    }
    
    public func startEditingSegment(_ seg: StudioSegment) {
        cancelEditing()
        self.selectedSegment = seg
        self.editorMode = .editSegment(segmentId: seg.id)
        self.editorType = seg.type
        self.editorDifficulty = seg.difficulty ?? "blue"
        self.editorName = seg.name
        self.editorSector = seg.sector
        self.editorPoints = seg.parsedPoints
        self.editorMidstations = seg.parsedMidstations
        self.selectedVertexIndex = nil
        self.appendMode = false
        self.undoStack.removeAll()
        self.redoStack.removeAll()
    }
    
    public func startEditingSectorBoundary(_ sec: StudioSector) {
        cancelEditing()
        self.selectedSector = sec
        self.editorMode = .drawSectorBoundary(sectorId: sec.id)
        self.editorName = sec.name
        self.editorPoints = sec.parsedBoundary
        self.selectedVertexIndex = nil
        self.appendMode = true
        self.showSectorPolygons = true
        self.undoStack.removeAll()
        self.redoStack.removeAll()
    }
    
    public func cancelEditing() {
        self.editorMode = .browse
        self.selectedSegment = nil
        self.selectedSector = nil
        self.editorPoints = []
        self.editorMidstations = []
        self.selectedVertexIndex = nil
        self.appendMode = false
        self.currentSnapTarget = nil
        self.undoStack.removeAll()
        self.redoStack.removeAll()
    }
    
    // MARK: - Point & Vertex Operations
    
    public func addPoint(_ point: StudioPoint) {
        pushUndoState()
        editorPoints.append(point)
        selectedVertexIndex = editorPoints.count - 1
    }
    
    public func updateVertex(at index: Int, to point: StudioPoint) {
        guard index >= 0 && index < editorPoints.count else { return }
        editorPoints[index] = point
    }
    
    public func commitVertexMove(at index: Int, to point: StudioPoint) {
        pushUndoState()
        updateVertex(at: index, to: point)
    }
    
    public func insertVertex(at index: Int, point: StudioPoint) {
        guard index >= 0 && index <= editorPoints.count else { return }
        pushUndoState()
        editorPoints.insert(point, at: index)
        selectedVertexIndex = index
        showToast("Inserted vertex #\(index + 1)")
    }
    
    public func deleteVertex(at index: Int) {
        guard index >= 0 && index < editorPoints.count else { return }
        if case .drawSectorBoundary = editorMode {
            guard editorPoints.count > 3 else {
                showToast("Sector boundary requires at least 3 points.")
                return
            }
        } else {
            guard editorPoints.count > 2 else {
                showToast("A segment requires at least 2 points.")
                return
            }
        }
        pushUndoState()
        editorPoints.remove(at: index)
        selectedVertexIndex = nil
        showToast("Deleted vertex #\(index + 1)")
    }
    
    public func reverseDirection() {
        guard editorPoints.count >= 2 else { return }
        pushUndoState()
        editorPoints.reverse()
        showToast("Reversed Start (S) and End (E) stations")
    }
    
    // MARK: - Undo & Redo
    
    public func pushUndoState() {
        undoStack.append(editorPoints)
        if undoStack.count > 40 { undoStack.removeFirst() }
        redoStack.removeAll()
    }
    
    public func undo() {
        guard !undoStack.isEmpty else { return }
        redoStack.append(editorPoints)
        editorPoints = undoStack.removeLast()
        selectedVertexIndex = nil
    }
    
    public func redo() {
        guard !redoStack.isEmpty else { return }
        undoStack.append(editorPoints)
        editorPoints = redoStack.removeLast()
        selectedVertexIndex = nil
    }
    
    // MARK: - Save Mutations
    
    public func saveCurrentEdit() async {
        switch editorMode {
        case .drawSectorBoundary(let secId):
            await saveSectorBoundary(sectorId: secId)
        case .drawLift, .drawSlope, .editSegment:
            await saveSegment()
        case .browse:
            break
        }
    }
    
    private func saveSegment() async {
        guard editorPoints.count >= 2 else {
            showToast("A segment requires at least 2 points.")
            return
        }
        
        let lengthM = MapCoordinateSpace.calculateLengthMeters(points: editorPoints)
        let isLift = editorType.lowercased() == "lift"
        let segId = (selectedSegment?.id ?? 0)
        
        var segmentToSave = StudioSegment(
            id: segId,
            name: editorName.trimmingCharacters(in: .whitespaces).isEmpty ? nextSuggestedName(type: editorType) : editorName,
            type: editorType,
            difficulty: isLift ? nil : editorDifficulty,
            sector: editorSector,
            lengthMeters: lengthM,
            points: editorPoints.map { $0.asArray },
            midstations: isLift && !editorMidstations.isEmpty ? editorMidstations.map { $0.asArray } : nil,
            active: 1
        )
        
        self.saveStatus = .saving
        
        do {
            let saved = try await apiClient.saveSegment(segmentToSave)
            
            // Update local memory list
            if let idx = segments.firstIndex(where: { $0.id == saved.id }) {
                segments[idx] = saved
            } else if segId != 0, let idx = segments.firstIndex(where: { $0.id == segId }) {
                segments[idx] = saved
            } else {
                segments.append(saved)
            }
            
            self.saveStatus = .saved
            showToast("Saved '\(saved.name)' (\(saved.lengthMeters) m)")
            cancelEditing()
        } catch {
            // Offline queue fallback
            segmentToSave.isLocalDraft = true
            syncQueue.enqueue(PendingOperation(type: .saveSegment, segment: segmentToSave))
            
            if let idx = segments.firstIndex(where: { $0.id == segmentToSave.id && segmentToSave.id != 0 }) {
                segments[idx] = segmentToSave
            } else {
                segments.append(segmentToSave)
            }
            
            self.saveStatus = .offlinePending(syncQueue.count)
            showToast("Network unavailable. Draft saved locally.")
            cancelEditing()
        }
    }
    
    public func deleteSegment(_ seg: StudioSegment) async {
        self.saveStatus = .saving
        do {
            if seg.id > 0 {
                try await apiClient.deleteSegment(id: seg.id)
            }
            segments.removeAll { $0.id == seg.id }
            self.saveStatus = .saved
            showToast("Deleted '\(seg.name)'")
            cancelEditing()
        } catch {
            syncQueue.enqueue(PendingOperation(type: .deleteSegment, segmentId: seg.id))
            segments.removeAll { $0.id == seg.id }
            self.saveStatus = .offlinePending(syncQueue.count)
            showToast("Delete queued locally.")
            cancelEditing()
        }
    }
    
    private func saveSectorBoundary(sectorId: Int) async {
        guard editorPoints.count >= 3 else {
            showToast("A sector boundary polygon requires at least 3 points.")
            return
        }
        
        let rawPoints = editorPoints.map { $0.asArray }
        self.saveStatus = .saving
        
        do {
            try await apiClient.saveSectorBoundary(sectorId: sectorId, boundaryPoints: rawPoints)
            if let idx = sectors.firstIndex(where: { $0.id == sectorId }) {
                sectors[idx].boundaryPoints = rawPoints
            }
            self.saveStatus = .saved
            showToast("Sector boundary saved!")
            cancelEditing()
        } catch {
            syncQueue.enqueue(PendingOperation(type: .saveSectorBoundary, sectorId: sectorId, points: rawPoints))
            if let idx = sectors.firstIndex(where: { $0.id == sectorId }) {
                sectors[idx].boundaryPoints = rawPoints
            }
            self.saveStatus = .offlinePending(syncQueue.count)
            showToast("Boundary saved locally.")
            cancelEditing()
        }
    }
    
    public func createSector(name: String, color: String) async {
        do {
            let created = try await apiClient.createSector(name: name, color: color)
            self.sectors.append(created)
            showToast("Created sector '\(created.name)'")
        } catch {
            showToast("Failed to create sector: \(error.localizedDescription)")
        }
    }
    
    public func toggleSectorVisibility(sectorId: Int) async {
        do {
            let newVis = try await apiClient.toggleSector(sectorId: sectorId)
            if let idx = sectors.firstIndex(where: { $0.id == sectorId }) {
                sectors[idx].visible = newVis
            }
        } catch {
            showToast("Failed to toggle sector visibility.")
        }
    }
    
    public func deleteSector(sectorId: Int) async {
        do {
            try await apiClient.deleteSector(sectorId: sectorId)
            sectors.removeAll { $0.id == sectorId }
            for i in 0..<segments.count {
                if segments[i].sector == sectorId {
                    segments[i].sector = 0
                }
            }
            showToast("Sector deleted.")
        } catch {
            showToast("Failed to delete sector.")
        }
    }
    
    public func autoAssignSectors() async {
        do {
            let count = try await apiClient.autoAssignSectors()
            showToast("Auto-assigned \(count) segments to sectors!")
            await loadSegments(mapKey: currentMapKey)
        } catch {
            showToast("Auto-assign failed: \(error.localizedDescription)")
        }
    }
    
    // MARK: - Offline Queue Drain
    
    public func drainOfflineQueue() async {
        let ops = syncQueue.loadPendingOps()
        guard !ops.isEmpty else { return }
        
        self.saveStatus = .saving
        for op in ops {
            do {
                switch op.type {
                case .saveSegment:
                    if let seg = op.segment {
                        _ = try await apiClient.saveSegment(seg)
                    }
                case .deleteSegment:
                    if let id = op.segmentId, id > 0 {
                        try await apiClient.deleteSegment(id: id)
                    }
                case .saveMidstations:
                    if let id = op.segmentId, let pts = op.points {
                        try await apiClient.saveMidstations(segmentId: id, midstations: pts)
                    }
                case .saveSectorBoundary:
                    if let secId = op.sectorId, let pts = op.points {
                        try await apiClient.saveSectorBoundary(sectorId: secId, boundaryPoints: pts)
                    }
                }
                syncQueue.remove(id: op.id)
            } catch {
                self.saveStatus = .offlinePending(syncQueue.count)
                return
            }
        }
        self.saveStatus = .saved
        showToast("Offline draft changes synchronized!")
        await loadSegments(mapKey: currentMapKey)
    }
    
    // MARK: - Helpers
    
    public func showToast(_ msg: String) {
        self.toastMessage = msg
        Task {
            try? await Task.sleep(nanoseconds: 3_200_000_000)
            if self.toastMessage == msg {
                self.toastMessage = nil
            }
        }
    }
    
    public var liveLengthMeters: Int {
        MapCoordinateSpace.calculateLengthMeters(points: editorPoints)
    }
    
    private func nextSuggestedName(type: String) -> String {
        let isLift = type.lowercased() == "lift"
        let prefix = isLift ? "Lift" : "Slope"
        let count = segments.filter { ($0.type.lowercased() == "lift") == isLift }.count + 1
        return "\(prefix) \(count)"
    }
}
