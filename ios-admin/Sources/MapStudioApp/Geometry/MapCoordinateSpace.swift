import Foundation
import CoreGraphics

public struct MapCoordinateSpace {
    public static let mapWidth: Double = 600.0
    public static let mapHeight: Double = 340.0
    
    // MARK: - Affine Projection (Map <-> Canvas)
    
    /// Projects a Map Coordinate [y, x] (lat, lng) to Canvas Viewport CGPoint(x, y).
    /// Leaflet CRS.Simple: y=0 is BOTTOM, y=340 is TOP; x=0 is LEFT, x=600 is RIGHT.
    public static func mapToScreen(point: StudioPoint, in canvasSize: CGSize) -> CGPoint {
        guard canvasSize.width > 0 && canvasSize.height > 0 else { return .zero }
        let screenX = (point.x / mapWidth) * canvasSize.width
        let screenY = ((mapHeight - point.y) / mapHeight) * canvasSize.height
        return CGPoint(x: screenX, y: screenY)
    }
    
    /// Inverses a Canvas Viewport CGPoint(x, y) to Map Coordinate [y, x] (lat, lng).
    public static func screenToMap(screenPoint: CGPoint, in canvasSize: CGSize) -> StudioPoint {
        guard canvasSize.width > 0 && canvasSize.height > 0 else { return StudioPoint(y: 0, x: 0) }
        let rawY = mapHeight - (Double(screenPoint.y) / Double(canvasSize.height)) * mapHeight
        let rawX = (Double(screenPoint.x) / Double(canvasSize.width)) * mapWidth
        
        // Clamp to mountain bounds and round to 4 decimals (matching server saveSegment)
        let clampedY = min(max(rawY, 0.0), mapHeight)
        let clampedX = min(max(rawX, 0.0), mapWidth)
        return StudioPoint(y: roundTo4(clampedY), x: roundTo4(clampedX))
    }
    
    // MARK: - Length Calculation (Exact Web Match)
    
    /// Web mapEngine.ts formula: Euclidean distance in 600x340 space * 15.0, rounded to nearest integer, min 1m.
    public static func calculateLengthMeters(points: [StudioPoint]) -> Int {
        guard points.count >= 2 else { return 0 }
        var totalDist: Double = 0.0
        for i in 1..<points.count {
            let dy = points[i].y - points[i - 1].y
            let dx = points[i].x - points[i - 1].x
            totalDist += sqrt(dy * dy + dx * dx)
        }
        return max(1, Int((totalDist * 15.0).rounded()))
    }
    
    // MARK: - Endpoint Magnetic Snapping (Exact Web Match)
    
    public struct SnapResult {
        public var snappedPoint: StudioPoint
        public var label: String
    }
    
    /// Searches nearby segment endpoints and vertices within 14 screen points.
    /// Endpoints (S, E) receive an 0.85 distance weight multiplier so they attract first.
    public static func findSnapTarget(
        for cursorPoint: StudioPoint,
        in segments: [StudioSegment],
        excludingSegmentId: Int?,
        canvasSize: CGSize,
        snapThresholdPt: CGFloat = 14.0
    ) -> SnapResult? {
        guard canvasSize.width > 0 else { return nil }
        
        let maxDistMap = (Double(snapThresholdPt) / Double(canvasSize.width)) * mapWidth
        
        var bestEffDist = maxDistMap
        var bestPoint: StudioPoint? = nil
        var bestLabel = ""
        
        for seg in segments {
            if let excl = excludingSegmentId, seg.id == excl { continue }
            let pts = seg.parsedPoints
            guard pts.count >= 2 else { continue }
            
            for (idx, pt) in pts.enumerated() {
                let dy = pt.y - cursorPoint.y
                let dx = pt.x - cursorPoint.x
                let dist = sqrt(dy * dy + dx * dx)
                let isEndpoint = (idx == 0 || idx == pts.count - 1)
                let effDist = isEndpoint ? dist * 0.85 : dist
                
                if effDist < bestEffDist {
                    bestEffDist = effDist
                    bestPoint = pt
                    let role = idx == 0 ? " (Start)" : (idx == pts.count - 1 ? " (End)" : "")
                    bestLabel = "\(seg.name)\(role)"
                }
            }
        }
        
        if let snapped = bestPoint {
            return SnapResult(snappedPoint: snapped, label: bestLabel)
        }
        return nil
    }
    
    // MARK: - Ray-Casting Point-in-Polygon (Exact PIP Match)
    
    /// Ray-casting algorithm matching ResortMap::pip ($x = pt[1], $y = pt[0]).
    public static func pointInPolygon(point: StudioPoint, polygon: [StudioPoint]) -> Bool {
        guard polygon.count >= 3 else { return false }
        var inside = false
        let n = polygon.count
        var j = n - 1
        
        for i in 0..<n {
            let yi = polygon[i].y
            let xi = polygon[i].x
            let yj = polygon[j].y
            let xj = polygon[j].x
            
            let dy = yj - yi
            if ((yi > point.y) != (yj > point.y)) && abs(dy) > 1e-9 {
                if point.x < (xj - xi) * (point.y - yi) / dy + xi {
                    inside.toggle()
                }
            }
            j = i
        }
        return inside
    }
    
    private static func roundTo4(_ val: Double) -> Double {
        (val * 10000.0).rounded() / 10000.0
    }
}
