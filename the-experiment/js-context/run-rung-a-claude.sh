#!/usr/bin/env bash
# Research 003, rung (a) calibration — Claude Code / claude-opus-4-8, n=4.
# Clean room: a fresh empty dir per run (no project or global CLAUDE.md), skills and slash
# commands disabled. Same setup as Research 002's Claude run. Saves one transcript per run.
# Run:  bash run-rung-a-claude.sh
set -uo pipefail

PROMPT='Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.'

HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="$HERE/runs/a-claude-opus"
mkdir -p "$OUT"
VER="$(claude --version 2>/dev/null || echo 'unknown')"

for i in 1 2 3 4; do
  DIR="$(mktemp -d)"
  echo "=== run $i (cwd $DIR) ==="
  {
    echo "# Research 003 — rung (a) calibration — Claude Code — run $i"
    echo
    echo "- tool: Claude Code $VER"
    echo "- model: claude-opus-4-8"
    echo "- flags: --setting-sources '' --disable-slash-commands"
    echo "- cwd: fresh empty $DIR"
    echo "- prompt (frozen, rung a): $PROMPT"
    echo
    echo "## Output"
    echo
  } > "$OUT/run-$i.md"
  ( cd "$DIR" && claude -p "$PROMPT" --model claude-opus-4-8 \
      --setting-sources '' --disable-slash-commands ) >> "$OUT/run-$i.md" 2>&1
  echo "saved $OUT/run-$i.md"
done

echo "done: 4 runs in $OUT"
