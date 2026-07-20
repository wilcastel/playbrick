<?php
defined('ABSPATH') || exit;

function playbrick_scaffold_mode( $settings = null ) {
  if ( $settings === null ) $settings = get_option( 'playbrick_settings', [] );
  $mode = $settings['scaffold_mode'] ?? 'classic';
  return in_array( $mode, [ 'classic', 'tailwind' ], true ) ? $mode : 'classic';
}

function playbrick_create_scaffold() {
  $settings        = get_option('playbrick_settings', []);
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $mode            = playbrick_scaffold_mode( $settings );
  $scaffold_dir    = PLAYBRICK_DIR . 'scaffold/' . $mode;

  if (!is_dir($scaffold_dir)) return;

  playbrick_copy_dir($scaffold_dir, $playground_path);

  // Write playbrick.config.js if it doesn't exist yet — embed mode so build.js knows which pipeline to use
  $config_file = $playground_path . '/playbrick.config.js';
  if ( !file_exists($config_file) ) {
    $out_dir = $settings['out_dir'] ?? (wp_upload_dir()['basedir'] . '/assets');
    $enqueue_strategy = $settings['enqueue_strategy'] ?? 'plugin';
    $content = "module.exports = {\n"
             . "  outDir:          " . json_encode($out_dir)         . ",\n"
             . "  mode:            " . json_encode($mode)            . ",\n"
             . "  enqueueStrategy: " . json_encode($enqueue_strategy) . ",\n"
             . "  wpLoadPath:      " . json_encode(ABSPATH . 'wp-load.php') . "\n"
             . "};\n";
    file_put_contents($config_file, $content);
  }
}

function playbrick_detect_scaffold_mode( $playground_path ) {
  $package_file = $playground_path . '/package.json';
  $dev_css_file = $playground_path . '/dev.css';
  $build_file   = $playground_path . '/build.js';

  if ( !file_exists($package_file) && !file_exists($dev_css_file) && !file_exists($build_file) ) {
    return 'missing';
  }

  $package = file_exists($package_file) ? file_get_contents($package_file) : '';
  $dev_css = file_exists($dev_css_file) ? file_get_contents($dev_css_file) : '';
  $build   = file_exists($build_file)   ? file_get_contents($build_file)   : '';

  if (
    strpos($package, '@tailwindcss/postcss') !== false ||
    strpos($package, '"tailwindcss"') !== false ||
    strpos($dev_css, '@import "tailwindcss"') !== false ||
    strpos($build, '@tailwindcss/postcss') !== false
  ) {
    return 'tailwind';
  }

  return 'classic';
}

function playbrick_scaffold_status( $settings = null ) {
  if ( $settings === null ) $settings = get_option('playbrick_settings', []);

  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $expected        = playbrick_scaffold_mode($settings);
  $actual          = playbrick_detect_scaffold_mode($playground_path);

  return [
    'expected' => $expected,
    'actual'   => $actual,
    'matches'  => $actual === $expected,
  ];
}

function playbrick_repair_scaffold( $settings = null ) {
  if ( $settings === null ) $settings = get_option('playbrick_settings', []);

  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $mode            = playbrick_scaffold_mode($settings);
  $scaffold_dir    = PLAYBRICK_DIR . 'scaffold/' . $mode;

  if ( !is_dir($scaffold_dir) ) return false;
  if ( !is_dir($playground_path) ) wp_mkdir_p($playground_path);

  $root_files = [
    'build.js',
    'dev.css',
    'dev.js',
    'export-bricks-source.php',
    'package.json',
    'playbrick.config.js.example',
  ];

  foreach ( $root_files as $file ) {
    $src = $scaffold_dir . '/' . $file;
    if ( file_exists($src) ) {
      copy($src, $playground_path . '/' . $file);
    }
  }

  playbrick_copy_dir($scaffold_dir, $playground_path);

  return true;
}

function playbrick_copy_dir($src, $dst) {
  if (!is_dir($dst)) wp_mkdir_p($dst);

  $items = scandir($src);
  foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;

    $src_path = $src . '/' . $item;
    $dst_path = $dst . '/' . $item;

    if (is_dir($src_path)) {
      playbrick_copy_dir($src_path, $dst_path);
    } else {
      // Never overwrite existing files — preserve the user's work
      if (!file_exists($dst_path)) {
        copy($src_path, $dst_path);
      }
    }
  }
}
