<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'playbrick_enqueue_assets');

function playbrick_enqueue_assets() {
  $settings        = get_option('playbrick_settings', []);
  $env             = $settings['env'] ?? 'dev';
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $out_dir         = $settings['out_dir'] ?? (wp_upload_dir()['basedir'] . '/assets');

  if ($env === 'dev') {
    $playground_url = playbrick_path_to_url($playground_path);
    $css_path       = $playground_path . '/dev.css';
    $js_path        = $playground_path . '/dev.js';
    $css_url        = $playground_url . '/dev.css';
    $js_url         = $playground_url . '/dev.js';
  } else {
    $upload   = wp_upload_dir();
    $css_path = $out_dir . '/style.min.css';
    $js_path  = $out_dir . '/script.min.js';
    $css_url  = playbrick_path_to_url($out_dir) . '/style.min.css';
    $js_url   = playbrick_path_to_url($out_dir) . '/script.min.js';
  }

  wp_enqueue_style(
    'playbrick-styles',
    $css_url,
    [],
    file_exists($css_path) ? filemtime($css_path) : PLAYBRICK_VERSION
  );

  wp_enqueue_script(
    'playbrick-scripts',
    $js_url,
    [],
    file_exists($js_path) ? filemtime($js_path) : PLAYBRICK_VERSION,
    true
  );
}

function playbrick_path_to_url($path) {
  $abspath = untrailingslashit(ABSPATH);
  $home    = untrailingslashit(get_home_url());
  $path    = untrailingslashit($path);
  return str_replace($abspath, $home, $path);
}
