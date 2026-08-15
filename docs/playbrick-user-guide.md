# PlayBrick User Guide

This guide is for designers and developers using Bricks Builder, PlayBrick, and Tailwind CSS. The quick path gets a Tailwind utility onto a selected element; the later sections explain scope, tokens, breakpoints, generated files, and recovery steps.

## Quick start

1. Open **Settings → PlayBrick** and confirm the **Playground path** points to the scaffold you will use.
2. If the scaffold does not exist, activate PlayBrick so it creates `wp-content/playground/`.
3. Open a terminal in the playground:

   ```bash
   pnpm install
   pnpm run watch
   ```

4. Keep the watcher running and open the page in Bricks Builder.
5. Select an element, open the PlayBrick CSS panel, and enter utilities such as `grid gap-5 md:grid-cols-2` in the utilities field.
6. Save the element in Bricks and confirm the generated CSS and frontend preview.

The watcher exports Bricks sources and rebuilds `dev.built.css` when the relevant source or scaffold files change. Use `pnpm run build` when preparing production assets.

## Choose the styling level

Use the smallest scope that matches the design intent.

| Level | Use it for | Stored in Bricks | Example |
|-------|------------|------------------|---------|
| **Direct Tailwind utilities** | A one-off or element-specific composition | The active element's `_cssClasses`, exposed by the PlayBrick utilities field | `flex items-center gap-3 md:gap-5` |
| **Element-specific Custom CSS** | A selector-scoped rule that is not practical as utilities | The active element's `_cssCustom`, exported against `#brxe-{id}` for normal elements | `@apply grid gap-5;` |
| **Reusable Bricks global class** | A component style shared by multiple elements | A Bricks global class and its `_cssCustom` or visual controls | `.card { @apply rounded-card shadow-card; }` |

Do not create a global class just to hold every element's utilities. Use a global class when the style has a reusable semantic role.

## Using the CSS panel

### Target modes

The panel follows the active Bricks selection:

- **Global class target:** edit the selected class's Custom CSS and inspect its supported visual controls.
- **Active element target:** edit the selected element's `_cssClasses` and element-scoped `_cssCustom` without first creating a global class.

If no global class or valid active element is selected, the panel shows an empty-state message. Switch elements, then use **Refresh** if the builder selection changed but the panel did not update.

### Generated CSS and Custom CSS

- **Generated CSS** is read-only. It is translated from supported Bricks visual settings such as layout, spacing, typography, backgrounds, borders, radius, and shadows.
- **Custom CSS** is editable and is stored in Bricks `_cssCustom` for the current target and active breakpoint.
- The utilities field is direct `_cssClasses` in element mode. In global-class mode it is intended for Tailwind `@apply` content in the class Custom CSS.
- Use **Copy generated** or **Copy declarations** when you need to inspect or reuse the panel output.

### Apply to visual

**Apply to visual** parses supported declarations from Custom CSS and moves them into Bricks visual controls. Unsupported declarations remain in Custom CSS. This keeps visual-control CSS and Custom CSS separate; do not duplicate the same declaration in both places.

### Clear

**Clear custom** clears the current target's breakpoint Custom CSS. For an active element it also clears the direct Tailwind utilities stored in `_cssClasses`. It does not delete a global class or remove unrelated Bricks visual settings. Confirm the target and breakpoint before clearing.

### Breakpoints and direct Bricks controls

The panel reads the active Bricks breakpoint. Base Custom CSS is stored under `_cssCustom`; breakpoint-specific CSS uses `_cssCustom:<breakpoint>`. Direct Bricks visual controls remain native Bricks settings and can override or compete with Custom CSS depending on selector specificity and render order.

## Bricks tokens in Tailwind

PlayBrick exports Bricks Style Manager variables and the color palette into `.playbrick/bricks-theme.css` as Tailwind `@theme inline` tokens. Bricks remains the source of truth: change the value in **Bricks → Settings → Style Manager**, then let the watcher rebuild.

### Naming convention

Use the supported names below when creating Bricks variables:

| Bricks variable name | Tailwind token namespace | Typical utilities |
|----------------------|--------------------------|--------------------|
| `space-m` | `--spacing-m` backed by `--space-m` | `p-m`, `gap-m`, `mt-m` |
| `text-xl` | `--text-xl` | `text-xl` |
| `color-primary` | `--color-primary` | `bg-primary`, `text-primary`, `border-primary` |
| `radius-card` or `card-radius` | `--radius-card` | `rounded-card` |
| `shadow-card` | `--shadow-card` | `shadow-card` |
| `leading-normal` | `--leading-normal` | `leading-normal` |
| `font-title` | `--font-title` | `font-title` |

For example, a Bricks variable named `space-m` can be used as `gap-m`, and a palette color named `Brand Amber` becomes a slug such as `bg-brand-amber`. Duplicate token names are given numeric suffixes rather than silently replacing another value.

### Variable categories do not define the Tailwind namespace

A Bricks variable category is organizational metadata only. Naming a category `Colors` or `eqColors` does not make its variables Tailwind color tokens. For a global variable to be exported into Tailwind's color namespace, use the `color-*` prefix:

```text
color-verde-oscuro
color-verde-menta
color-verde-azulado
```

This produces tokens such as `--color-verde-oscuro` and utilities such as `bg-verde-oscuro`, `text-verde-oscuro`, and `border-verde-oscuro`. Colors created in Bricks' color palette are exported as colors independently of this variable-name convention. Imported variables that were originally organized as colors may still arrive as ordinary global variables, so verify their names after migration.

### Container-token limitation

Arbitrary container names such as `deskbox` are not currently mapped into a Tailwind container namespace. The safe options are:

```css
max-width: var(--deskbox);
```

or an arbitrary Tailwind value where supported:

```html
<div class="max-w-[var(--deskbox)]"></div>
```

Do not edit `.playbrick/bricks-theme.css` to add a missing token; it is generated and will be overwritten.

## Responsive styling

Prefer Tailwind variants in direct utility classes:

```text
text-base md:text-lg max-md:text-sm
```

Use the base utility for the default state and add a variant only where the value changes. Avoid accidentally placing a mobile-only utility in the base element class list.

When using Bricks Custom CSS, PlayBrick treats `_cssCustom:<breakpoint>` as breakpoint-scoped data and wraps it with the live Bricks media-query direction. Unknown breakpoint keys are skipped rather than emitted globally. Do not manually edit generated CSS or add your own wrapper to a generated file.

## Build and production workflow

### Development

1. Confirm **Settings → PlayBrick → Environment** is `dev`.
2. Run `pnpm install` once in the playground.
3. Run `pnpm run watch` while editing Bricks or playground files.
4. Use the Bricks builder and browser preview to check the result.

### Production

1. Stop or leave the watcher aside and run `pnpm run build` from the playground.
2. In **Settings → PlayBrick**, switch **Environment** to `prod`/`Production` as shown by the installation.
3. Confirm the configured enqueue strategy loads the generated minified assets.

The Tailwind workflow writes these generated files under the playground:

| File | Purpose |
|------|---------|
| `.playbrick/bricks-sources.html` | Escaped Bricks classes for Tailwind scanning |
| `.playbrick/bricks-sources.txt` | Raw Bricks classes for Tailwind scanning |
| `.playbrick/bricks-custom.css` | Bricks global and element Custom CSS processed by Tailwind |
| `.playbrick/bricks-theme.css` | Bricks variables and palette as `@theme inline` tokens |
| `.playbrick/tailwind-utilities.json` | Project utility suggestions for the panel |
| `dev.built.css` | Watch-mode CSS output |
| `playbrick.reload.json` | Watch-mode reload timestamp |

These files are generated. Do not hand-edit them.

## Troubleshooting

| Problem | Likely cause | Solution |
|---------|--------------|----------|
| Utility is not generated | The class is not in scanned sources, the watcher is stopped, or Tailwind rejected the utility | Keep `pnpm run watch` running, save the Bricks element, inspect `.playbrick/bricks-sources.txt`, and run `pnpm run build` to see the build error. |
| Variable is not recognized | The Bricks variable name is outside the supported token patterns or the theme export is stale | Rename it using `space-*`, `text-*`, `color-*`, `radius-*`, `shadow-*`, `leading-*`, or `font-*`; rebuild; use `var(--name)` for arbitrary tokens. |
| Mobile style leaks into desktop | A mobile value was placed in the base utility/CSS, or Custom CSS used an unknown breakpoint key | Put the value in a Tailwind variant or the correct `_cssCustom:<breakpoint>` field; never edit generated CSS. |
| Font size and line height collide | Bricks typography controls and Tailwind `text-*` utilities both set related values, or a later selector wins | Inspect Generated CSS and browser Rules; keep the intended font size/line height in one styling level and remove the duplicate declaration. |
| Classes are not visible after switching elements | The panel has not synchronized with the new active element | Select the element again and press **Refresh**. Confirm the classes are in the element's `_cssClasses`, not only in generated CSS. |
| Clear did not remove what you expected | Clear is scoped to the current target and breakpoint; visual controls are not cleared | Select the correct target/breakpoint. Clear Custom removes element utilities in element mode, but direct Bricks visual settings must be changed in Bricks. |
| CSS is overridden by Bricks | A Bricks visual setting, global class, inline style, or more specific selector wins | Use browser DevTools Rules, then remove the competing declaration or choose the correct styling level. Avoid stacking `!important` as a first fix. |
| CSS or tokens are stale | The watcher did not see a source change, an export failed, or browser/WordPress cache is serving old assets | Save the builder change, press panel **Refresh**, restart `pnpm run watch`, run `pnpm run build`, and purge the relevant cache. Check that `dev.built.css` timestamp changes. |
| Component selector context behaves unexpectedly | A component definition and a standalone element do not share the same selector context | Treat component CSS as reusable definition CSS; inspect the rendered selector in DevTools and avoid assuming `#brxe-{id}` is valid for every component instance. |

## Final workflow checklist

- [ ] Bricks Style Manager contains the canonical token value.
- [ ] The styling level matches the scope: direct utility, element CSS, or reusable global class.
- [ ] The active target and breakpoint are visible before editing or clearing.
- [ ] Responsive changes use Tailwind variants or a valid Bricks breakpoint key.
- [ ] `pnpm run watch` is running during development.
- [ ] Generated `.playbrick` files were not edited manually.
- [ ] The generated CSS, Bricks visual preview, and frontend browser result agree.
- [ ] `pnpm run build` and the configured enqueue strategy are verified before production.
