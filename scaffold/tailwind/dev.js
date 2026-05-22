/* ==========================================================================
   dev.js — PlayBrick entry point
   One IIFE per feature inside DOMContentLoaded.
   Always guard with: if (!el) return  — this JS runs on every page.
   No const/let/arrow functions — keep ES5 for Bricks compatibility.
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {

  // ── Add your features below ───────────────────────────────────────────────
  // One IIFE per feature. Always guard with: if (!el) return
  //
  // Example — scroll progress bar:
  // (function () {
  //   var bar = document.createElement('div');
  //   bar.className = 'scroll-progress';
  //   document.body.prepend(bar);
  //   var target = 0, current = 0, raf = null;
  //   function tick() {
  //     current += (target - current) * 0.12;
  //     bar.style.transform = 'scaleX(' + current + ')';
  //     if (Math.abs(target - current) > 0.001) { raf = requestAnimationFrame(tick); }
  //     else { raf = null; }
  //   }
  //   window.addEventListener('scroll', function () {
  //     var scrolled = window.scrollY;
  //     var total = document.documentElement.scrollHeight - window.innerHeight;
  //     target = total > 0 ? scrolled / total : 0;
  //     if (!raf) raf = requestAnimationFrame(tick);
  //   }, { passive: true });
  // })();

});
