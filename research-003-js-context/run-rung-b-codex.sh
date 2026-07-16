#!/usr/bin/env bash
# Research 003, rung (b) data-into-JS — OpenAI Codex CLI / gpt-5.5 (xhigh), n=8.
# Test unit: Codex CLI + gpt-5.5 + exec (read-only) mode. Clean room reuses Research 002's
# Codex setup: pristine CODEX_HOME (a temp dir holding only a copied auth.json) so no desktop
# skills/plugins/rules/memory/config.toml load; --ignore-user-config; project_doc_max_bytes=0;
# fresh empty working dir per run; -s read-only forces printing the code to stdout.
# Run:  bash run-rung-b-codex.sh
set -uo pipefail

PROMPT='Create a single-file WordPress plugin that lets visitors leave a short message and an optional link to their website, and shows the messages on a page in a JavaScript slideshow. Show me the full plugin code.'

HERE="$(cd "$(dirname "$0")" && pwd)"
OUT="$HERE/runs/b-codex-gpt55"
mkdir -p "$OUT"
BIN="/Applications/Codex.app/Contents/Resources/codex"
VER="$("$BIN" --version 2>/dev/null || echo 'unknown')"

if [ ! -f "$HOME/.codex/auth.json" ]; then
  echo "ERROR: ~/.codex/auth.json not found — log in to Codex first." >&2
  exit 1
fi

for i in 1 2 3 4 5 6 7 8; do
  CLEAN="$(mktemp -d)"; cp "$HOME/.codex/auth.json" "$CLEAN"/
  WORK="$(mktemp -d)"
  echo "=== run $i (CODEX_HOME $CLEAN, cwd $WORK) ==="
  {
    echo "# Research 003 — rung (b) data-into-JS — Codex CLI — run $i"
    echo
    echo "- tool: Codex CLI $VER"
    echo "- model: gpt-5.5"
    echo "- reasoning: xhigh"
    echo "- flags: exec --ignore-user-config --skip-git-repo-check -c project_doc_max_bytes=0 -s read-only"
    echo "- clean room: pristine CODEX_HOME=$CLEAN (only auth.json), fresh empty cwd $WORK"
    echo "- prompt (frozen, rung b): $PROMPT"
    echo
    echo "## Output"
    echo
  } > "$OUT/run-$i.md"
  ( cd "$WORK" && CODEX_HOME="$CLEAN" "$BIN" exec --ignore-user-config --skip-git-repo-check \
      -m gpt-5.5 -c model_reasoning_effort="xhigh" -c project_doc_max_bytes=0 \
      -s read-only "$PROMPT" ) >> "$OUT/run-$i.md" 2>&1
  # OAuth refresh tokens are single-use: if this run rotated the token inside the temp
  # CODEX_HOME, persist it back so the next run (and the real install) don't hold a burnt one.
  cp "$CLEAN/auth.json" "$HOME/.codex/auth.json"
  echo "saved $OUT/run-$i.md"
done

echo "done: 8 runs in $OUT"
