# laravel-composition-spine-data — Context Map (spoke)

The satellite-facing DTO/schema half of the composition engine (the `#[Beat]`/`#[Generate]`/
`#[Ground]` attributes, `BeatGrammar`, angle contracts, `StructuredExport`). Layering,
upstream, and shared seam rules: `splicewire-app/CONTEXT-MAP.md`.

## The seam this file exists to record

Its consumer lives in a **different, in-app** repo and isn't visible from here:
`laravel-composition-engine` (`Rushing\CompositionEngine\`, private) depends on this
package — **never the reverse** (1:1 parity). Why this package exists and what stays in the
engine: app ADR-0044.

## Grounding contracts

Two host-implemented seams keep the grounding vocabulary in the spine while its concrete
resolution stays per-vertical:

- **`GroundingCategoryContract`** — the stable string identity of a grounding category
  (characters, locations, watch, …) so the engine can key providers without enumerating one
  app's taxonomy.
- **`GroundingTokenResolver`** — `resolve(string $token): ?object`, the prose/fact trust
  boundary. A model may *cite* a `groundingToken` but the host resolves it back to a real
  record; a `null` return **drops** the token. It sinks here (not into any one consumer) so
  every projection — editorial Posts, and any future consumer — inherits safe-by-construction
  rehydration. Publishing's editorial `StructuredExport`→ProseMirror projector is the first
  consumer (publishing ADR-0002).

## Steering

`Splicewire\Composition\Steering` is the medium-agnostic half of "how was this piece steered".
One rule: **contributions layer across axes and override within an axis, declaration order
deciding.** It never opens a payload — the mechanism settles *which* contributions survive, the
medium's own payload type settles *how* survivors merge. That split is the whole reason it can
serve more than one medium.

- **`SteeringContribution`** — axis, key, medium-owned payload, read-time `priority`, and an
  optional `scope`: absolute RFC 9535 JSONPath against the *target* document, evaluated at read
  time per node (hence `ResolvedSteering::effective($document, $nodePath)`, not at resolution).
- **`ResolvedSteering::fingerprint()`** — `axis => {key, hash}` plus the medium's opaque dials.
  The hash covers payload, scope and priority, so a rewritten cue under an unchanged key is a
  different fingerprint. Attribution is the point: vary one axis, hold the rest, and a
  regression has one suspect.
- **`JsonPathScope`** — the only place the JSONPath engine is named, so it stays swappable
  (same isolation `JsonPathMutator` gives DataOverlay, ADR-0089).

Its first consumer is `rushing/audiostud`, whose `AngleSet` / `AlbumAngleResolver` are the music
façade over it; the tier was extracted from that cascade, not designed ahead of it. This
package's own `Angles\AngleComposer` is the degenerate case — it buckets by the angle's own
identity, so the override never visibly fires — and has **not** yet been moved onto it.
