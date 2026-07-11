# wp-ai-security

Reproducibility material for the Lunetrax series **AI-written WordPress security**
on [blog.lunetrax.com](https://blog.lunetrax.com).

The series asks a plain question: when you ask a current AI assistant to write a
small WordPress plugin, is the code it hands you actually safe? The posts make
claims. This repository is where those claims can be checked. It holds the exact
prompts, the method, the raw per-run scoring, and representative outputs, so you
can re-run any experiment yourself and see what you get.

## Studies

Each study (one question, one design, one dataset) carries a stable `Research NNN`
ID, assigned in run order and never reused. Posts cite these IDs in their footers;
this table is the registry.

| ID | Folder | Run date | Post | The question it pokes at |
|----|--------|----------|------|--------------------------|
| Research 001 | [`the-experiment/`](the-experiment/) | 2026-06-27 | [*I tried to prove AI writes insecure WordPress code*](https://blog.lunetrax.com/i-tried-to-prove-ai-writes-insecure-wordpress-code) | Does the model slip on output escaping when asked the same plugin task again and again, in a harder output context, with a weaker model, or on a complex stateful form? (32/32 safe) |
| Research 002 | [`the-experiment/cross-vendor/`](the-experiment/cross-vendor/) | 2026-06-27/29; probe 2026-07-10 | [*So, can you stop checking AI's WordPress code?*](https://blog.lunetrax.com/so-can-you-stop-checking-ais-wordpress-code) | With the same neutral prompt, which security default cracks first across vendors? Injection held everywhere; the moderation default split (24/24 injection-safe; auto-publish 0/8 · 5/8 · 5/8) |

More rows will be added as the series continues.

## How to read this

- Each experiment folder has its own `README.md` with the full method and the verbatim prompts.
- `data/` holds the raw, per-run scoring tables, read by hand rather than grepped.
- `samples/` holds representative generated plugins, regenerated from a clean slate and re-checked by hand. Outputs vary cosmetically from run to run; the security behaviour is what they illustrate.

## Re-running it yourself

Every prompt is a plain feature request. It never mentions security, escaping,
sanitizing, or XSS. Any defense in the output is the model's own default. The
harness is Claude Code in headless mode:

```
claude -p "<the prompt>" --model <model-id> --setting-sources '' --disable-slash-commands
```

run from a fresh, empty directory. The two flags strip every user skill and
enabled plugin from the model's context, so nothing security-related is primed
into the run. The per-experiment README lists the exact models and prompts.

## License

Public domain, [CC0 1.0](LICENSE). Take it, re-run it, quote it, no permission
needed. A link back to the post is appreciated but not required.
