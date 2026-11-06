#!/usr/bin/env python3
"""
Antislop & Design System Rule Checker for Ski Manager React Islands.
Enforces DESIGN.md rules across resources/js:
- No em dashes (—)
- No decorative emojis
- No generic AI marketing buzzwords (AI powered, seamless, ultimate, powerful, Get started, Learn more)
"""

import os
import re
import sys

BANNED_PATTERNS = [
    (re.compile(r"—"), "Em dash (—) is banned. Use en dash (–) or hyphen (-) instead."),
    (re.compile(r"[\U00010000-\U0010ffff]"), "Decorative emoji is banned. Use Font Awesome <Icon /> instead."),
    (re.compile(r"\bAI[- ]powered\b", re.IGNORECASE), "Banned marketing buzzword: 'AI powered'."),
    (re.compile(r"\bseamless(?:ly)?\b", re.IGNORECASE), "Banned marketing buzzword: 'seamless'."),
    (re.compile(r"\bultimate\b", re.IGNORECASE), "Banned marketing buzzword: 'ultimate'."),
    (re.compile(r"\bpowerful\b", re.IGNORECASE), "Banned marketing buzzword: 'powerful'."),
    (re.compile(r"\bGet started\b", re.IGNORECASE), "Generic CTA 'Get started' is banned. Be specific."),
    (re.compile(r"\bLearn more\b", re.IGNORECASE), "Generic CTA 'Learn more' is banned. Be specific."),
]

def main() -> int:
    base_dir = "resources/js"
    if not os.path.isdir(base_dir):
        print(f"[FAIL] Directory '{base_dir}' not found.")
        return 1

    violations = []
    checked_files = 0

    for root, _, files in os.walk(base_dir):
        for f in files:
            if not f.endswith((".ts", ".tsx")):
                continue
            checked_files += 1
            file_path = os.path.join(root, f)
            with open(file_path, "r", encoding="utf-8") as fp:
                for line_num, line in enumerate(fp, 1):
                    for pattern, reason in BANNED_PATTERNS:
                        match = pattern.search(line)
                        if match:
                            rel_path = os.path.relpath(file_path)
                            snippet = line.strip()
                            violations.append(f"{rel_path}:{line_num}: {reason}\n    -> {snippet}")

    print("=" * 72)
    print("SKI MANAGER DESIGN SYSTEM & ANTISLOP QUALITY AUDIT")
    print("=" * 72)
    print(f"Scanned {checked_files} TypeScript/TSX files in {base_dir}/")

    if violations:
        print(f"\n[FAIL] Found {len(violations)} antislop violation(s):")
        for v in violations:
            print(f"  {v}")
        return 1

    print("\n[PASS] 0 antislop violations found. All islands adhere to DESIGN.md rules.")
    return 0

if __name__ == "__main__":
    sys.exit(main())
