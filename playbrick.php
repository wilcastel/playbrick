<?php
/**
 * Plugin Name: PlayBrick
 * Plugin URI:  https://github.com/wilcastel/playbrick
 * Description: Playground bridge between design (Figma/HTML/images) and Bricks Builder. Creates a local dev scaffold and enqueues optimized assets for production.
 * Version:     1.0.0
 * Author:      wilcastel
 * License:     MIT
 */

defined('ABSPATH') || exit;

define('PLAYBRICK_VERSION', '1.0.0');
define('PLAYBRICK_DIR',     plugin_dir_path(__FILE__));
define('PLAYBRICK_URL',     plugin_dir_url(__FILE__));

require_once PLAYBRICK_DIR . 'includes/enqueue-strategy.php';
require_once PLAYBRICK_DIR . 'includes/scaffold.php';
require_once PLAYBRICK_DIR . 'includes/enqueue.php';
require_once PLAYBRICK_DIR . 'includes/admin.php';

register_activation_hook(__FILE__, 'playbrick_activate');

function playbrick_activate() {
  // Set defaults only on first activation
  if (!get_option('playbrick_settings')) {
    $upload = wp_upload_dir();
    update_option('playbrick_settings', [
      'env'              => 'dev',
      'scaffold_mode'    => 'classic',
      'playground_path'  => ABSPATH . 'playground',
      'out_dir'          => $upload['basedir'] . '/assets',
      'enqueue_strategy' => 'plugin',
    ]);
  }
  playbrick_create_scaffold();
}
