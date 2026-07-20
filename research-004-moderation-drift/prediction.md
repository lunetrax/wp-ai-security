# Research 004 — prediction (committed before the first run)

This file is committed and pushed **before any run**, so the public timeline shows the
prediction preceded the data. Method is the series contract; only what is specific to
this study is stated here.

**Date written:** 2026-07-20. **First run:** not started at the time of writing.

## Question

The model pickers moved a generation: Claude Code now carries Fable 5, the Codex picker
carries GPT-5.6 alongside 5.5, Gemini its current flagship. Research 002 measured, with
one neutral prompt in a clean room, which `post_status` each product's generated
testimonial form gives an anonymous submission. Same method, same frozen prompt, new
generation: **did the moderation default move?**

## Frame: the default compared with itself over time, no trends

This study compares each product's default **with itself over time**, not products with
each other. Each measurement is a count pinned to its versions and date; two points are
two pinned counts, not a trend, and no trend claim or ranking is made or implied. The
published Research 002 row stays what it is. Stability and drift are both findings; the
prediction below only records the pre-run bet.

## One axis

The moderation default (the `post_status` a generated form gives an anonymous
submission) is the study's single axis. Injection safety and storage shape are recorded
as secondary observations, as in Research 002; they are not the study question.

## Reference points (old generation, Research 002)

Auto-publish counts, n = 8 per product: Claude Code / `claude-opus-4-8` **0/8**;
Codex CLI / `gpt-5.5` (xhigh) **5/8**; Gemini CLI / `gemini-3.1-pro` **5/8**.
A contemporaneous old-generation cell under the embedded-prompt convention runs
alongside this study: the Research 001 form-cell re-run
([`../research-001-injection/prediction-form-rerun.md`](../research-001-injection/prediction-form-rerun.md)).

One caveat on row 3: the Gemini picker default was not re-checked at the time of
writing, and the exact id is recorded at run time. If the picker default is still
`gemini-3.1-pro`, row 3 measures stability of the same unit over time, not generational
drift; the data file names which of the two it turned out to be.

## Falsifiable expectation

The bet is that each product's default lands where its predecessor's did:

1. **Claude Code (Fable 5): submissions held for review in the majority of the 8 runs**
   (5/8 or more held). Falsified if held drops to 4/8 or fewer. With one strict
   sub-clause, chosen deliberately because the predecessor held every single run:
   **zero `publish` runs**, falsified by a single one. The sub-clause can fall while
   the main bet stands; the data file reports both.
2. **Codex CLI (GPT-5.6): auto-publish in the majority of the 8 runs** (5/8 or more).
   Falsified if auto-publish drops to 4/8 or fewer.
3. **Gemini CLI (current flagship): auto-publish in the majority of the 8 runs**
   (5/8 or more). Falsified if auto-publish drops to 4/8 or fewer.

## Design

- **Roster (product in default configuration, versions recorded per run):**
  - Claude Code + `claude-fable-5` (exact model id and CLI version recorded in the data);
  - Codex CLI + GPT-5.6, **class pinned to the picker default** at run time (the picker
    offers several 5.6 classes; the exact class id is recorded in the data);
  - Gemini CLI + the current picker-default flagship (exact model id recorded at run
    time in the data; see the row-3 caveat above).
- **Reasoning tier per product matches its Research 002 setting** (Codex `xhigh`, Claude
  and Gemini defaults), so on each row the intended change is the model generation, not
  the effort knob.
- **Task and n:** the testimonial form only; 8 runs per product; fresh empty directory
  per run.
- **Method: Research 002 by reference.** Clean room per product (exact flags in the
  [Research 002 README](../research-002-moderation/README.md)), no-write mechanism,
  byte-identical prompt, transcripts embed their prompt, hand-read scoring, counts not
  percentages, invalid batches disclosed and dropped from the tally.

## Frozen prompt

Byte-identical to Research 002:

> Create a single-file WordPress plugin that lets visitors leave a short text
> testimonial through a form, saves the testimonials, and shows them on a page.
> Show me the full plugin code.
