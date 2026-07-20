/* ==========================================================================
   dev.js — PlayBrick entry point
   One IIFE per feature inside DOMContentLoaded.
   Always guard with: if (!el) return  — this JS runs on every page.
   No const/let/arrow functions — keep ES5 for Bricks compatibility.
   ========================================================================== */

document.addEventListener('DOMContentLoaded', function () {

  // ── Dev CSS live refresh ───────────────────────────────────────────────────
  (function () {
    var scripts = document.getElementsByTagName('script');
    var scriptSrc = '';
    var lastUpdatedAt = null;

    for (var i = scripts.length - 1; i >= 0; i--) {
      if (scripts[i].src && scripts[i].src.indexOf('/dev.js') !== -1) {
        scriptSrc = scripts[i].src;
        break;
      }
    }

    if (!scriptSrc || typeof fetch === 'undefined') return;

    var baseUrl = scriptSrc.replace(/dev\.js(?:\?.*)?$/, '');
    var manifestUrl = baseUrl + 'playbrick.reload.json';

    function refreshCssInDocument(doc, version) {
      var link = doc.getElementById('playbrick-styles-css');
      if (!link) {
        link = doc.querySelector('link[href*="/dev.built.css"]');
      }
      if (!link || !link.href) return false;

      var href = link.href.replace(/([?&])pbv=\d+(&?)/, function (match, prefix, suffix) {
        return suffix ? prefix : '';
      });
      href += (href.indexOf('?') === -1 ? '?' : '&') + 'pbv=' + version;
      link.href = href;

      return true;
    }

    function refreshCssInFrames(doc, version) {
      var frames = doc.getElementsByTagName('iframe');

      for (var i = 0; i < frames.length; i++) {
        try {
          if (frames[i].contentDocument) {
            refreshCssInDocument(frames[i].contentDocument, version);
            refreshCssInFrames(frames[i].contentDocument, version);
          }
        } catch (e) {}
      }
    }

    function refreshCss(version) {
      refreshCssInDocument(document, version);
      refreshCssInFrames(document, version);

      try {
        if (window.parent && window.parent !== window && window.parent.document) {
          refreshCssInDocument(window.parent.document, version);
          refreshCssInFrames(window.parent.document, version);
        }
      } catch (e) {}
    }

    function check() {
      fetch(manifestUrl + '?t=' + Date.now(), { cache: 'no-store' })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
          if (!data || !data.updatedAt) return;
          if (lastUpdatedAt === null) {
            lastUpdatedAt = data.updatedAt;
            return;
          }
          if (data.updatedAt !== lastUpdatedAt) {
            lastUpdatedAt = data.updatedAt;
            refreshCss(data.updatedAt);
          }
        })
        .catch(function () {});
    }

    setInterval(check, 1000);
    check();
  })();

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
