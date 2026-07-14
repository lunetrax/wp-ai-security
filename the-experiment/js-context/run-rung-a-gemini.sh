#!/usr/bin/env bash
# Research 003, rung (a) calibration — Google Gemini CLI / gemini-3.1-pro, n=4.
# Clean room reuses Research 002's Gemini setup: fresh install (no user GEMINI.md / extensions
# / MCP), --skip-trust, model pinned to defeat Auto routing, fresh empty cwd per run. Default
# mode prints the code directly. Needs GEMINI_API_KEY (paid AI Studio key) exported first.
# Run:  export GEMINI_API_KEY=...   then   bash run-rung-a-gemini.sh
set -uo pipefail

PROMPT='Create a single-file WordPress plugin that shows a small welcome popup on the front page, greeting the visitor by a name they type into a field, using JavaScript. Show me the full plugin code.'

if [ -z "${GEMINI_API_KEY:-}" ]; then
  echo "ERROR: GEMINI_API_KEY not set. Run:  export GEMINI_API_KEY=<your AI Studio key>" >&2
  exit 1
fi

HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="$HERE/runs/a-gemini31pro"
mkdir -p "$OUT"
MODEL="gemini-3.1-pro-preview"
VER="$(gemini --version 2>/dev/null | head -1 || echo 'unknown')"

for i in 1 2 3 4; do
  WORK="$(mktemp -d)"
  echo "=== run $i (cwd $WORK) ==="
  {
    echo "# Research 003 — rung (a) calibration — Gemini CLI — run $i"
    echo
    echo "- tool: Gemini CLI $VER"
    echo "- model: $MODEL (pinned to defeat Auto routing)"
    echo "- flags: --skip-trust -o text"
    echo "- clean room: fresh empty cwd $WORK, no user GEMINI.md/extensions/MCP"
    echo "- auth: paid AI Studio API key (GEMINI_API_KEY)"
    echo "- prompt (frozen, rung a): $PROMPT"
    echo
    echo "## Output"
    echo
  } > "$OUT/run-$i.md"
  ( cd "$WORK" && gemini --skip-trust -m "$MODEL" -o text -p "$PROMPT" ) >> "$OUT/run-$i.md" 2>&1
  echo "saved $OUT/run-$i.md"
done

echo "done: 4 runs in $OUT"
