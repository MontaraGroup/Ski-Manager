// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "MapStudioApp",
    platforms: [
        .iOS(.v17),
        .macOS(.v14)
    ],
    products: [
        .executable(
            name: "MapStudioApp",
            targets: ["MapStudioApp"]
        )
    ],
    dependencies: [],
    targets: [
        .executableTarget(
            name: "MapStudioApp",
            dependencies: [],
            path: "Sources/MapStudioApp"
        )
    ]
)
