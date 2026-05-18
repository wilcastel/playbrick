# PlayBrick

A local development playground that bridges design (Figma / images / HTML) and **Bricks Builder**.
Everything built here — CSS and JS — gets enqueued in your WordPress child theme and is available globally in the frontend and inside the Bricks editor.

---

## Folder structure

```
playbrick/
│
├── src/                   ← DROP YOUR REFERENCE MATERIALS HERE (read-only)
│   ├── images/            ← design screenshots, mockups
│   └── *.html             ← Figma HTML exports (visual reference only)
│
├── bricks/                ← ACTIVE WORK — components and sections for Bricks
│   ├── base/              ← variables.css · reset.css · global.css
│   ├── components/        ← reusable BEM components  (card-*, button, header…)
│   └── sections/          ← page sections  (hero.css · page-about.css…)
│                             each section also has its preview .html file here
│
├── dev.css                ← CSS entry point: @import chain → enqueue in child theme
├── dev.js                 ← JS entry point: IIFEs inside DOMContentLoaded
├── build.js               ← production build (bundles + minifies → dist/)
└── package.json
```

---

## Quick start

```bash
npm install
```

### Develop
Edit files inside `bricks/`. Open the preview HTML in your browser. No build step needed.

### Build for production
```bash
npm run build       # outputs dist/style.min.css and dist/script.min.js
npm run watch       # same, but rebuilds on every save
```

Set `OUT_DIR` in `build.js` to point to your WordPress uploads folder, e.g.:
```js
var OUT_DIR = path.resolve(ROOT, '../mysite/wp-content/uploads/assets');
```

---

## Workflow: design → Bricks

```
src/  →  bricks/  →  dev.css / dev.js  →  Bricks Builder
(ref)    (build)      (integrate)          (production)
```

### Step 1 — Analyze the source
Open the reference material in `src/`.
- Elements that repeat across pages → **component** (`bricks/components/`)
- Elements unique to one page → **page section** (`bricks/sections/page-{name}.css`)

### Step 2 — Build the component / section

Create the CSS file following BEM. Apply the self-centering pattern on every section:

```css
.my-section {
  width: 100%;               /* required — prevents shrinking in Bricks flex containers */
  max-width: var(--container-max);
  margin: var(--space-section) auto 0;
  padding: var(--space-2xl) var(--space-md-plus);
  box-sizing: border-box;
}
```

### Step 3 — Create an HTML preview

Create `bricks/sections/{name}.html`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../../dev.css" />
</head>
<body>
  <div style="height: 20vh"></div>
  <!-- section HTML here -->
  <script src="../../dev.js"></script>
</body>
</html>
```

Open in the browser and verify visually before moving to Bricks.

### Step 4 — Add to dev.css and dev.js

**dev.css** — add the `@import` in the right order:
```css
/* Components */
@import './bricks/components/card-product.css';

/* Sections */
@import './bricks/sections/hero.css';
```

**dev.js** — add an IIFE:
```js
(function () {
  var section = document.querySelector('.my-section');
  if (!section) return;    // guard — this JS runs on every page
  /* logic here */
})();
```

### Step 5 — Implement in Bricks

1. Replicate the HTML structure using native Bricks elements (Section, Div, Heading…)
2. Assign the CSS class to the **outermost Section** element
3. Set **padding 0** on that Section in the Bricks editor — your CSS already provides it
4. Verify in Bricks preview

---

## CSS patterns

### Full-bleed background (SVG / color spans full viewport)

```css
.my-section {
  position: relative;
}

.my-section-bg {
  position: absolute !important;  /* !important required — Bricks overrides brxe-svg */
  top: 0 !important;
  bottom: 0 !important;
  left: 50% !important;
  right: auto !important;
  transform: translateX(-50%);
  width: 100vw !important;
  height: 100% !important;
  z-index: 0;
  pointer-events: none;
}

.my-section-inner {
  position: relative;
  z-index: 1;
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 0 var(--space-md-plus);
}
```

### Entrance animation (IntersectionObserver)

**CSS:**
```css
.my-element {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity var(--duration-slow) var(--ease-out-expo),
              transform var(--duration-slow) var(--ease-out-expo);
}

.my-section.is-visible .my-element {
  opacity: 1;
  transform: none;
  transition-delay: 0.15s;
}

@media (prefers-reduced-motion: reduce) {
  .my-element { opacity: 1 !important; transform: none !important; transition: none !important; }
}
```

**JS:**
```js
(function () {
  var section = document.querySelector('.my-section');
  if (!section) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    section.classList.add('is-visible');
    return;
  }

  var obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) {
        section.classList.add('is-visible');
        obs.unobserve(section);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

  obs.observe(section);
})();
```

---

## Bricks gotchas

| Symptom | Cause | Fix |
|---------|-------|-----|
| Section shrinks to content width | `max-width` without `width: 100%` | Add `width: 100%` |
| SVG background doesn't fill section | Bricks sets `height: auto` on `brxe-svg` | Use `height: 100% !important` + `top/bottom: 0 !important` |
| White gap after section | CSS class is on an inner Container, not the outer Section | Move class to the Bricks Section element |
| White gap persists | The Bricks Section has its own padding | Set Section padding to 0 in the editor |
| Animation never fires | Observer threshold too high, or element above the fold | Reduce threshold, check rootMargin |
| SVG icon doesn't inherit color | SVG uses `fill="currentColor"` but wrapper has no `color` | Define `color` on the parent container |

---

## WordPress enqueue

In your child theme's enqueue file, point to `dev.css` / `dev.js` for development,
and to the `dist/` files (or your uploads folder) for production.

```php
// In wp-config.php: define('MYBRICK_ENV', 'prod');  (omit for dev)
$is_dev = !defined('MYBRICK_ENV') || MYBRICK_ENV === 'dev';

if ($is_dev) {
  $css_url = get_home_url() . '/playbrick/dev.css';
  $js_url  = get_home_url() . '/playbrick/dev.js';
} else {
  $upload  = wp_upload_dir();
  $css_url = $upload['baseurl'] . '/assets/style.min.css';
  $js_url  = $upload['baseurl'] . '/assets/script.min.js';
}
```
