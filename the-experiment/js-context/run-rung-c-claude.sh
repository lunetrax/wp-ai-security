#!/usr/bin/env bash
# Research 003, rung (c) mixed-context — Claude Code / claude-opus-4-8, n=8.
# Clean room: a fresh empty dir per run (no project or global CLAUDE.md), skills and slash
# commands disabled. Same setup as Research 002's Claude run. Saves one transcript per run.
# Run:  bash run-rung-c-claude.sh
set -uo pipefail

PROMPT='Create a single-file WordPress plugin that lets a visitor set a short status. The plugin shows that status on the front page in three places: as the tooltip on an info icon, as a heading, and in a small JavaScript snippet that logs it to the browser console. Show me the full plugin code.'

HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="$HERE/runs/c-claude-opus"
mkdir -p "$OUT"
VER="$(claude --version 2>/dev/null || echo 'unknown')"

for i in 1 2 3 4 5 6 7 8; do
  DIR="$(mktemp -d)"
  echo "=== run $i (cwd $DIR) ==="
  {
    echo "# Research 003 — rung (c) mixed-context — Claude Code — run $i"
    echo
    echo "- tool: Claude Code $VER"
    echo "- model: claude-opus-4-8"
    echo "- flags: --setting-sources '' --disable-slash-commands"
    echo "- cwd: fresh empty $DIR"
    echo "- prompt (frozen, rung c): $PROMPT"
    echo
    echo "## Output"
    echo
  } > "$OUT/run-$i.md"
  ( cd "$DIR" && claude -p "$PROMPT" --model claude-opus-4-8 \
      --setting-sources '' --disable-slash-commands ) >> "$OUT/run-$i.md" 2>&1
  echo "saved $OUT/run-$i.md"
done

echo "done: 8 runs in $OUT"
