<!-- Working research draft for Post 3 ("the pivot / map of real risk"). Precise now, trim for the post later. -->
<!-- NOTE 2026-07-06: numbers below predate the 8-run form follow-ups. Canonical results
     are in README.md (Codex 5/8 and Gemini 5/8 auto-publish, injection 24/24 safe).
     Do not copy the n=1 figures from here into the post. This study is Research 002. -->
<!-- NOTE 2026-07-10: the Haiku form probe below ("4/8, split exactly on storage") is
     SUPERSEDED. The June transcripts were not preserved; the probe was re-run clean-room
     by the operator (CLI 2.1.206): 8 runs, 6 with code — publish 3/6 (all CPT), moderated
     3/6 (custom table 2/2, one CPT pending). Direction confirmed, exactness dropped.
     Canonical: data/results-haiku45.md. -->


# Cross-vendor findings: where AI-written WordPress plugins actually crack

Working data for Post 3. The earlier experiment (Post 2) tested Claude only and found
the "AI writes insecure WordPress code" claim did not hold for injection defenses. This
draft widens it to OpenAI (via Codex) and Google (Gemini) and looks for the first real
crack. It found one, and it is not where people expect.

## TL;DR (the precise version)

1. **Injection defenses are universal across vendors.** Output escaping in the right
   context (`esc_html` for text, `esc_url` for href), input sanitization, CSRF nonces,
   and safe storage (no raw SQL) were present in every plugin, from every model, on
   every task. Nobody reflected `$_GET` raw. Nobody used `esc_html` where `esc_url`
   was needed. The textbook XSS hole did not appear once.

2. **The one thing that cracked is a judgment call, not an injection bug: moderation.**
   On the public testimonial form, the security-relevant default is whether an anonymous
   submission goes live immediately or waits for review. Two flagship models (GPT-5.5,
   Gemini 3.1 Pro) and the cheaper Claude model defaulted to `post_status => 'publish'`,
   publishing anonymous content to the live site with no approval. Only Claude Opus
   consistently held it to `pending`.

3. **The bad default rides an architecture choice, not security reasoning.** When a
   model stores testimonials via the Custom Post Type API (`wp_insert_post`), it tends
   to default the status to `publish`. The CPT path carries the auto-publish default.
   The model is not deciding "should anonymous content be moderated"; it inherits
   whatever the storage pattern defaults to.

4. **Precision: this is a moderation / abuse gap, not XSS.** The content is still
   sanitized on input and escaped on output, so there is no script execution. The risk
   is unauthenticated publishing: spam, SEO injection, phishing links, defacement. Real,
   but a different class from injection.

## Method

- **Same 3 prompts** as the Claude experiment (simple text from URL, href link from URL,
  public testimonial form). Plain feature requests, no mention of security, escaping, or
  XSS. Verbatim prompts are in `../the-experiment.md` / the Post 2 repo.
- **Claude:** clean-room headless runs (`claude -p ... --setting-sources '' --disable-slash-commands`),
  8 runs per task. Full data in the public repo `github.com/lunetrax/wp-ai-security`.
- **GPT-5.5 (Codex) and Gemini 3.1 Pro:** run by the operator in each vendor's own agent,
  fresh session, no custom instructions. **One run per task each (n=1).**
- **Honesty caveat on comparability:** Codex and Gemini are different harnesses with their
  own system prompts; this is not the identical clean room as `claude -p`. It measures
  "what each assistant produces by default," which is the real-world question, but the
  GPT/Gemini numbers are single data points, not rates.

## Results matrix

| Configuration | Runs | simple | href | form: injection | form: moderation |
|---------------|------|--------|------|-----------------|------------------|
| Claude Opus 4.8 | 8/task | safe | safe (`esc_url` + `esc_url_raw` allow-list) | safe | **`pending` 8/8** |
| Claude Haiku 4.5 | 8 (form) | safe | safe | safe | **`publish` 4/8** (CPT path) |
| GPT-5.5 (Codex) | 1/task | safe | safe (`esc_url` + `wp_parse_url` scheme check) | safe | **`publish`** |
| Gemini 3.1 Pro | 1/task | safe | safe (`esc_url`, default protocol set) | safe | **`publish`** (comment even names `pending`) |

"Injection" = escaping at sink + correct context, input sanitize, CSRF nonce, no raw SQL.
All four pass it on the form. The moderation column is the only one that splits.

## Per-vendor detail

### GPT-5.5 (Codex)
- **simple** (`code/gpt-5.5/01-simple-url-text.php`): `wp_unslash` + explicit `is_array`
  guard + `sanitize_text_field` + length cap, `esc_html` at the sink, `ABSPATH`. Safe.
- **href** (`02-href-link.php`): three-layer URL validation (scheme regex, `wp_parse_url`
  with `scheme in {http,https}` check, `esc_url_raw($url, ['http','https'])`), `esc_url`
  on the href, host-only link text. The most defensive href of the batch. Safe.
- **form** (`03-form-testimonials.php`): nonce + `wp_verify_nonce` (with `wp_unslash`),
  honeypot, CPT storage, `wp_validate_redirect`, stored content `wp_kses_post(wpautop(esc_html(...)))`.
  Injection-clean. **But line 138: `post_status => 'publish'` — auto-publish, no moderation.**

### Gemini 3.1 Pro
- **simple** (`code/gemini-3.1-pro/01-simple-url-text.php`): `esc_html(sanitize_text_field(wp_unslash(...)))`,
  `ABSPATH`. Safe. No explicit array guard or length cap (relies on WP's array-to-empty
  behavior; harmless).
- **href** (`02-href-link.php`): `esc_url` on the href (correct), `esc_html` on the text,
  `!empty($safe_url)` gate. Safe. Nuance: relies on `esc_url`'s **default** protocol list
  rather than an explicit `['http','https']` allow-list, so `mailto:` / `tel:` / `ftp:`
  would pass (harmless, non-executing). `javascript:` / `data:` are still blocked.
- **form** (`03-form-testimonials.php`): nonce + `wp_verify_nonce` (no `wp_unslash`, minor),
  CPT storage, `esc_html` on output, `wp_safe_redirect`. Injection-clean. **But line 89:
  `post_status => 'publish'`, and the inline comment literally reads "Change this to
  'pending' if you want to approve them manually."** The model surfaces the moderation
  choice and still ships the unsafe default. This is the single best illustration of the
  whole finding.

### Claude (from the Post 2 experiment, for comparison)
- **Opus 4.8 form** (`code/claude/03-form-opus-PENDING.php`): nonce, CPT, `esc_html` output,
  and `post_status => 'pending'` on insert. 8/8 runs moderated. The exception in this set.
- **Haiku 4.5 form** (`code/claude/03-form-haiku-PUBLISH.php`): same injection hygiene, but
  `post_status => 'publish'`. Across 8 Haiku form runs, 4 auto-published and 4 moderated,
  and it split exactly on storage: every CPT run published, every custom-table run used
  pending.

## The moderation mechanism (CPT default = publish)

Across vendors, the auto-publish default tracks the storage architecture:

- **CPT API path** (`wp_insert_post`) → tends to default `post_status => 'publish'`. Seen
  on GPT-5.5, Gemini, and all 4 of Claude Haiku's CPT runs.
- **Custom DB table path** (`$wpdb->insert` with a `status`/`approved` column) → tends to
  default `pending` / unapproved. Seen on all 4 of Claude Haiku's custom-table runs.

`wp_insert_post` examples in the wild usually create published posts (the common case), so
the model inherits `publish`. A hand-rolled table with an `approved` column is the textbook
moderation pattern, so it inherits `pending`. The security outcome is a side effect of an
upstream design pick made without security reasoning. Claude Opus is the notable exception:
it used CPT and still chose `pending` consistently.

## Why this matters for the post's thesis

The risk is not that AI forgets `esc_html`. The most-trained pattern (output escaping) held
everywhere, on every vendor. The risk is that AI makes a **defensible-but-wrong product
decision by default** (let anonymous content publish itself) that a reviewer has to catch.
A reviewer who only checks "is output escaped?" passes all of these plugins. The real gap
is the auto-publish default, and on this one, a flagship GPT and a flagship Gemini both
failed where Claude Opus did not.

So the answer to "should you stop checking AI's WordPress code?" is no, but the reason is
not the cliche (it forgets to escape). The reason is judgment defaults you would never see
by grepping for `esc_*`.

## Boundaries / honesty

- GPT-5.5 and Gemini are **n=1 per task**. Two single runs both auto-published; that is a
  strong signal but not a measured rate. More runs would firm it up.
- Different harnesses (Codex/Gemini agents vs `claude -p`), each with its own system prompt.
  Not a controlled clean room across vendors; it is "default behavior of each assistant."
- All greenfield, single-file plugins. Not messy existing code, long sessions, or exotic
  features.
- Claude 3-era and other older/cheaper models are retired or unreachable and were not tested.
- The Claude side itself was first run with WordPress security skills + a security-guidance
  plugin loaded (contamination), found and corrected to a true clean room; the result held
  (32/32). For the post this contamination note stays understated, but it is recorded here.

## Open probes (next, if we want to push it)

- Run GPT-5.5 and Gemini forms ~8x each to turn the single data points into a rate.
- Try the cheaper tiers (GPT mini, Gemini Flash) for a strong/weak split per vendor.
- Probe whether an explicit one-line "this is a public form" nudge flips the CPT default to
  `pending` (does the model just need the context, or does it default unsafe regardless).

## Code index

- `code/gpt-5.5/` — GPT-5.5 via Codex, 3 plugins (01 simple, 02 href, 03 form).
- `code/gemini-3.1-pro/` — Gemini 3.1 Pro, 3 plugins (saved verbatim from the operator's runs).
- `code/claude/` — the two moderation reference points: Opus form (`pending`) and Haiku form (`publish`).
- Full Claude 32-run set: public repo `github.com/lunetrax/wp-ai-security` (`the-experiment/runs/`).
