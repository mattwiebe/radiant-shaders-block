<?php
/**
 * Plugin bootstrap.
 *
 * @package WPRadiantShaders
 */

namespace WPRadiantShaders;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	/**
	 * Cached shader metadata.
	 *
	 * @var array|null
	 */
	protected static $shader_metadata = null;

	/**
	 * Start the plugin.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_config' ) );
	}

	/**
	 * Register the block.
	 *
	 * @return void
	 */
	public static function register_block() {
		$block_dir = WP_RADIANT_SHADERS_DIR . 'build/radiant-shader';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$block_dir,
			array(
				'render_callback' => array( __CLASS__, 'render_radiant_shader_block' ),
			)
		);
	}

	/**
	 * Provide editor-side config before the block editor script runs.
	 *
	 * @return void
	 */
	public static function enqueue_editor_config() {
		$handle = 'wp-radiant-shaders-radiant-shader-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			'window.WPRadiantShadersBlock = ' . wp_json_encode(
				array(
					'assetsBaseUrl' => esc_url_raw( WP_RADIANT_SHADERS_URL . 'assets/radiant-static/' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Load shader metadata from the generated JSON manifest.
	 *
	 * @return array
	 */
	protected static function get_shader_metadata() {
		if ( null !== self::$shader_metadata ) {
			return self::$shader_metadata;
		}

		$path = WP_RADIANT_SHADERS_DIR . 'assets/radiant-shaders.json';

		if ( ! file_exists( $path ) ) {
			self::$shader_metadata = array();
			return self::$shader_metadata;
		}

		$decoded = json_decode( file_get_contents( $path ), true );
		self::$shader_metadata = is_array( $decoded ) ? $decoded : array();

		return self::$shader_metadata;
	}

	/**
	 * Index shader metadata by shader id.
	 *
	 * @return array
	 */
	protected static function get_shader_map() {
		$map = array();

		foreach ( self::get_shader_metadata() as $shader ) {
			if ( isset( $shader['id'] ) ) {
				$map[ $shader['id'] ] = $shader;
			}
		}

		return $map;
	}

	/**
	 * Allowlisted gallery color schemes.
	 *
	 * @return array
	 */
	protected static function get_color_schemes() {
		return array(
			'amber'   => 'none',
			'mono'    => 'grayscale(1)',
			'blue'    => 'hue-rotate(175deg)',
			'rose'    => 'hue-rotate(300deg) saturate(1.1)',
			'emerald' => 'hue-rotate(90deg) saturate(1.2)',
			'arctic'  => 'hue-rotate(180deg) saturate(0.5) brightness(1.1)',
		);
	}

	/**
	 * Allowlisted overlay blend modes.
	 *
	 * @return string[]
	 */
	protected static function get_overlay_blend_modes() {
		return array(
			'normal',
			'multiply',
			'screen',
			'overlay',
			'darken',
			'lighten',
			'color-dodge',
			'color-burn',
			'hard-light',
			'soft-light',
			'difference',
			'exclusion',
			'hue',
			'saturation',
			'color',
			'luminosity',
		);
	}

	/**
	 * Allowlisted shader layer blend modes.
	 *
	 * @return string[]
	 */
	protected static function get_shader_blend_modes() {
		return array(
			'normal',
			'multiply',
			'screen',
			'overlay',
			'darken',
			'lighten',
			'soft-light',
			'hard-light',
			'difference',
			'exclusion',
			'luminosity',
		);
	}

	/**
	 * Allowed color modes.
	 *
	 * @return string[]
	 */
	protected static function get_color_modes() {
		return array( 'preset', 'theme' );
	}

	/**
	 * Convert a theme palette slug to the matching CSS custom property.
	 *
	 * @param string $slug Theme palette slug.
	 * @return string
	 */
	protected static function get_theme_palette_css_var( $slug ) {
		if ( empty( $slug ) ) {
			return '';
		}

		return sprintf( 'var(--wp--preset--color--%s)', sanitize_title( $slug ) );
	}

	/**
	 * Sanitize an optional custom theme color value.
	 *
	 * @param string $value Raw color value.
	 * @return string
	 */
	protected static function get_theme_color_value( $value ) {
		$hex = sanitize_hex_color( $value );

		return is_string( $hex ) ? $hex : '';
	}

	/**
	 * Sanitize block config against the shader manifest.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	protected static function normalize_block_config( $attributes ) {
		$shader_map = self::get_shader_map();
		$shader_id  = isset( $attributes['shaderId'] ) ? sanitize_key( $attributes['shaderId'] ) : 'flow-field';

		if ( ! isset( $shader_map[ $shader_id ] ) ) {
			$shader_id = 'flow-field';
		}

		$shader  = $shader_map[ $shader_id ];
		$schemes = self::get_color_schemes();
		$scheme  = isset( $attributes['scheme'] ) ? sanitize_key( $attributes['scheme'] ) : 'amber';
		$color_mode = isset( $attributes['colorMode'] ) ? sanitize_key( $attributes['colorMode'] ) : 'preset';
		$theme_color_slug = isset( $attributes['themeColorSlug'] ) ? sanitize_title( $attributes['themeColorSlug'] ) : '';
		$theme_color_value = isset( $attributes['themeColorValue'] ) ? self::get_theme_color_value( $attributes['themeColorValue'] ) : '';
		$show_shader_label = isset( $attributes['showShaderLabel'] ) ? (bool) $attributes['showShaderLabel'] : false;
		$invert_tone = isset( $attributes['invertTone'] ) ? (bool) $attributes['invertTone'] : false;
		$shader_blend_mode = isset( $attributes['shaderBlendMode'] ) ? sanitize_key( $attributes['shaderBlendMode'] ) : 'normal';
		$overlay_blend_mode = isset( $attributes['overlayBlendMode'] ) ? sanitize_key( $attributes['overlayBlendMode'] ) : 'soft-light';
		$overlay_opacity = isset( $attributes['overlayOpacity'] ) ? (float) $attributes['overlayOpacity'] : 36;

		if ( ! isset( $schemes[ $scheme ] ) ) {
			$scheme = 'amber';
		}

		if ( ! in_array( $color_mode, self::get_color_modes(), true ) ) {
			$color_mode = 'preset';
		}

		if ( ! in_array( $overlay_blend_mode, self::get_overlay_blend_modes(), true ) ) {
			$overlay_blend_mode = 'soft-light';
		}

		if ( ! in_array( $shader_blend_mode, self::get_shader_blend_modes(), true ) ) {
			$shader_blend_mode = 'normal';
		}

		$overlay_opacity = min( max( $overlay_opacity, 0 ), 100 );

		$min_height = isset( $attributes['minHeight'] ) ? absint( $attributes['minHeight'] ) : 480;
		$min_height = min( max( $min_height, 180 ), 1600 );

		$defaults = array();
		$params   = array();

		if ( ! empty( $shader['params'] ) && is_array( $shader['params'] ) ) {
			foreach ( $shader['params'] as $param ) {
				if ( empty( $param['name'] ) ) {
					continue;
				}

				$name    = $param['name'];
				$min     = isset( $param['min'] ) ? (float) $param['min'] : 0;
				$max     = isset( $param['max'] ) ? (float) $param['max'] : $min;
				$default = isset( $param['default'] ) ? (float) $param['default'] : $min;
				$value   = $default;

				$defaults[ $name ] = $default;

				if ( isset( $attributes['params'][ $name ] ) && is_numeric( $attributes['params'][ $name ] ) ) {
					$value = (float) $attributes['params'][ $name ];
				}

				if ( $value < $min ) {
					$value = $min;
				}

				if ( $value > $max ) {
					$value = $max;
				}

				$params[ $name ] = $value;
			}
		}

		if ( empty( $params ) ) {
			$params = $defaults;
		}

		return array(
			'shader'       => $shader,
			'shaderId'     => $shader_id,
			'colorMode'    => $color_mode,
			'scheme'       => $scheme,
			'schemeFilter' => 'preset' === $color_mode ? $schemes[ $scheme ] : 'none',
			'themeColorSlug' => $theme_color_slug,
			'themeColorValue' => $theme_color_value,
			'showShaderLabel' => $show_shader_label,
			'invertTone' => $invert_tone,
			'shaderBlendMode' => $shader_blend_mode,
			'overlayBlendMode' => $overlay_blend_mode,
			'overlayOpacity' => 'theme' === $color_mode ? $overlay_opacity : 0,
			'overlayTint' => 'theme' === $color_mode
				? ( $theme_color_value ? $theme_color_value : self::get_theme_palette_css_var( $theme_color_slug ) )
				: '',
			'minHeight'    => $min_height,
			'params'       => $params,
		);
	}

	/**
	 * Render the Radiant Shader block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block markup.
	 * @return string
	 */
	public static function render_radiant_shader_block( $attributes, $content ) {
		$config = self::normalize_block_config( $attributes );

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'wp-radiant-shader',
				'style' => sprintf(
					'--wp-radiant-shader-min-height:%1$dpx;--wp-radiant-shader-blend-mode:%2$s;--wp-radiant-overlay-opacity:%3$s;--wp-radiant-overlay-blend-mode:%4$s;--wp-radiant-surface:%5$s;%6$s',
					(int) $config['minHeight'],
					esc_attr( $config['shaderBlendMode'] ),
					esc_attr( (string) $config['overlayOpacity'] ),
					esc_attr( $config['overlayBlendMode'] ),
					esc_attr(
						$config['overlayTint']
							? sprintf( 'color-mix(in srgb, %s 18%%, #120804)', $config['overlayTint'] )
							: '#120804'
					),
					$config['overlayTint']
						? '--wp-radiant-overlay-tint:' . esc_attr( $config['overlayTint'] ) . ';'
						: ''
				),
			)
		);

		$payload = array(
			'shaderId'     => $config['shaderId'],
			'shaderFile'   => $config['shader']['file'],
			'colorMode'    => $config['colorMode'],
			'scheme'       => $config['scheme'],
			'schemeFilter' => $config['schemeFilter'],
			'showShaderLabel' => $config['showShaderLabel'],
			'themeColorSlug' => $config['themeColorSlug'],
			'themeColorValue' => $config['themeColorValue'],
			'invertTone' => $config['invertTone'],
			'shaderBlendMode' => $config['shaderBlendMode'],
			'params'       => $config['params'],
			'assetsBaseUrl' => WP_RADIANT_SHADERS_URL . 'assets/radiant-static/',
		);

		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				class="wp-radiant-shader__background"
				aria-hidden="true"
				data-wp-radiant-config="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
			></div>
			<div class="wp-radiant-shader__overlay"></div>
			<div class="wp-radiant-shader__content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
