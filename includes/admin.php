<?php
defined('ABSPATH') || exit;

add_action('admin_menu', 'playbrick_admin_menu');
add_action('admin_post_playbrick_save_settings', 'playbrick_save_settings');
add_action('admin_post_playbrick_generate_config', 'playbrick_generate_config');

function playbrick_admin_menu() {
  add_options_page(
    'PlayBrick',
    'PlayBrick',
    'manage_options',
    'playbrick',
    'playbrick_settings_page'
  );
}

function playbrick_settings_page() {
  $settings        = get_option('playbrick_settings', []);
  $env             = $settings['env']             ?? 'dev';
  $scaffold_mode   = playbrick_scaffold_mode( $settings );
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $out_dir         = $settings['out_dir']         ?? (wp_upload_dir()['basedir'] . '/assets');

  $saved   = isset($_GET['saved']);
  $config  = isset($_GET['config']);
  ?>
  <div class="wrap">
    <h1>PlayBrick</h1>

    <?php if ($saved): ?>
      <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>
    <?php if ($config): ?>
      <div class="notice notice-success is-dismissible"><p>playbrick.config.js generated.</p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <?php wp_nonce_field('playbrick_settings'); ?>
      <input type="hidden" name="action" value="playbrick_save_settings" />

      <table class="form-table" role="presentation">

        <tr>
          <th scope="row"><label>Environment</label></th>
          <td>
            <select name="playbrick_env">
              <option value="dev"  <?php selected($env, 'dev');  ?>>Development — serves dev.css / dev.js</option>
              <option value="prod" <?php selected($env, 'prod'); ?>>Production  — serves style.min.css / script.min.js</option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row"><label>Scaffold mode</label></th>
          <td>
            <select name="playbrick_scaffold_mode">
              <option value="classic"  <?php selected($scaffold_mode, 'classic');  ?>>Classic — plain CSS/JS (PostCSS + Terser)</option>
              <option value="tailwind" <?php selected($scaffold_mode, 'tailwind'); ?>>Tailwind CSS v4 — utility-first with CDN in dev</option>
            </select>
            <p class="description" style="color:#d63638;">Changing mode only affects new scaffold generation — existing files are never overwritten.</p>
            <p class="description">Tailwind mode: classes stored in Bricks Builder (database) may not be detected during production builds. Add a safelist in <code>dev.css</code> if needed.</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label>Playground path</label></th>
          <td>
            <input type="text" name="playbrick_playground_path" value="<?php echo esc_attr($playground_path); ?>" class="large-text" />
            <p class="description">Absolute path to the playground folder. Default: <code><?php echo esc_html(ABSPATH . 'playground'); ?></code></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label>Output directory</label></th>
          <td>
            <input type="text" name="playbrick_out_dir" value="<?php echo esc_attr($out_dir); ?>" class="large-text" />
            <p class="description">Where <code>npm run build</code> puts the minified files. Default: <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/assets'); ?></code></p>
          </td>
        </tr>

      </table>

      <?php submit_button('Save settings'); ?>
    </form>

    <hr />
    <h2>Generate playbrick.config.js</h2>
    <p>Writes <code>playbrick.config.js</code> to the playground folder using the output directory above. Run this after changing the output directory.</p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <?php wp_nonce_field('playbrick_generate_config'); ?>
      <input type="hidden" name="action" value="playbrick_generate_config" />
      <?php submit_button('Generate playbrick.config.js', 'secondary'); ?>
    </form>

    <hr />
    <h2>Build commands</h2>
    <p>Run these in your playground folder:</p>
    <pre style="background:#f0f0f1;padding:12px;display:inline-block;">
cd <?php echo esc_html($playground_path); ?>

pnpm install          # first time only
pnpm run build        # generate production assets
pnpm run watch        # auto-rebuild on save</pre>
  </div>
  <?php
}

function playbrick_save_settings() {
  check_admin_referer('playbrick_settings');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  $current       = get_option( 'playbrick_settings', [] );
  $allowed_modes = [ 'classic', 'tailwind' ];
  $new_mode      = sanitize_text_field( $_POST['playbrick_scaffold_mode'] ?? '' );
  $scaffold_mode = in_array( $new_mode, $allowed_modes, true ) ? $new_mode : ( $current['scaffold_mode'] ?? 'classic' );

  update_option('playbrick_settings', [
    'env'             => sanitize_text_field($_POST['playbrick_env'] ?? 'dev'),
    'scaffold_mode'   => $scaffold_mode,
    'playground_path' => untrailingslashit(sanitize_text_field($_POST['playbrick_playground_path'] ?? '')),
    'out_dir'         => untrailingslashit(sanitize_text_field($_POST['playbrick_out_dir'] ?? '')),
    'child_theme'     => untrailingslashit(sanitize_text_field($_POST['playbrick_child_theme'] ?? ($current['child_theme'] ?? ''))),
    'enqueue_file'    => sanitize_text_field($_POST['playbrick_enqueue_file'] ?? ($current['enqueue_file'] ?? '')),
  ]);

  wp_redirect(admin_url('options-general.php?page=playbrick&saved=1'));
  exit;
}

function playbrick_generate_config() {
  check_admin_referer('playbrick_generate_config');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  $settings        = get_option('playbrick_settings', []);
  $playground_path = $settings['playground_path'] ?? (ABSPATH . 'playground');
  $out_dir         = $settings['out_dir']         ?? (wp_upload_dir()['basedir'] . '/assets');
  $mode            = playbrick_scaffold_mode( $settings );

  $content = "module.exports = {\n  outDir: " . json_encode($out_dir) . ",\n  mode: " . json_encode($mode) . "\n};\n";
  file_put_contents($playground_path . '/playbrick.config.js', $content);

  wp_redirect(admin_url('options-general.php?page=playbrick&config=1'));
  exit;
}
