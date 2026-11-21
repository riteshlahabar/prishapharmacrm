#!/usr/bin/env bash
# Enforces the docs budget: every docs file <= 2 KB, CLAUDE.md <= 100 lines.
# Run before committing:  bash docs/check-docs.sh
set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

MAX_BYTES=2048
MAX_LINES=100
fail=0

while IFS= read -r f; do
    bytes=$(wc -c <"$f")
    if [ "$bytes" -gt "$MAX_BYTES" ]; then
        printf 'FAIL %5d B (max %d)  %s\n' "$bytes" "$MAX_BYTES" "$f"
        fail=1
    fi
done < <(find docs -type f -name '*.md')

if [ -f CLAUDE.md ]; then
    lines=$(wc -l <CLAUDE.md)
    if [ "$lines" -gt "$MAX_LINES" ]; then
        printf 'FAIL %5d lines (max %d)  CLAUDE.md\n' "$lines" "$MAX_LINES"
        fail=1
    fi
fi

if [ "$fail" -eq 0 ]; then
    echo "docs OK: all files <= ${MAX_BYTES} B, CLAUDE.md <= ${MAX_LINES} lines"
else
    echo "Split the oversized file, or rotate old log lines into docs/history/."
fi
exit "$fail"
