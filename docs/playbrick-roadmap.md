# PlayBrick Roadmap

PlayBrick connects Bricks Builder's visual controls and design tokens to a Tailwind CSS v4 development workflow. The roadmap keeps Bricks as the visual source of truth while making utility authoring, CSS inspection, and production delivery safer and faster.

## Guiding architecture

- Bricks owns visual values, global variables, color palettes, breakpoint definitions, and reusable global classes.
- PlayBrick exports Bricks content and tokens into `.playbrick` source files consumed by Tailwind.
- Tailwind generates the CSS bundle; PlayBrick does not create a global class for every element.
- The CSS panel is an editing aid. It should expose the correct Bricks target without replacing Bricks' native visual controls.
- Every improvement should preserve a clear path from a builder change to generated source, generated CSS, and the rendered page.

## Current baseline delivered

The current implementation provides:

- A Tailwind scaffold with `pnpm install`, `pnpm run watch`, and `pnpm run build` workflows.
- Bricks class and content scanning into `.playbrick/bricks-sources.html` and `.playbrick/bricks-sources.txt`.
- Bricks global-class Custom CSS and element Custom CSS export into `.playbrick/bricks-custom.css`.
- Bricks Style Manager variables and palette colors bridged into `.playbrick/bricks-theme.css` using Tailwind `@theme inline` tokens.
- A builder CSS panel with two target modes: the active global class and the active Bricks element.
- Direct element utility editing through `_cssClasses`, plus element-scoped `_cssCustom` editing.
- Read-only CSS generated from supported Bricks visual controls and an `Apply to visual` action for supported declarations.
- Built-in CSS and Tailwind utility suggestions, including project utilities from `.playbrick/tailwind-utilities.json`.
- Breakpoint-aware export for `_cssCustom:<breakpoint>` with unresolved breakpoint keys skipped instead of leaking globally.
- Watch-mode source export, Tailwind rebuilds, generated `dev.built.css`, and reload signaling through `playbrick.reload.json`.
- PHPUnit coverage for source extraction, token mapping, breakpoint wrapping, and completion data.

## Prioritized roadmap

### Now: safer authoring and clearer feedback

| Item | User value | Implementation direction | Acceptance signal |
|------|------------|--------------------------|-------------------|
| **P0: Utility autocomplete and class sorting** | Find valid project utilities quickly and keep class strings readable and stable. | Expand completion metadata with namespaces, variants, and token origin; sort utilities by variant, namespace, and utility name without changing semantic order where Tailwind conflict order matters. | Suggestions distinguish built-in and project utilities; repeated edits produce deterministic class ordering; responsive variants remain valid. |
| **P0: Cascade diagnostics** | Explain why a style does not appear instead of asking users to guess. | Compare generated declarations, element custom CSS, global classes, Bricks visual settings, and computed-style evidence where available. Report likely winners and specificity. | A panel diagnostic identifies the winning source for common conflicts such as font size, line height, and Bricks overrides. |
| **P0: Cache and invalidation rules** | Prevent stale CSS after a builder or token change. | Define fingerprints for Bricks sources, theme tokens, Custom CSS, and configuration; invalidate only affected exports and expose the last export reason/time. | A token or class edit rebuilds once; unchanged inputs do not rebuild; the panel can report stale generated data. |
| **P0: Responsive breakpoint safety** | Ensure mobile edits never leak into desktop styles. | Read the live Bricks breakpoint map, validate variant direction, preserve base rules, and show unresolved or conflicting breakpoint keys before export. | Tests cover desktop-first and mobile-first maps; an invalid key cannot emit unscoped CSS; browser verification confirms desktop/mobile isolation. |

### Next: consistent design-system integration

| Item | User value | Implementation direction | Acceptance signal |
|------|------------|--------------------------|-------------------|
| **P1: Design-token synchronization** | Make renamed or changed Bricks tokens predictable in Tailwind. | Add an explicit export manifest containing source variable, generated Tailwind token, fallback, collision suffix, and unsupported reason. Keep synchronization one-way from Bricks unless intentionally configured otherwise. | Users can trace `space-m` to `--spacing-m` and see collisions or unsupported names without inspecting generated files manually. |
| **P1: Component context** | Style component definitions and instances without selector collisions. | Model standalone elements, component definitions, and component instances separately; use context-aware selectors and prevent an instance edit from silently changing the definition. | Component CSS uses the correct selector for its context, and a regression test proves instance and standalone styles do not collide. |
| **P1: Production CSS contract** | Ship only the CSS and JavaScript that production needs. | Document and validate the build artifact contract, output directory, enqueue strategy, source maps/debug policy, and removal of development reload behavior. | `pnpm run build` produces reproducible minified assets, production does not depend on `.playbrick` reload files, and the configured enqueue path loads one copy. |
| **P1: Tests and browser verification** | Catch silent builder regressions before release. | Keep focused PHP tests for extraction and token mapping; add fixture tests for panel state; add browser checks for target switching, Apply to visual, Clear, responsive behavior, and stale-cache recovery. | CI passes unit tests and a repeatable browser smoke flow verifies the panel on a representative Bricks page. |

### Later: smoother daily UX

| Item | User value | Implementation direction | Acceptance signal |
|------|------------|--------------------------|-------------------|
| **P2: Panel UX improvements** | Reduce friction while switching elements and editing CSS. | Preserve target state reliably, show the active element/class and breakpoint prominently, add focused status messages, improve keyboard navigation, and make Clear behavior explicit. | A user can switch targets, return to an element, understand its classes, and clear the intended scope without refreshing. |
| **P2: WindPress-inspired authoring affordances** | Offer a polished utility-first authoring experience. | Borrow product ideas such as fast utility entry, contextual suggestions, and visible project tokens, but keep PlayBrick's export pipeline and Bricks-native storage model. | The experience is faster to learn without binding utilities to generated Bricks IDs or introducing a second styling authority. |
| **P2: Explainable source preview** | Let users understand what Tailwind is scanning and why a utility exists. | Link panel suggestions to their source category and show a compact export summary instead of requiring users to open generated files. | A user can identify whether a utility came from core completions, Bricks content, a token, or Custom CSS. |

## Guardrails and non-goals

- Do not create a global class for every element. Use direct `_cssClasses` for local utility composition and global classes for genuinely reusable component styles.
- Do not bind Tailwind utilities to Bricks IDs as a replacement for semantic classes. Element IDs are selector context for scoped CSS, not design-system names.
- Preserve Bricks as the visual token source of truth unless a future feature intentionally changes that contract and documents migration behavior.
- Do not edit generated files under the WordPress Bricks CSS output directory or `.playbrick` by hand.
- Do not duplicate visual-control CSS into Custom CSS merely to make the panel appear complete.
- Do not copy WindPress architecture. PlayBrick should retain its small export boundary, explicit generated files, and Bricks-native persistence.
- Do not promise arbitrary Tailwind token namespaces until their mapping and collision behavior are implemented and tested.

## Release decision checklist

- [ ] The feature has a user-visible outcome and a measurable acceptance signal.
- [ ] Bricks source-of-truth and selector scope are explicit.
- [ ] Base and responsive behavior are covered by tests.
- [ ] Generated artifacts and invalidation behavior are documented.
- [ ] A browser smoke check confirms the builder and frontend result.
