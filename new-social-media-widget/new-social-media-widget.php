<?php
/*
Plugin Name: New Social Media Widget
Plugin URI: http://awplife.com/
Description: The Social Media Widget is a simple sidebar widget that allows users to input their social media website profile URLs and other subscription options to show an icon on the sidebar to that social media site and more that open up in a separate browser window.
Version: 1.4.0
Author: A WP Life
Author URI: https://awplife.com/
Text Domain: new-social-media-widget
Domain Path: /languages
License: GPLv2 or later

*/
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Load SVG icons
require_once plugin_dir_path(__FILE__) . 'includes/nsmw-icons.php';

if (!class_exists('NSMW_New_Social_Media_Free')) {
	class NSMW_New_Social_Media_Free extends WP_Widget
	{

		/**
		 * Sets up the widgets name
		 */
		public function __construct()
		{
			$sm_widget_ops = array(
				'classname' => 'new_social_media_widget',
				'description' => esc_html__('Display Social Media Profiles', 'new-social-media-widget'),
				'show_instance_in_rest' => false,
			);
			parent::__construct('new_social_media_widget', esc_html__('Social Media Widget', 'new-social-media-widget'), $sm_widget_ops);

			add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		}

		public function enqueue_admin_scripts()
		{
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_style('nsmw-admin-widget', plugin_dir_url(__FILE__) . 'css/admin-widget.css', array(), time());
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('nsmw-color-picker-js',  plugin_dir_url(__FILE__) . 'js/nsmw-color-picker.js', array('jquery', 'wp-color-picker', 'jquery-ui-sortable'), time(), true);
		}

		/**
		 * Widget Output
		 */
		public function widget($args, $instance)
		{
			wp_enqueue_style('nsmw-grid-css', plugin_dir_url(__FILE__) . 'css/nsmw-grid.css', array(), '3.5.2');
			wp_enqueue_style('nsmw-hover-min-css', plugin_dir_url(__FILE__) . 'css/hover-min.css', array(), '3.5.2');

			//load save settings — sanitize all values on output

			// Widget ID (integer only)
			$nsmw_widget_id = ! empty($instance['nsmw_widget_id']) ? absint($instance['nsmw_widget_id']) : '';

			// Widget title
			$title = ! empty($instance['title']) ? sanitize_text_field($instance['title']) : '';

			// Social URLs — sanitize as URLs
			$facebook = ! empty($instance['facebook']) ? esc_url($instance['facebook']) : '';
			$twitter = ! empty($instance['x-twitter']) ? esc_url($instance['x-twitter']) : '';
			$instagram = ! empty($instance['instagram']) ? esc_url($instance['instagram']) : '';
			$youtube = ! empty($instance['youtube']) ? esc_url($instance['youtube']) : '';
			$pinterest = ! empty($instance['pinterest']) ? esc_url($instance['pinterest']) : '';
			$linkedin = ! empty($instance['linkedin']) ? esc_url($instance['linkedin']) : '';
			$tumblr = ! empty($instance['tumblr']) ? esc_url($instance['tumblr']) : '';
			$flickr = ! empty($instance['flickr']) ? esc_url($instance['flickr']) : '';
			$vimeo = ! empty($instance['vimeo']) ? esc_url($instance['vimeo']) : '';
			$rss = ! empty($instance['rss']) ? esc_url($instance['rss']) : '';
			$whatsapp = ! empty($instance['whatsapp']) ? esc_url($instance['whatsapp']) : '';
			$envelope = ! empty($instance['envelope']) ? esc_url($instance['envelope']) : '';

			// Display settings — sanitize per type
			$columns = ! empty($instance['columns']) ? sanitize_text_field($instance['columns']) : '';
			$icon_size = ! empty($instance['icon_size']) ? sanitize_text_field($instance['icon_size']) : '';
			$padding = ! empty($instance['padding']) ? intval($instance['padding']) : 0;
			$background = ! empty($instance['background']) ? sanitize_text_field($instance['background']) : '';
			$margin_top = ! empty($instance['margin_top']) ? intval($instance['margin_top']) : 0;
			$margin_bottom = ! empty($instance['margin_bottom']) ? intval($instance['margin_bottom']) : 0;

			// Colors — sanitize to allow hex or rgb/rgba values from modern color pickers
			$div_bg_color = ! empty($instance['div_bg_color']) ? sanitize_text_field($instance['div_bg_color']) : '#003b5b';
			$icon_color = ! empty($instance['icon_color']) ? sanitize_text_field($instance['icon_color']) : '#ffffff';

			// Effects — sanitize
			$allowed_effect_types = array('none', 'transform', 'hover');
			$effect_type = ! empty($instance['effect_type']) && in_array($instance['effect_type'], $allowed_effect_types) ? $instance['effect_type'] : 'none';
			$transition = ! empty($instance['transition']) ? floatval($instance['transition']) : 0;
			$transform = ! empty($instance['transform']) ? sanitize_text_field($instance['transform']) : '';
			$hover_effects = ! empty($instance['hover_effects']) ? sanitize_text_field($instance['hover_effects']) : '';
			$css = ! empty($instance['css']) ? wp_strip_all_tags($instance['css']) : '';
			$allowed_targets = array('_new', '_self');
			$url_target = ! empty($instance['url_target']) && in_array($instance['url_target'], $allowed_targets) ? $instance['url_target'] : '_new';

			$custom_css = "";
			if (!empty($css)) {
				$custom_css .= wp_strip_all_tags($css) . " ";
			}
			$custom_css .= "
				.nsmw-div-" . esc_attr($nsmw_widget_id) . " {
					padding: " . esc_attr($padding) . "px !important;
				}

				.nsmw-div-" . esc_attr($nsmw_widget_id) . " .border-box {
					background-color: " . esc_attr($div_bg_color) . " !important;
					color: " . esc_attr($icon_color) . ";
					list-style-type: none;
					display: flex;
					align-items: center;
					justify-content: center;
					min-height: 80px;
					border: 1px solid rgba(167, 146, 129, 0.4);
					cursor: pointer;
					transition: ease 0.3s;
					width: 100%;
					height: 100%;
					transition-duration: .8s;
				}

				.nsmw-div-" . esc_attr($nsmw_widget_id) . " .border-box:hover {
					z-index: 999 !important;
					position: relative;
				}
			";

			// Add a class for backend preview to prevent media queries from collapsing columns
			$is_admin_preview = is_admin() || (function_exists('wp_is_json_request') && wp_is_json_request()) || (defined('REST_REQUEST') && REST_REQUEST);

			if ($is_admin_preview) {
				echo '<style id="nsmw-inline-preview-' . esc_attr($nsmw_widget_id) . '">' . $custom_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				wp_add_inline_style('nsmw-grid-css', $custom_css);
			}

			echo wp_kses_post($args['before_widget']);
			// widget title
			if (! empty($instance['title'])) {
				echo wp_kses_post($args['before_title']) . esc_html(apply_filters('widget_title', $instance['title'])) . wp_kses_post($args['after_title']);
			}

			if ($effect_type != "hover") {
				$hover_effects = "";
			}
			$pos = get_option("social_media_icon_pos");

			// Allowed social media keys for validation
			$allowed_social_keys = array(
				'facebook',
				'x-twitter',
				'instagram',
				'youtube',
				'pinterest',
				'linkedin',
				'tumblr',
				'flickr',
				'vimeo',
				'rss',
				'whatsapp',
				'envelope'
			);

			// Set rel attribute for links opening in new tab
			$rel_attr = ($url_target === '_new') ? ' rel="noopener noreferrer"' : '';

			// Migration / Backward Compatibility:
			// If profiles array doesn't exist, build it from legacy individual keys
			$profiles = !empty($instance['profiles']) && is_array($instance['profiles']) ? $instance['profiles'] : array();

			if (empty($profiles)) {
				foreach ($allowed_social_keys as $key) {
					// Fallback check for old keys with trailing spaces
					$val = !empty($instance[$key]) ? $instance[$key] : (!empty($instance[$key . ' ']) ? $instance[$key . ' '] : '');
					if (!empty($val)) {
						$profiles[] = array(
							'network' => $key,
							'url' => $val
						);
					}
				}
			}

			// Add a class for backend preview to prevent media queries from collapsing columns
			$is_admin_preview = is_admin() || (function_exists('wp_is_json_request') && wp_is_json_request()) || (defined('REST_REQUEST') && REST_REQUEST);
			$admin_preview_class = $is_admin_preview ? ' nsmw-admin-preview' : '';
?>
			<div class="social-wrap text-center<?php echo esc_attr($admin_preview_class); ?>">

				<?php
				$icon_index = 0;
				foreach ($profiles as $profile) {
					if ($icon_index >= 15) {
						break; // Limit to 15 icons as before
					}

					$safe_key = sanitize_key($profile['network']);
					$instance_res = $profile['url'];

					if ($instance_res && in_array($safe_key, $allowed_social_keys)) {
						$icon_index++;
						$aria_label = ucwords(str_replace(array('-', '_'), ' ', $safe_key));
						$nsmw_rendered_icon = nsmw_render_svg_icon($safe_key, $icon_size);
				?>
						<div id="nsmw-div-<?php echo esc_attr($nsmw_widget_id); ?>-<?php echo esc_attr($safe_key); ?>" class="nsmw-div-<?php echo esc_attr($nsmw_widget_id); ?> <?php echo esc_attr($columns); ?>">
							<a href="<?php echo esc_url($instance_res); ?>" target="<?php echo esc_attr($url_target); ?>" <?php echo wp_kses_post($rel_attr); ?> class="social-media-link-<?php echo esc_attr($nsmw_widget_id); ?>" aria-label="<?php echo esc_attr($aria_label); ?>">
								<div class="border-box <?php echo esc_attr($hover_effects); ?>">
									<?php echo wp_kses($nsmw_rendered_icon, array('svg' => array('xmlns' => array(), 'viewbox' => array(), 'class' => array(), 'style' => array()), 'path' => array('d' => array()))); ?>
								</div>
							</a>
						</div>
				<?php
					}
				} ?>
			</div><!-- .social-wrap -->

		<?php
			echo wp_kses_post($args['after_widget']);
		}

		/**
		 * Widget Administrator From
		 */
		public function form($instance)
		{
			// outputs the options form on admin
			$nsmw_widget_id = ! empty($instance['nsmw_widget_id']) ? $instance['nsmw_widget_id'] : wp_rand(100, 10000);
			$title = ! empty($instance['title']) ? $instance['title'] : '';

			// Allowed social media keys for the dropdown
			$allowed_social_keys = array(
				'facebook',
				'x-twitter',
				'instagram',
				'youtube',
				'pinterest',
				'linkedin',
				'tumblr',
				'flickr',
				'vimeo',
				'rss',
				'whatsapp',
				'envelope'
			);

			// Migrate old values if profiles is empty
			$profiles = !empty($instance['profiles']) && is_array($instance['profiles']) ? $instance['profiles'] : array();
			if (empty($profiles)) {
				foreach ($allowed_social_keys as $key) {
					$val = !empty($instance[$key]) ? $instance[$key] : (!empty($instance[$key . ' ']) ? $instance[$key . ' '] : '');
					if (!empty($val)) {
						$profiles[] = array(
							'network' => $key,
							'url' => $val
						);
					}
				}
			}

			//widget display setting
			$style_type = ! empty($instance['style_type']) ? $instance['style_type'] : 'default';
			$columns = ! empty($instance['columns']) ? $instance['columns'] : 'col-md-3';
			$icon_size = ! empty($instance['icon_size']) ? $instance['icon_size'] : '2';
			$padding = ! empty($instance['padding']) ? $instance['padding'] : '0';
			$background = ! empty($instance['background']) ? $instance['background'] : '';

			$div_bg_color = ! empty($instance['div_bg_color']) ? $instance['div_bg_color'] : '#003b5b';
			$icon_color = ! empty($instance['icon_color']) ? $instance['icon_color'] : '#ffffff';
			$effect_type = ! empty($instance['effect_type']) ? $instance['effect_type'] : 'none';
			$transition = ! empty($instance['transition']) ? $instance['transition'] : '0.8';
			$transform = ! empty($instance['transform']) ? $instance['transform'] : '';
			$hover_effects = ! empty($instance['hover_effects']) ? $instance['hover_effects'] : '';
			$css = ! empty($instance['css']) ? $instance['css'] : '';
			$url_target = ! empty($instance['url_target']) ? $instance['url_target'] : '_new';
		?>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('nsmw_widget_id')); ?>" name="<?php echo esc_attr($this->get_field_name('nsmw_widget_id')); ?>" type="hidden" value="<?php echo esc_attr($nsmw_widget_id); ?>">

			<div class="nsmw-admin-wrapper">
				<p>
					<label><?php esc_html_e('Widget Title :', 'new-social-media-widget'); ?></label>
					<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
				</p>
				<button type="button" class="nsmw-section-toggle"><?php esc_html_e('Social Media Profile Settings', 'new-social-media-widget'); ?></button>

				<div class="nsmw-section-content" style="display:none;">
					<div class="nsmw-social-media-urls-sortable nsmw-social-links-grid nsmw-repeater-list" id="social-media-urls-<?php echo esc_attr($nsmw_widget_id); ?>">
						<?php
						$index = 0;
						foreach ($profiles as $profile) {
							$net = esc_attr($profile['network']);
							$url = esc_attr($profile['url']);
						?>
							<div class="nsmw-repeater-item" style="display:flex; gap:10px; align-items:center; margin-bottom:10px; background:#f9f9f9; padding:8px; border:1px solid #ddd; cursor:move;">
								<span class="dashicons dashicons-menu" style="cursor: move; color: #888;"></span>
								<select name="<?php echo esc_attr($this->get_field_name('profiles')); ?>[<?php echo esc_attr($index); ?>][network]" style="width: 130px; margin: 0;">
									<?php foreach ($allowed_social_keys as $key) { ?>
										<option value="<?php echo esc_attr($key); ?>" <?php selected($net, $key); ?>><?php echo esc_html(ucwords(str_replace(array('-', '_'), ' ', $key))); ?></option>
									<?php } ?>
								</select>
								<input class="widefat" name="<?php echo esc_attr($this->get_field_name('profiles')); ?>[<?php echo esc_attr($index); ?>][url]" type="url" value="<?php echo esc_url($url); ?>" placeholder="https://..." style="flex-grow:1; margin: 0;">
								<button type="button" class="button button-secondary nsmw-remove-repeater-item" title="Remove">&times;</button>
							</div>
						<?php
							$index++;
						}
						?>
					</div>

					<!-- Hidden Template for new rows -->
					<script type="text/template" class="nsmw-repeater-template">
						<div class="nsmw-repeater-item" style="display:flex; gap:10px; align-items:center; margin-bottom:10px; background:#f9f9f9; padding:8px; border:1px solid #ddd; cursor:move;">
							<span class="dashicons dashicons-menu" style="cursor: move; color: #888;"></span>
							<select name="<?php echo esc_attr($this->get_field_name('profiles')); ?>[__INDEX__][network]" style="width: 130px; margin: 0;">
								<?php foreach ($allowed_social_keys as $key) { ?>
									<option value="<?php echo esc_attr($key); ?>"><?php echo esc_html(ucwords(str_replace(array('-', '_'), ' ', $key))); ?></option>
								<?php } ?>
							</select>
							<input class="widefat" name="<?php echo esc_attr($this->get_field_name('profiles')); ?>[__INDEX__][url]" type="url" value="" placeholder="https://..." style="flex-grow:1; margin: 0;">
							<button type="button" class="button button-secondary nsmw-remove-repeater-item" title="Remove">&times;</button>
						</div>
					</script>
					<p><button type="button" class="button button-primary nsmw-add-repeater-item" style="width:100%; text-align:center;">+ Add Social Icon</button></p>
				</div>

				<button type="button" class="nsmw-section-toggle"><?php esc_html_e('Widget Customization Settings', 'new-social-media-widget'); ?></button>

				<div class="nsmw-section-content" style="display:none;">
					<!-- widget display setting -->
					<div class="nsmw-display-settings" id="display-settings-<?php echo esc_attr($nsmw_widget_id); ?>">

						<div class="nsmw-settings-grid">
							<p>
								<label><?php esc_html_e('How Many Icon Into Per Row:', 'new-social-media-widget'); ?></label>
								<select id="<?php echo esc_attr($this->get_field_id('columns')); ?>" name="<?php echo esc_attr($this->get_field_name('columns')); ?>">
									<option value="col-md-6 col-sm-6 col-xs-6" <?php if ($columns == "col-md-6 col-sm-6 col-xs-6") echo "selected=selected"; ?>>2 Icon</option>
									<option value="col-md-4 col-sm-4 col-xs-4" <?php if ($columns == "col-md-4 col-sm-4 col-xs-4") echo "selected=selected"; ?>>3 Icon</option>
									<option value="col-md-3 col-sm-3 col-xs-3" <?php if ($columns == "col-md-3 col-sm-3 col-xs-3") echo "selected=selected"; ?>>4 Icon</option>
									<option value="col-md-2 col-sm-2 col-xs-2" <?php if ($columns == "col-md-2 col-sm-2 col-xs-2") echo "selected=selected"; ?>>6 Icon</option>
								</select>
							</p>


							<p>
								<label><?php esc_html_e('Icon Size:', 'new-social-media-widget'); ?></label>
								<select id="<?php echo esc_attr($this->get_field_id('icon_size')); ?>" name="<?php echo esc_attr($this->get_field_name('icon_size')); ?>">
									<option value="lg" <?php if ($icon_size == "lg") echo "selected=selected"; ?>>1x</option>
									<option value="2" <?php if ($icon_size == 2) echo "selected=selected"; ?>>2x</option>
									<option value="3" <?php if ($icon_size == 3) echo "selected=selected"; ?>>3x</option>
									<option value="4" <?php if ($icon_size == 4) echo "selected=selected"; ?>>4x</option>
									<option value="5" <?php if ($icon_size == 5) echo "selected=selected"; ?>>5x</option>
								</select>
							</p>

							<p>
								<label><?php esc_html_e('Spacing Between Icons:', 'new-social-media-widget'); ?></label>
								<select id="<?php echo esc_attr($this->get_field_id('padding')); ?>" name="<?php echo esc_attr($this->get_field_name('padding')); ?>">
									<option value="0" <?php if ($padding == 0) echo "selected=selected"; ?>>None</option>
									<option value="1" <?php if ($padding == 1) echo "selected=selected"; ?>>1px</option>
									<option value="2" <?php if ($padding == 2) echo "selected=selected"; ?>>2px</option>
									<option value="3" <?php if ($padding == 3) echo "selected=selected"; ?>>3px</option>
									<option value="4" <?php if ($padding == 4) echo "selected=selected"; ?>>4px</option>
									<option value="5" <?php if ($padding == 5) echo "selected=selected"; ?>>5px</option>
									<option value="6" <?php if ($padding == 6) echo "selected=selected"; ?>>6px</option>
									<option value="7" <?php if ($padding == 7) echo "selected=selected"; ?>>7px</option>
									<option value="8" <?php if ($padding == 8) echo "selected=selected"; ?>>8px</option>
									<option value="9" <?php if ($padding == 9) echo "selected=selected"; ?>>9px</option>
									<option value="10" <?php if ($padding == 10) echo "selected=selected"; ?>>10px</option>
								</select>
							</p>
						</div>



						<div class="sap_block effect_type nsmw-settings-grid">
							<p>
								<label><?php esc_html_e('Effect Type:', 'new-social-media-widget'); ?></label>
								<select class="nsmw-effect-type-select" id="<?php echo esc_attr($this->get_field_id('effect_type')); ?>" name="<?php echo esc_attr($this->get_field_name('effect_type')); ?>">
									<option value="none" <?php if ($effect_type == "none") echo "selected=selected"; ?>>None</option>
									<option value="hover" <?php if ($effect_type == "hover") echo "selected=selected"; ?>>Hover</option>
								</select>
							</p>
							<p class="nsmwhe-wrap" style="display:none;">
								<label><?php esc_html_e('Hover  Effects:', 'new-social-media-widget'); ?></label>
								<select id="<?php echo esc_attr($this->get_field_id('hover_effects')); ?>" name="<?php echo esc_attr($this->get_field_name('hover_effects')); ?>">
									<option value="0" <?php if (empty($hover_effects) || $hover_effects == '0') echo "selected=selected"; ?>>None</option>
									<option value="0" disabled>-- Shadow and Glow Transitions --</option>
									<option value="hvr-shadow" <?php if ($hover_effects == "hvr-shadow") echo "selected=selected"; ?>>shadow</option>
									<option value="hvr-grow-shadow" <?php if ($hover_effects == "hvr-grow-shadow") echo "selected=selected"; ?>>grow-shadow</option>
									<option value="hvr-float-shadow" <?php if ($hover_effects == "hvr-float-shadow") echo "selected=selected"; ?>>float-shadow</option>
									<option value="hvr-glow" <?php if ($hover_effects == "hvr-glow") echo "selected=selected"; ?>>glow</option>
									<option value="hvr-shadow-radial" <?php if ($hover_effects == "hvr-shadow-radial") echo "selected=selected"; ?>>shadow-radial</option>
									<option value="hvr-box-shadow-outset" <?php if ($hover_effects == "hvr-box-shadow-outset") echo "selected=selected"; ?>>box-shadow-outset</option>
									<option value="hvr-box-shadow-inset" <?php if ($hover_effects == "hvr-box-shadow-inset") echo "selected=selected"; ?>>box-shadow-inset</option>
								</select>
							</p>

						</div>


						<div class="sap_block effect_color" style="margin-top:5px;">
							<label style="margin-bottom:15px; display:block;"><b><i><?php esc_html_e('Note: To change the color first click on default', 'new-social-media-widget'); ?></i></b></label>
							<div class="nsmw-settings-grid">
								<p>
									<label><?php esc_html_e('Div Background Color:', 'new-social-media-widget'); ?></label><br>
									<input type="text" class="div_bg_color" id="<?php echo esc_attr($this->get_field_id('div_bg_color')); ?>" name="<?php echo esc_attr($this->get_field_name('div_bg_color')); ?>" value="<?php echo esc_attr($div_bg_color); ?>" data-default-color="#003b5b">
								</p>

								<p>
									<label><?php esc_html_e('Icon Color:', 'new-social-media-widget'); ?></label><br>
									<input type="text" class="icon_color" id="<?php echo esc_attr($this->get_field_id('icon_color')); ?>" name="<?php echo esc_attr($this->get_field_name('icon_color')); ?>" value="<?php echo esc_attr($icon_color); ?>" data-default-color="#ffffff">
								</p>
							</div>
						</div>


						<div style="margin-top: 10px;">
							<label style="display:block; margin-bottom:6px; font-weight: 600;"><?php esc_html_e('Open Link URL:', 'new-social-media-widget'); ?></label>
							<div class="nsmw-radio-group">
								<label class="nsmw-radio-option">
									<input type="radio" id="<?php echo esc_attr($this->get_field_id('url_target')); ?>_new" name="<?php echo esc_attr($this->get_field_name('url_target')); ?>" value="_new" <?php if ($url_target == "_new") echo "checked=checked"; ?>>
									<?php esc_html_e('Into New Tab', 'new-social-media-widget'); ?>
								</label>
								<label class="nsmw-radio-option">
									<input type="radio" id="<?php echo esc_attr($this->get_field_id('url_target')); ?>_self" name="<?php echo esc_attr($this->get_field_name('url_target')); ?>" value="_self" <?php if ($url_target == "_self") echo "checked=checked"; ?>>
									<?php esc_html_e('Into Same Tab', 'new-social-media-widget'); ?>
								</label>
							</div>
						</div>


						<p>
							<label><?php esc_html_e('Custom CSS:', 'new-social-media-widget'); ?></label><br>
							<textarea class="css" id="<?php echo esc_attr($this->get_field_id('css')); ?>" name="<?php echo esc_attr($this->get_field_name('css')); ?>" style=" width: 100%;"><?php echo esc_attr($css); ?></textarea>
						</p>
					</div>
				</div>
				<button type="button" class="nsmw-section-toggle" style="background: #fff9e0; color: #856404; font-weight: 700;"><?php esc_html_e('🔥 Pro Features & Support', 'new-social-media-widget'); ?></button>

				<div class="nsmw-section-content" style="display:none; padding: 15px; background: #fffcf0;">
					<p style="margin-top: 0; font-weight: 600; color: #856404;"><?php esc_html_e('Upgrade to Pro for more power:', 'new-social-media-widget'); ?></p>
					<ul style="margin-bottom: 20px; list-style-type: none; padding-left: 0;">
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('30+ Pro Social Networks', 'new-social-media-widget'); ?></li>
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('3 Unique Stunning Layout Styles', 'new-social-media-widget'); ?></li>
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('Custom Background & Icon Color on Hover', 'new-social-media-widget'); ?></li>
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('60+ Exciting Hover Animations (2D, Curls, Glow)', 'new-social-media-widget'); ?></li>
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('10+ 3D Transform Rotations', 'new-social-media-widget'); ?></li>
						<li style="margin-bottom: 8px;"><span class="dashicons dashicons-yes" style="color: #28a745; vertical-align: middle;"></span> <?php esc_html_e('Priority Email & Forum Support', 'new-social-media-widget'); ?></li>
					</ul>

					<div style="display: flex; flex-direction: column; gap: 10px;">
						<a href="https://awplife.com/demo/social-media-widget-premium/" target="_blank" class="button button-secondary nsmw-pro-btn" style="text-align: center; justify-content: center;"><?php esc_html_e('Check Pro Live Demo', 'new-social-media-widget'); ?></a>
						<a href="https://awplife.com/wordpress-plugins/social-media-widget-wordpress-plugin/" target="_blank" class="button button-primary nsmw-pro-buy-btn" style="text-align: center; justify-content: center; background: #ff4d4d; border-color: #ff3333; color: white;"><?php esc_html_e('Buy Pro Plugin', 'new-social-media-widget'); ?></a>
					</div>
					<p style="margin-top: 15px; font-size: 11px; text-align: center; color: #666; font-style: italic;">
						<?php esc_html_e('Unlock more features today!', 'new-social-media-widget'); ?>
					</p>
				</div>
			</div>
<?php
		}

		/**
		 * Widget Save Settings
		 */
		public function update($new_instance, $old_instance)
		{
			// processes widget options to be saved
			$instance = array();

			if (empty($new_instance['nsmw_widget_id'])) {
				$new_instance['nsmw_widget_id'] = wp_rand(10, 100000);
			}
			$instance['nsmw_widget_id'] = absint($new_instance['nsmw_widget_id']);
			$instance['title'] = sanitize_text_field($new_instance['title']);

			// Handle Repeater Profiles
			if (isset($new_instance['profiles']) && is_array($new_instance['profiles'])) {
				$instance['profiles'] = array();
				foreach ($new_instance['profiles'] as $profile) {
					if (!empty($profile['network']) && !empty($profile['url'])) {
						$instance['profiles'][] = array(
							'network' => sanitize_text_field($profile['network']),
							'url'     => esc_url_raw($profile['url'])
						);
					}
				}
			}

			// Keep legacy fields updated if they were set, so shortcodes / old installs stay compatible
			// Social URLs — sanitize with esc_url_raw for safe storage
			$url_fields = array(
				'facebook',
				'bluesky',
				'x-twitter',
				'google-plus',
				'linkedin',
				'instagram',
				'pinterest',
				'flickr',
				'tumblr',
				'dribbble',
				'vine',
				'yahoo',
				'qq',
				'reddit',
				'vk',
				'wordpress',
				'stack-overflow',
				'stumbleupon',
				'lastfm',
				'xing',
				'youtube',
				'soundcloud',
				'digg',
				'git',
				'share-alt',
				'snapchat',
				'vimeo',
				'weixin',
				'wikipedia-w',
				'yelp',
				'skype',
				'medium',
				'rss',
				'envelope',
				'twitch',
				'telegram',
				'discord',
				'tiktok',
				'threads',
				'mastodon',
				'whatsapp'
			);
			foreach ($url_fields as $field) {
				$instance[$field] = (! empty($new_instance[$field])) ? esc_url_raw($new_instance[$field]) : '';
			}

			// Widget display settings — sanitize per type
			$instance['style_type'] = sanitize_text_field($new_instance['style_type']);
			$instance['columns'] = sanitize_text_field($new_instance['columns']);
			$instance['icon_size'] = sanitize_text_field($new_instance['icon_size']);
			$instance['padding'] = (! empty($new_instance['padding'])) ? intval($new_instance['padding']) : 0;
			$instance['background'] = sanitize_text_field($new_instance['background']);
			$instance['div_bg_color'] = (! empty($new_instance['div_bg_color'])) ? sanitize_hex_color($new_instance['div_bg_color']) : '';
			$instance['icon_color'] = (! empty($new_instance['icon_color'])) ? sanitize_hex_color($new_instance['icon_color']) : '';
			$instance['effect_type'] = sanitize_text_field($new_instance['effect_type']);
			$instance['hover_effects'] = sanitize_text_field($new_instance['hover_effects']);
			$instance['css'] = (! empty($new_instance['css'])) ? wp_strip_all_tags($new_instance['css']) : '';
			$instance['url_target'] = sanitize_text_field($new_instance['url_target']);

			// Sanitize POST data before saving
			if (isset($_POST['pos']) && is_array($_POST['pos'])) {
				check_admin_referer('update-widget-' . $this->id_base); // Standard widget nonce check
				$sanitized_pos = array_map('intval', $_POST['pos']);
				update_option('social_media_icon_pos', $sanitized_pos);
			}
			return $instance;
		}
	} // end of class
} // end of class exist

add_action('widgets_init', function () {
	register_widget('NSMW_New_Social_Media_Free');
});

/**
 * Shortcode Support
 * Usage: [social_media_widget style_type="default" facebook="https://..." etc]
 */
function nsmw_free_shortcode($atts)
{
	if (!class_exists('NSMW_New_Social_Media_Free')) {
		return '';
	}

	$defaults = array(
		'title' => '',
		'columns' => 'col-md-2',
		'icon_size' => '2',
		'padding' => '0',
		'background' => '',
		'margin_top' => '0',
		'margin_bottom' => '0',
		'div_bg_color' => '#003b5b',
		'icon_color' => '#ffffff',
		'effect_type' => 'none',
		'hover_effects' => '',
		'css' => '',
		'url_target' => '_new',
		// Social platforms
		'facebook' => '',
		'x-twitter' => '',
		'linkedin' => '',
		'instagram' => '',
		'pinterest' => '',
		'flickr' => '',
		'tumblr' => '',
		'youtube' => '',
		'vimeo' => '',
		'rss' => '',
		'envelope' => '',
		'whatsapp' => '',
		'profiles' => array()
	);

	$instance = shortcode_atts($defaults, $atts);

	// If Gutenberg passes the 'profiles' attribute via ServerSideRender
	if (isset($atts['profiles'])) {
		if (is_string($atts['profiles'])) {
			$decoded = json_decode($atts['profiles'], true);
			if (is_array($decoded)) {
				$instance['profiles'] = $decoded;
			}
		} elseif (is_array($atts['profiles'])) {
			$instance['profiles'] = $atts['profiles'];
		}
	}

	// Generate random widget ID to ensure CSS selectors match HTML output correctly
	// Need to save it in instance as WP_Widget instances do
	$instance['nsmw_widget_id'] = wp_rand(10000, 99999);

	$args = array(
		'before_widget' => '<div class="nsmw-shortcode-wrapper">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
		'widget_id'     => 'nsmw-shortcode-' . $instance['nsmw_widget_id'],
	);

	ob_start();
	the_widget('NSMW_New_Social_Media_Free', $instance, $args);
	return ob_get_clean();
}
add_shortcode('social_media_widget', 'nsmw_free_shortcode');

/**
 * Gutenberg Block Registration
 */
function nsmw_register_block()
{
	if (!function_exists('register_block_type')) {
		return;
	}

	wp_register_script(
		'nsmw-block-editor',
		plugin_dir_url(__FILE__) . 'js/nsmw-block.js',
		array('wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-block-editor', 'wp-components'),
		'3.6.0',
		true
	);

	wp_localize_script('nsmw-block-editor', 'nsmwBlockData', array(
		'pluginUrl' => plugin_dir_url(__FILE__)
	));

	$attributes = array(
		'title' => array('type' => 'string', 'default' => ''),
		'columns' => array('type' => 'string', 'default' => 'col-md-2'),
		'icon_size' => array('type' => 'string', 'default' => '2'),
		'padding' => array('type' => 'string', 'default' => '0'),
		'background' => array('type' => 'string', 'default' => ''),
		'div_bg_color' => array('type' => 'string', 'default' => '#003b5b'),
		'icon_color' => array('type' => 'string', 'default' => '#ffffff'),
		'effect_type' => array('type' => 'string', 'default' => 'none'),
		'hover_effects' => array('type' => 'string', 'default' => ''),
		'css' => array('type' => 'string', 'default' => ''),
		'url_target' => array('type' => 'string', 'default' => '_new'),
	);

	$social_keys = array(
		'facebook',
		'x-twitter',
		'instagram',
		'youtube',
		'pinterest',
		'linkedin',
		'tumblr',
		'flickr',
		'vimeo',
		'rss',
		'whatsapp',
		'envelope'
	);

	foreach ($social_keys as $key) {
		$attributes[$key] = array('type' => 'string', 'default' => '');
	}

	$attributes['profiles'] = array(
		'type' => 'array',
		'default' => array(),
		'items' => array(
			'type' => 'object',
			'properties' => array(
				'network' => array('type' => 'string'),
				'url' => array('type' => 'string')
			)
		)
	);



	wp_register_style('nsmw-block-grid', plugin_dir_url(__FILE__) . 'css/nsmw-grid.css', array(), '3.5.2');
	wp_register_style('nsmw-block-all', plugin_dir_url(__FILE__) . 'css/all.css', array(), '3.5.2');
	wp_register_style('nsmw-block-hover', plugin_dir_url(__FILE__) . 'css/hover-min.css', array(), '3.5.2');

	register_block_type('nsmw/social-media-icons', array(
		'api_version' => 3,
		'editor_script' => 'nsmw-block-editor',
		'style' => array('nsmw-block-grid', 'nsmw-block-all', 'nsmw-block-hover'), // Ensure styles load in Gutenberg
		'attributes' => $attributes,
		'render_callback' => 'nsmw_free_shortcode', // Reusing the shortcode renderer!
	));
}
add_action('init', 'nsmw_register_block');

?>