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

if ( ! defined( 'PLAYBRICK_VERSION' ) ) define('PLAYBRICK_VERSION', '1.0.0');
if ( ! defined( 'PLAYBRICK_DIR' ) )     define('PLAYBRICK_DIR',     plugin_dir_path(__FILE__));
if ( ! defined( 'PLAYBRICK_URL' ) )     define('PLAYBRICK_URL',     plugin_dir_url(__FILE__));

require_once PLAYBRICK_DIR . 'includes/enqueue-strategy.php';
require_once PLAYBRICK_DIR . 'includes/scaffold.php';
require_once PLAYBRICK_DIR . 'includes/bricks-source.php';
require_once PLAYBRICK_DIR . 'includes/bricks-tokens-seed.php';
require_once PLAYBRICK_DIR . 'includes/enqueue.php';
require_once PLAYBRICK_DIR . 'includes/builder-panel.php';
require_once PLAYBRICK_DIR . 'includes/admin.php';

register_activation_hook(__FILE__, 'playbrick_activate');

function playbrick_activate() {
  // Set defaults only on first activation
  if (!get_option('playbrick_settings')) {
    $upload = wp_upload_dir();
    update_option('playbrick_settings', [
      'env'              => 'dev',
      'scaffold_mode'    => 'classic',
      'playground_path'  => playbrick_default_playground_path(),
      'out_dir'          => $upload['basedir'] . '/assets',
      'enqueue_strategy' => 'plugin',
    ]);
  }
  if ( ! playbrick_create_scaffold() ) {
    wp_die( 'PlayBrick could not create the playground scaffold. Make sure wp-content is writable, then activate the plugin again.' );
  }
}
