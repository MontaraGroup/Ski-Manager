#!/usr/bin/env python3
"""
Bundle Size & Performance Budget Gate for Ski Manager React Islands.
Verifies production bundles against performance budgets.
"""

import gzip
import json
import os
import sys

BUDGETS = {
    "entry_js": 300 * 1024,      # 300 KB raw uncompressed
    "dialog_js": 140 * 1024,     # 140 KB raw uncompressed
    "page_js": 75 * 1024,        # 75 KB per individual page island chunk
    "islands_css": 600 * 1024,   # 600 KB uncompressed HeroUI + Tailwind bundle
}

def get_gzip_size(data: bytes) -> int:
    return len(gzip.compress(data))

def main() -> int:
    manifest_path = "public/build/.vite/manifest.json"
    if not os.path.exists(manifest_path):
        print(f"[FAIL] Manifest not found at {manifest_path}. Run build first.")
        return 1

    with open(manifest_path, "r", encoding="utf-8") as f:
        manifest = json.load(f)

    violations = []
    print("=" * 72)
    print("SKI MANAGER REACT ISLANDS - ASSET SIZE & BUDGET REPORT")
    print("=" * 72)
    print(f"{'Asset':<42} {'Raw Size':>12} {'Gzip Size':>12}")
    print("-" * 72)

    total_raw = 0
    total_gzip = 0
    checked_files = set()

    for key, entry in manifest.items():
        files_to_check = []
        if "file" in entry:
            files_to_check.append(entry["file"])
        for css in entry.get("css", []):
            files_to_check.append(css)

        for rel_file in files_to_check:
            if rel_file in checked_files:
                continue
            checked_files.add(rel_file)

            path = os.path.join("public/build", rel_file)
            if not os.path.exists(path):
                violations.append(f"Referenced asset missing on disk: {path}")
                continue

            with open(path, "rb") as af:
                content = af.read()

            raw_size = len(content)
            gz_size = get_gzip_size(content)
            total_raw += raw_size
            total_gzip += gz_size

            # Check budget rules
            basename = os.path.basename(rel_file)
            if basename.startswith("islands-") and basename.endswith(".js"):
                if raw_size > BUDGETS["entry_js"]:
                    violations.append(f"Entry runtime bundle '{basename}' exceeds {BUDGETS['entry_js']//1024} KB budget: {raw_size/1024:.2f} KB")
            elif basename.startswith("Dialog-") and basename.endswith(".js"):
                if raw_size > BUDGETS["dialog_js"]:
                    violations.append(f"Dialog bundle '{basename}' exceeds {BUDGETS['dialog_js']//1024} KB budget: {raw_size/1024:.2f} KB")
            elif basename.endswith(".js") and not basename.startswith("islands-") and not basename.startswith("Dialog-"):
                if raw_size > BUDGETS["page_js"]:
                    violations.append(f"Page chunk '{basename}' exceeds {BUDGETS['page_js']//1024} KB budget: {raw_size/1024:.2f} KB")
            elif basename.startswith("islands-") and basename.endswith(".css"):
                if raw_size > BUDGETS["islands_css"]:
                    violations.append(f"Islands CSS bundle '{basename}' exceeds {BUDGETS['islands_css']//1024} KB budget: {raw_size/1024:.2f} KB")

            # Only print top/notable chunks or all if desired
            if raw_size > 15 * 1024 or basename.startswith("islands-"):
                print(f"{basename:<42} {raw_size / 1024:>10.2f} KB {gz_size / 1024:>10.2f} KB")

    print("-" * 72)
    print(f"Total Assets Checked: {len(checked_files)}")
    print(f"Total Bundle Footprint: {total_raw / (1024 * 1024):.2f} MB raw, {total_gzip / (1024 * 1024):.2f} MB gzipped")
    print("=" * 72)

    if violations:
        print("\n[FAIL] Performance budget violations detected:")
        for v in violations:
            print(f"  - {v}")
        return 1

    print("\n[PASS] All assets conform to the production budget.")
    return 0

if __name__ == "__main__":
    sys.exit(main())
