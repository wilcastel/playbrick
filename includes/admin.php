<?php
defined('ABSPATH') || exit;

add_action('admin_menu', 'playbrick_admin_menu');
add_action('admin_post_playbrick_save_settings', 'playbrick_save_settings');
add_action('admin_post_playbrick_generate_config', 'playbrick_generate_config');
add_action('admin_post_playbrick_generate_child_theme_enqueue', 'playbrick_generate_child_theme_enqueue');
add_action('admin_post_playbrick_repair_scaffold', 'playbrick_repair_scaffold_handler');

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
  $settings         = get_option('playbrick_settings', []);
  $env              = $settings['env']              ?? 'dev';
  $scaffold_mode    = playbrick_scaffold_mode( $settings );
  $playground_path  = $settings['playground_path']   ?? playbrick_default_playground_path();
  $out_dir          = $settings['out_dir']           ?? (wp_upload_dir()['basedir'] . '/assets');
  $enqueue_strategy = playbrick_get_enqueue_strategy( $settings );
  $child_theme      = $settings['child_theme']      ?? '';
  $enqueue_file     = $settings['enqueue_file']     ?? '';
  $scaffold_status  = playbrick_scaffold_status( $settings );

  $saved     = isset($_GET['saved']);
  $config    = isset($_GET['config']);
  $generated = isset($_GET['child_theme_generated']);
  $repaired  = isset($_GET['scaffold_repaired']);
  $error     = $_GET['error'] ?? '';

  $child_theme_fields_class = $enqueue_strategy === 'child_theme' ? '' : 'hidden';

  $legacy_file_warning = '';
  if ($enqueue_strategy === 'plugin' && $child_theme && is_dir($child_theme)) {
    $legacy_file = trailingslashit($child_theme) . 'includes/playbrick-enqueue.php';
    if (file_exists($legacy_file)) {
      $legacy_file_warning = '<div class="notice notice-warning is-dismissible"><p><strong>Legacy child-theme enqueue file detected:</strong> ' . esc_html($legacy_file) . '. Remove it or switch to <strong>Child theme</strong> strategy to avoid double enqueue.</p></div>';
    }
  }
  ?>
  <div class="wrap">
    <h1>PlayBrick</h1>

    <?php if ($saved): ?>
      <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
    <?php endif; ?>
    <?php if ($config): ?>
      <div class="notice notice-success is-dismissible"><p>playbrick.config.js generated.</p></div>
    <?php endif; ?>
    <?php if ($generated): ?>
      <div class="notice notice-success is-dismissible"><p>Child-theme enqueue file generated.</p></div>
    <?php endif; ?>
    <?php if ($repaired): ?>
      <div class="notice notice-success is-dismissible"><p>Playground scaffold repaired for the selected mode.</p></div>
    <?php endif; ?>

    <?php if ($error === 'child_theme_fields'): ?>
      <div class="notice notice-error is-dismissible"><p>Child theme path and Enqueue file are required when the <strong>Child theme</strong> strategy is selected.</p></div>
    <?php elseif ($error === 'child_theme_strategy'): ?>
      <div class="notice notice-error is-dismissible"><p>Switch the enqueue strategy to <strong>Child theme</strong> before generating the enqueue file.</p></div>
    <?php elseif ($error === 'child_theme_path_empty'): ?>
      <div class="notice notice-error is-dismissible"><p>Child theme path is empty.</p></div>
    <?php elseif ($error === 'child_theme_path_invalid'): ?>
      <div class="notice notice-error is-dismissible"><p>Child theme path is not a valid directory.</p></div>
    <?php elseif ($error === 'child_theme_path_forbidden'): ?>
      <div class="notice notice-error is-dismissible"><p>Child theme path must be inside the WordPress installation.</p></div>
    <?php elseif ($error === 'child_theme_file_missing'): ?>
      <div class="notice notice-error is-dismissible"><p>Enqueue file not found inside the child theme.</p></div>
    <?php elseif ($error === 'child_theme_file_forbidden'): ?>
      <div class="notice notice-error is-dismissible"><p>Enqueue file must stay inside the configured child theme.</p></div>
    <?php elseif ($error === 'child_theme_write_failed'): ?>
      <div class="notice notice-error is-dismissible"><p>Could not write the child-theme enqueue files. Check child theme permissions.</p></div>
    <?php elseif ($error === 'scaffold_repair_failed'): ?>
      <div class="notice notice-error is-dismissible"><p>Could not repair the playground scaffold. Check that the selected scaffold mode exists in the plugin.</p></div>
    <?php endif; ?>

    <?php echo $legacy_file_warning; ?>

    <?php if (!$scaffold_status['matches']): ?>
      <div class="notice notice-warning">
        <p><strong>Playground scaffold mismatch:</strong> settings expect <code><?php echo esc_html($scaffold_status['expected']); ?></code>, but the playground looks like <code><?php echo esc_html($scaffold_status['actual']); ?></code>.</p>
        <p>Repairing overwrites only scaffold infrastructure files (<code>build.js</code>, <code>dev.css</code>, <code>dev.js</code>, <code>package.json</code>, and <code>playbrick.config.js.example</code>). Your <code>bricks/</code> components, sections, and <code>playbrick.config.js</code> are preserved.</p>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
          <?php wp_nonce_field('playbrick_repair_scaffold'); ?>
          <input type="hidden" name="action" value="playbrick_repair_scaffold" />
          <?php submit_button('Repair playground scaffold', 'secondary', 'submit', false); ?>
        </form>
      </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <?php wp_nonce_field('playbrick_settings'); ?>
      <input type="hidden" name="action" value="playbrick_save_settings" />

      <table class="form-table" role="presentation">

        <tr>
          <th scope="row"><label>Environment</label></th>
          <td>
            <select name="playbrick_env">
              <option value="dev"  <?php selected($env, 'dev');  ?>>Development — serves playground dev assets</option>
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
            <p class="description" style="color:#d63638;">Changing mode does not rewrite your existing playground automatically. If files do not match the selected mode, PlayBrick will offer a safe repair.</p>
            <p class="description">Tailwind mode: classes stored in Bricks Builder (database) may not be detected during production builds. Add a safelist in <code>dev.css</code> if needed.</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label>Playground path</label></th>
          <td>
            <input type="text" name="playbrick_playground_path" value="<?php echo esc_attr($playground_path); ?>" class="large-text" />
            <p class="description">Absolute path to the playground folder. Default: <code><?php echo esc_html(playbrick_default_playground_path()); ?></code></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label>Output directory</label></th>
          <td>
            <input type="text" name="playbrick_out_dir" value="<?php echo esc_attr($out_dir); ?>" class="large-text" />
            <p class="description">Where <code>pnpm run build</code> puts the minified files. Default: <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/assets'); ?></code></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="playbrick_enqueue_strategy">Enqueue strategy</label></th>
          <td>
            <select id="playbrick_enqueue_strategy" name="playbrick_enqueue_strategy">
              <option value="plugin" <?php selected($enqueue_strategy, 'plugin'); ?>>Plugin — PlayBrick enqueues assets automatically</option>
              <option value="child_theme" <?php selected($enqueue_strategy, 'child_theme'); ?>>Child theme — the active theme enqueues assets</option>
            </select>
            <p class="description">Use <strong>Plugin</strong> for a typical install. Choose <strong>Child theme</strong> only when the theme must control asset loading (for example, <code>snn-brx-child-theme</code>).</p>
          </td>
        </tr>

        <tbody id="playbrick-child-theme-fields" class="<?php echo esc_attr($child_theme_fields_class); ?>">

          <tr>
            <th scope="row"><label>Child theme path</label></th>
            <td>
              <input type="text" name="playbrick_child_theme" value="<?php echo esc_attr($child_theme); ?>" class="large-text" />
              <p class="description">Absolute path to the child theme. Required when <strong>Child theme</strong> is selected. Example: <code><?php echo esc_html(WP_CONTENT_DIR . '/themes/my-child-theme'); ?></code></p>
            </td>
          </tr>

          <tr>
            <th scope="row"><label>Enqueue file</label></th>
            <td>
              <input type="text" name="playbrick_enqueue_file" value="<?php echo esc_attr($enqueue_file); ?>" class="large-text" />
              <p class="description">PHP file inside the child theme where the enqueue hook is appended. Required when <strong>Child theme</strong> is selected. Example: <code>functions.php</code> or <code>includes/features/enqueue-scripts.php</code> for <code>snn-brx-child-theme</code>.</p>
            </td>
          </tr>

        </tbody>

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
    <div id="playbrick-generate-child-theme-section" class="<?php echo esc_attr($child_theme_fields_class); ?>">
      <h2>Generate child-theme enqueue</h2>
      <p>Writes the PlayBrick enqueue hook into the child theme file configured above. The plugin will stop enqueueing assets while the <strong>Child theme</strong> strategy is active.</p>
      <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('playbrick_generate_child_theme_enqueue'); ?>
        <input type="hidden" name="action" value="playbrick_generate_child_theme_enqueue" />
        <?php submit_button('Generate child-theme enqueue', 'secondary'); ?>
      </form>
    </div>

    <hr />
    <h2>Build commands</h2>
    <p>Run these in your playground folder:</p>
    <pre style="background:#f0f0f1;padding:12px;display:inline-block;">
cd <?php echo esc_html($playground_path); ?>

corepack enable      # if pnpm is not available in this shell
pnpm install          # first time only
pnpm run build        # generate production assets
pnpm run watch        # auto-rebuild on save</pre>
  </div>

  <script>
  (function () {
    var strategy = document.getElementById('playbrick_enqueue_strategy');
    var childFields = document.getElementById('playbrick-child-theme-fields');
    var generateSection = document.getElementById('playbrick-generate-child-theme-section');
    function update() {
      var isChild = strategy.value === 'child_theme';
      childFields.classList.toggle('hidden', !isChild);
      generateSection.classList.toggle('hidden', !isChild);
    }
    strategy.addEventListener('change', update);
    update();
  })();
  </script>
  <?php
}

function playbrick_save_settings() {
  check_admin_referer('playbrick_settings');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  $current = get_option( 'playbrick_settings', [] );

  $allowed_modes = [ 'classic', 'tailwind' ];
  $new_mode      = sanitize_text_field( $_POST['playbrick_scaffold_mode'] ?? '' );
  $scaffold_mode = in_array( $new_mode, $allowed_modes, true ) ? $new_mode : ( $current['scaffold_mode'] ?? 'classic' );

  $allowed_strategies = [ 'plugin', 'child_theme' ];
  $new_strategy      = sanitize_text_field( $_POST['playbrick_enqueue_strategy'] ?? '' );
  $enqueue_strategy  = in_array( $new_strategy, $allowed_strategies, true ) ? $new_strategy : ( $current['enqueue_strategy'] ?? 'plugin' );

  $child_theme  = untrailingslashit(sanitize_text_field($_POST['playbrick_child_theme']  ?? ($current['child_theme']  ?? '')));
  $enqueue_file = sanitize_text_field($_POST['playbrick_enqueue_file'] ?? ($current['enqueue_file'] ?? ''));

  if ($enqueue_strategy === 'child_theme' && (empty($child_theme) || empty($enqueue_file))) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_fields'));
    exit;
  }

  update_option('playbrick_settings', [
    'env'              => sanitize_text_field($_POST['playbrick_env'] ?? 'dev'),
    'scaffold_mode'    => $scaffold_mode,
    'playground_path'  => untrailingslashit(sanitize_text_field($_POST['playbrick_playground_path'] ?? '')),
    'out_dir'          => untrailingslashit(sanitize_text_field($_POST['playbrick_out_dir'] ?? '')),
    'enqueue_strategy' => $enqueue_strategy,
    'child_theme'      => $child_theme,
    'enqueue_file'     => $enqueue_file,
  ]);

  wp_redirect(admin_url('options-general.php?page=playbrick&saved=1'));
  exit;
}

function playbrick_generate_config() {
  check_admin_referer('playbrick_generate_config');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  $settings         = get_option('playbrick_settings', []);
  $playground_path  = $settings['playground_path'] ?? playbrick_default_playground_path();
  $out_dir          = $settings['out_dir']         ?? (wp_upload_dir()['basedir'] . '/assets');
  $mode             = playbrick_scaffold_mode( $settings );
  $enqueue_strategy = playbrick_get_enqueue_strategy( $settings );

  $content = "// Local config — git-ignored. Edit values as needed.\n"
           . "module.exports = {\n"
           . "  outDir:          " . json_encode($out_dir)         . ",\n"
           . "  mode:            " . json_encode($mode)            . ",\n"
           . "  enqueueStrategy: " . json_encode($enqueue_strategy) . ",\n"
           . "  wpLoadPath:      " . json_encode(ABSPATH . 'wp-load.php') . ",\n"
           . "};\n";
  if ( !is_dir($playground_path) && !wp_mkdir_p($playground_path) ) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=scaffold_repair_failed'));
    exit;
  }

  if ( file_put_contents($playground_path . '/playbrick.config.js', $content) === false ) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=scaffold_repair_failed'));
    exit;
  }

  wp_redirect(admin_url('options-general.php?page=playbrick&config=1'));
  exit;
}

function playbrick_generate_child_theme_enqueue() {
  check_admin_referer('playbrick_generate_child_theme_enqueue');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  $settings = get_option('playbrick_settings', []);
  $strategy = playbrick_get_enqueue_strategy( $settings );
  if ($strategy !== 'child_theme') {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_strategy'));
    exit;
  }

  $child_theme  = untrailingslashit($settings['child_theme']  ?? '');
  $enqueue_file = sanitize_text_field($settings['enqueue_file'] ?? '');

  if (empty($child_theme)) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_path_empty'));
    exit;
  }

  $real = realpath($child_theme);
  if ($real === false || !is_dir($real)) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_path_invalid'));
    exit;
  }

  $themes_dir = defined('WP_CONTENT_DIR') ? realpath(WP_CONTENT_DIR . '/themes') : realpath(ABSPATH . 'wp-content/themes');
  if ($themes_dir === false) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_path_invalid'));
    exit;
  }

  $theme_norm  = trailingslashit(wp_normalize_path($real));
  $themes_norm = trailingslashit(wp_normalize_path($themes_dir));
  if (strpos($theme_norm, $themes_norm) !== 0) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_path_forbidden'));
    exit;
  }

  $child_theme = $real;
  $snippet     = playbrick_render_child_theme_snippet();

  if (!empty($enqueue_file)) {
    $target = trailingslashit($child_theme) . $enqueue_file;
    if (!file_exists($target)) {
      wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_file_missing'));
      exit;
    }
    $target_real = realpath($target);
    if ($target_real === false || strpos(wp_normalize_path($target_real), trailingslashit(wp_normalize_path($child_theme))) !== 0) {
      wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_file_forbidden'));
      exit;
    }
    if (!playbrick_append_php_snippet($target, $snippet)) {
      wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_write_failed'));
      exit;
    }
  } else {
    $includes_dir = $child_theme . '/includes';
    if (!is_dir($includes_dir) && !wp_mkdir_p($includes_dir)) {
      wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_write_failed'));
      exit;
    }
    $target = $includes_dir . '/playbrick-enqueue.php';

    $marker  = '// PlayBrick child-theme enqueue snippet — do not edit manually.';
    $content = "<?php\n" . $marker . "\n" . $snippet . "\n";
    if (file_put_contents($target, $content, LOCK_EX) === false) {
      wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_write_failed'));
      exit;
    }

    $functions_path = $child_theme . '/functions.php';
    if (file_exists($functions_path)) {
      $require_line = "require_once get_stylesheet_directory() . '/includes/playbrick-enqueue.php';";
      $functions_content = file_get_contents($functions_path);
      if ($functions_content === false) {
        wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_write_failed'));
        exit;
      }
      if (strpos($functions_content, $require_line) === false) {
        $functions_content = rtrim($functions_content);
        if (substr($functions_content, -2) === '?>') {
          $functions_content = substr($functions_content, 0, -2) . "\n" . $require_line . "\n?>";
        } else {
          $functions_content .= "\n" . $require_line . "\n";
        }
        if (file_put_contents($functions_path, $functions_content, LOCK_EX) === false) {
          wp_redirect(admin_url('options-general.php?page=playbrick&error=child_theme_write_failed'));
          exit;
        }
      }
    }
  }

  wp_redirect(admin_url('options-general.php?page=playbrick&child_theme_generated=1'));
  exit;
}

function playbrick_repair_scaffold_handler() {
  check_admin_referer('playbrick_repair_scaffold');
  if (!current_user_can('manage_options')) wp_die('Unauthorized');

  if (!playbrick_repair_scaffold()) {
    wp_redirect(admin_url('options-general.php?page=playbrick&error=scaffold_repair_failed'));
    exit;
  }

  wp_redirect(admin_url('options-general.php?page=playbrick&scaffold_repaired=1'));
  exit;
}
