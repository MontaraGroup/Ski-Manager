#!/usr/bin/env bash
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$DIR"

echo "=== Building Ski Manager Map Studio Unsigned IPA ==="

BUILD_DIR="build"
PAYLOAD_DIR="$BUILD_DIR/Payload"
APP_DIR="$PAYLOAD_DIR/MapStudio.app"
IPA_OUTPUT="$BUILD_DIR/MapStudio.ipa"

rm -rf "$BUILD_DIR"
mkdir -p "$APP_DIR"

# Detect iPhoneOS SDK
if ! xcrun --sdk iphoneos --show-sdk-path >/dev/null 2>&1; then
    echo "Warning: iPhoneOS SDK not found in current environment."
    echo "Attempting to locate Xcode..."
    LATEST_XCODE=$(find /Applications -maxdepth 1 -name "Xcode*.app" 2>/dev/null | sort -V | tail -n 1)
    if [ -n "$LATEST_XCODE" ]; then
        echo "Found Xcode at $LATEST_XCODE. Run 'sudo xcode-select -s $LATEST_XCODE' if building locally."
    fi
    echo "This script is designed to run in Xcode environments and CI (macos-latest)."
fi

SDK_PATH=$(xcrun --sdk iphoneos --show-sdk-path 2>/dev/null || echo "")
TARGET="arm64-apple-ios17.0"

SWIFT_FILES=$(find Sources/MapStudioApp -name "*.swift")
echo "Source files to compile:"
echo "$SWIFT_FILES"

if [ -n "$SDK_PATH" ]; then
    echo "Compiling native iOS arm64 binary with SDK $SDK_PATH..."
    xcrun -sdk iphoneos swiftc \
        -target "$TARGET" \
        -sdk "$SDK_PATH" \
        -module-name MapStudioApp \
        -O \
        -parse-as-library \
        -emit-executable \
        -o "$APP_DIR/MapStudioApp" \
        $SWIFT_FILES
else
    echo "Compiling with host swiftc..."
    swiftc \
        -module-name MapStudioApp \
        -O \
        -parse-as-library \
        -emit-executable \
        -o "$APP_DIR/MapStudioApp" \
        $SWIFT_FILES
fi

echo "Copying Info.plist and Resources..."
cp Info.plist "$APP_DIR/Info.plist"
if [ -d "Sources/MapStudioApp/Resources" ]; then
    cp -R Sources/MapStudioApp/Resources/* "$APP_DIR/" 2>/dev/null || true
fi

echo "Ad-hoc codesigning app bundle (no developer identity / unsigned)..."
codesign --force --deep --sign - "$APP_DIR"

echo "Packaging into unsigned IPA..."
cd "$BUILD_DIR"
zip -qr "MapStudio.ipa" Payload
cd "$DIR"

ls -lh "$IPA_OUTPUT"
echo "=== Successfully built $IPA_OUTPUT ==="
