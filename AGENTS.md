> You are in **splicewire/laravel-composition-spine** — the composition spine's shared Data/DTO + schema authoring vocabulary: generation attributes, the data-schemas projection strategy, the angle layer, the BeatGrammar projector, and the structured-export I/O DTOs with 1:1 hydration.

A Laravel package providing the satellite-facing DTO/schema surface of the composition engine (the `#[Beat]`/`#[Generate]`/`#[Ground]` attributes, `BeatGrammar`, angle contracts, `StructuredExport`). The heavy composition engine stays private in-app; this package is the surface satellites and the client share without it.

## Vendored family-package conventions

Any repo that vendors another family repo's code (composer `vendor/<vendor>/<pkg>/`, npm
`node_modules/<vendor>/<pkg>/`) checks that vendored repo's own `AGENTS.md` for conventions it
ships with itself before editing through into it.
