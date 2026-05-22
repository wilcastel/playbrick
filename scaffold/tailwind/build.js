#!/usr/bin/env node

'use strict';

var path        = require('path');
var fs          = require('fs');
var postcss     = require('postcss');
var atImport    = require('postcss-import');
var tailwindcss = require('@tailwindcss/postcss');
var cssnano     = require('cssnano');
var terser      = require('terser');

var ROOT    = __dirname;
var CSS_IN  = path.join(ROOT, 'dev.css');
var JS_IN   = path.join(ROOT, 'dev.js');

// Load project config (playbrick.config.js) — falls back to dist/
var OUT_DIR = path.join(ROOT, 'dist');
var configPath = path.join(ROOT, 'playbrick.config.js');
if (fs.existsSync(configPath)) {
  var config = require(configPath);
  if (config.outDir) OUT_DIR = path.resolve(ROOT, config.outDir);
}

var CSS_OUT = path.join(OUT_DIR, 'style.min.css');
var JS_OUT  = path.join(OUT_DIR, 'script.min.js');

// ── helpers ───────────────────────────────────────────────────────────────────

function ensureDir(dir) {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
}

function stamp() {
  return new Date().toLocaleTimeString('en', { hour12: false });
}

// ── CSS build ─────────────────────────────────────────────────────────────────

function buildCSS() {
  var src = fs.readFileSync(CSS_IN, 'utf8');

  return postcss([atImport(), tailwindcss(), cssnano({ preset: 'default' })])
    .process(src, { from: CSS_IN, to: CSS_OUT })
    .then(function (result) {
      ensureDir(OUT_DIR);
      fs.writeFileSync(CSS_OUT, result.css);
      var kb = (Buffer.byteLength(result.css, 'utf8') / 1024).toFixed(1);
      console.log('[' + stamp() + '] CSS  →  style.min.css  (' + kb + ' kB)');
    });
}

// ── JS build ──────────────────────────────────────────────────────────────────

function buildJS() {
  var src = fs.readFileSync(JS_IN, 'utf8');

  return terser.minify(src, {
    compress: { drop_console: false },
    format:   { comments: false }
  }).then(function (result) {
    if (result.error) throw result.error;
    ensureDir(OUT_DIR);
    fs.writeFileSync(JS_OUT, result.code);
    var kb = (Buffer.byteLength(result.code, 'utf8') / 1024).toFixed(1);
    console.log('[' + stamp() + '] JS   →  script.min.js  (' + kb + ' kB)');
  });
}

// ── main ──────────────────────────────────────────────────────────────────────

function build() {
  console.log('[' + stamp() + '] Building…');
  return Promise.all([buildCSS(), buildJS()])
    .then(function () { console.log('[' + stamp() + '] Done.'); })
    .catch(function (err) { console.error(err); process.exitCode = 1; });
}

build();

// ── watch mode ────────────────────────────────────────────────────────────────

if (process.argv.includes('--watch')) {
  var chokidar = require('chokidar');

  chokidar
    .watch([
      path.join(ROOT, 'dev.css'),
      path.join(ROOT, 'dev.js'),
      path.join(ROOT, 'bricks/**/*.css'),
      path.join(ROOT, 'bricks/**/*.js')
    ], { ignoreInitial: true })
    .on('change', function (file) {
      console.log('[' + stamp() + '] Changed: ' + path.relative(ROOT, file));
      build();
    });

  console.log('[' + stamp() + '] Watching… (Ctrl+C to stop)');
}
