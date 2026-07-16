# Discarded: Gemini plan-mode batch (not counted)

These 8 transcripts are kept as evidence of a methodology correction, **not** as
part of the result.

The first Gemini batch was run with `--approval-mode plan`, chosen as a read-only
analog of Codex's `-s read-only`. That was wrong: Gemini's "plan" mode is a
propose-then-wait workflow, not a plain read-only answer. The consequence:

- **run-1, run-6, run-8** produced **no code** — they stopped at "here is my
  strategy, shall I proceed?"
- run-2, run-4, run-5 produced code with `post_status => publish`.
- run-3, run-7 produced code with `post_status => pending`.

Because 3 of 8 produced no scorable code, and the planning framing may have biased
the rest, this batch is **not comparable** to Claude and Codex (which answered the
prompt directly and always produced code). It was discarded and the study was
re-run in default mode; see [`../form-gemini31pro/`](../form-gemini31pro/) and
[`../../data/results-gemini31pro.md`](../../data/results-gemini31pro.md).

The lesson: verify the run mode with a dry run before a batch, and make sure each
tool's clean-room flags preserve the *task behavior* (produce code), not just the
sandbox property.
