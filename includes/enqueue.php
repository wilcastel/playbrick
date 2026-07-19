<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'playbrick_enqueue_assets');

function playbrick_enqueue_assets() {
  $settings = get_option('playbrick_settings', []);

  if ( playbrick_is_child_theme_strategy( $settings ) ) {
    return;
  }

  $env             = $settings['env'] ?? 'dev';
  $mode            = playbrick_scaffold_mode( $settings );
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $out_dir         = $settings['out_dir'] ?? (wp_upload_dir()['basedir'] . '/assets');

  if ($env === 'dev') {
    $playground_url = playbrick_path_to_url($playground_path);
    $js_path        = $playground_path . '/dev.js';
    $js_url         = $playground_url . '/dev.js';
    // Tailwind mode: serve compiled dev.built.css (requires pnpm run watch)
    // Classic mode: serve raw dev.css
    if ( $mode === 'tailwind' ) {
      $css_path = $playground_path . '/dev.built.css';
      $css_url  = $playground_url . '/dev.built.css';
    } else {
      $css_path = $playground_path . '/dev.css';
      $css_url  = $playground_url . '/dev.css';
    }
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
