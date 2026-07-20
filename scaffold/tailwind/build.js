#!/usr/bin/env node

'use strict';

var path        = require('path');
var fs          = require('fs');
var childProcess = require('child_process');
var postcss     = require('postcss');
var tailwindcss = require('@tailwindcss/postcss');
var cssnano     = require('cssnano');
var terser      = require('terser');

var ROOT    = __dirname;
var CSS_IN  = path.join(ROOT, 'dev.css');
var JS_IN   = path.join(ROOT, 'dev.js');

// Load project config (playbrick.config.js) — falls back to dist/
var OUT_DIR = path.join(ROOT, 'dist');
var WP_LOAD_PATH = path.resolve(ROOT, '../wp-load.php');
var configPath = path.join(ROOT, 'playbrick.config.js');
if (fs.existsSync(configPath)) {
  var config = require(configPath);
  if (config.outDir) OUT_DIR = path.resolve(ROOT, config.outDir);
  if (config.wpLoadPath) WP_LOAD_PATH = path.resolve(ROOT, config.wpLoadPath);
}

var CSS_OUT     = path.join(OUT_DIR, 'style.min.css');
var CSS_DEV_OUT = path.join(ROOT, 'dev.built.css');
var JS_OUT      = path.join(OUT_DIR, 'script.min.js');
var BRICKS_SOURCE_OUT = path.join(ROOT, '.playbrick', 'bricks-sources.html');
var RELOAD_OUT  = path.join(ROOT, 'playbrick.reload.json');

// ── helpers ───────────────────────────────────────────────────────────────────

function ensureDir(dir) {
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
}

function stamp() {
  return new Date().toLocaleTimeString('en', { hour12: false });
}

function readFileIfExists(file) {
  return fs.existsSync(file) ? fs.readFileSync(file, 'utf8') : '';
}

function writeReloadManifest() {
  fs.writeFileSync(RELOAD_OUT, JSON.stringify({ updatedAt: Date.now() }));
}

function exportBricksSource(options) {
  var script = path.join(ROOT, 'export-bricks-source.php');
  if (!fs.existsSync(script)) return;

  var before = readFileIfExists(BRICKS_SOURCE_OUT);
  var silent = options && options.silent;

  try {
    childProcess.execFileSync('php', [script, WP_LOAD_PATH], { stdio: silent ? 'pipe' : 'inherit' });
  } catch (err) {
    console.warn('[' + stamp() + '] Bricks source export skipped.');
    return false;
  }

  return before !== readFileIfExists(BRICKS_SOURCE_OUT);
}

// ── CSS build ─────────────────────────────────────────────────────────────────

function buildCSS() {
  exportBricksSource();

  var src = fs.readFileSync(CSS_IN, 'utf8');

  return postcss([tailwindcss(), cssnano({ preset: 'default' })])
    .process(src, { from: CSS_IN, to: CSS_OUT })
    .then(function (result) {
      ensureDir(OUT_DIR);
      fs.writeFileSync(CSS_OUT, result.css);
      fs.writeFileSync(CSS_DEV_OUT, result.css);
      var kb = (Buffer.byteLength(result.css, 'utf8') / 1024).toFixed(1);
      console.log('[' + stamp() + '] CSS  →  style.min.css + dev.built.css  (' + kb + ' kB)');
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
    .then(function () {
      writeReloadManifest();
      console.log('[' + stamp() + '] Done.');
    })
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
      path.join(ROOT, 'bricks/**/*.js'),
      path.join(ROOT, 'export-bricks-source.php')
    ], { ignoreInitial: true })
    .on('change', function (file) {
      console.log('[' + stamp() + '] Changed: ' + path.relative(ROOT, file));
      build();
    });

  setInterval(function () {
    if (exportBricksSource({ silent: true })) {
      console.log('[' + stamp() + '] Changed: .playbrick/bricks-sources.html');
      build();
    }
  }, 3000);

  console.log('[' + stamp() + '] Watching… (Ctrl+C to stop)');
  console.log('[' + stamp() + '] Bricks source polling enabled every 3s.');
}
