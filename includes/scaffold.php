<?php
defined('ABSPATH') || exit;

function playbrick_create_scaffold() {
  $settings        = get_option('playbrick_settings', []);
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $scaffold_dir    = PLAYBRICK_DIR . 'scaffold';

  if (!is_dir($scaffold_dir)) return;

  playbrick_copy_dir($scaffold_dir, $playground_path);

  // Rename playbrick.config.js.example → playbrick.config.js if config doesn't exist yet
  $config_example = $playground_path . '/playbrick.config.js.example';
  $config_file    = $playground_path . '/playbrick.config.js';
  if (file_exists($config_example) && !file_exists($config_file)) {
    $out_dir = $settings['out_dir'] ?? (wp_upload_dir()['basedir'] . '/assets');
    $content = "module.exports = {\n  outDir: " . json_encode($out_dir) . "\n};\n";
    file_put_contents($config_file, $content);
  }
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
