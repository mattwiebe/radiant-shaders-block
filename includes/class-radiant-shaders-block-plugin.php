<?php
/**
 * Plugin bootstrap.
 *
 * @package RadiantShadersBlock
 */

namespace RadiantShadersBlock;

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
	 * Tracks whether the legacy fallback initializer was already enqueued.
	 *
	 * @var bool
	 */
	protected static $legacy_view_script_enqueued = false;

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
		$block_dir = RADIANT_SHADERS_BLOCK_DIR . 'build/radiant-shader-block';

		if ( ! file_exists( $block_dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$block_dir,
			array(
				'render_callback' => array( __CLASS__, 'render_radiant_shader_block' ),
			)
		);

		self::register_legacy_block_alias();
	}

	/**
	 * Register the previous block namespace so existing content keeps working.
	 *
	 * @return void
	 */
	protected static function register_legacy_block_alias() {
		$registry = \WP_Block_Type_Registry::get_instance();

		if ( $registry->is_registered( 'wp-radiant-shaders/radiant-shader' ) ) {
			return;
		}

		$block = $registry->get_registered( 'radiant-shaders-block/radiant-shader' );

		if ( ! $block ) {
			return;
		}

		$supports = is_array( $block->supports ) ? $block->supports : array();
		$supports['inserter'] = false;

		register_block_type(
			'wp-radiant-shaders/radiant-shader',
			array(
				'api_version'          => $block->api_version,
				'title'                => $block->title,
				'description'          => $block->description,
				'category'             => $block->category,
				'icon'                 => $block->icon,
				'keywords'             => $block->keywords,
				'attributes'           => $block->attributes,
				'supports'             => $supports,
				'example'              => $block->example,
				'uses_context'         => $block->uses_context,
				'provides_context'     => $block->provides_context,
				'selectors'            => $block->selectors,
				'style_handles'        => $block->style_handles,
				'editor_style_handles' => $block->editor_style_handles,
				'script_handles'       => $block->script_handles,
				'editor_script_handles'=> $block->editor_script_handles,
				'view_script_handles'  => $block->view_script_handles,
				'render_callback'      => $block->render_callback,
			)
		);
	}

	/**
	 * Provide editor-side config before the block editor script runs.
	 *
	 * @return void
	 */
	public static function enqueue_editor_config() {
		$handle = 'radiant-shaders-block-radiant-shader-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_add_inline_script(
			$handle,
			'window.RadiantShadersBlockEditor = ' . wp_json_encode(
				array(
					'assetsBaseUrl' => esc_url_raw( RADIANT_SHADERS_BLOCK_URL . 'assets/radiant-static/' ),
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

		$path = RADIANT_SHADERS_BLOCK_DIR . 'assets/radiant-shaders.json';

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
			'amber'   => '#c8956c',
			'mono'    => '#a7a298',
			'blue'    => '#6ca7c8',
			'rose'    => '#d96c97',
			'emerald' => '#59b88d',
			'arctic'  => '#8ebfd1',
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
	 * Clamp a value to a range.
	 *
	 * @param float $value Raw value.
	 * @param float $min Minimum.
	 * @param float $max Maximum.
	 * @return float
	 */
	protected static function clamp( $value, $min, $max ) {
		return min( max( $value, $min ), $max );
	}

	/**
	 * Parse a hex color into RGB channels.
	 *
	 * @param string $value Hex color.
	 * @return array|null
	 */
	protected static function parse_hex_color( $value ) {
		$normalized = sanitize_hex_color( $value );

		if ( ! is_string( $normalized ) ) {
			return null;
		}

		return array(
			'r' => hexdec( substr( $normalized, 1, 2 ) ),
			'g' => hexdec( substr( $normalized, 3, 2 ) ),
			'b' => hexdec( substr( $normalized, 5, 2 ) ),
		);
	}

	/**
	 * Convert RGB values to HSL.
	 *
	 * @param array $rgb RGB channels.
	 * @return array
	 */
	protected static function rgb_to_hsl( $rgb ) {
		$red       = $rgb['r'] / 255;
		$green     = $rgb['g'] / 255;
		$blue      = $rgb['b'] / 255;
		$max       = max( $red, $green, $blue );
		$min       = min( $red, $green, $blue );
		$delta     = $max - $min;
		$lightness = ( $max + $min ) / 2;

		if ( 0.0 === $delta ) {
			return array(
				'h' => 0,
				's' => 0,
				'l' => $lightness,
			);
		}

		$saturation = $lightness > 0.5
			? $delta / ( 2 - $max - $min )
			: $delta / ( $max + $min );

		switch ( $max ) {
			case $red:
				$hue = fmod( ( $green - $blue ) / $delta + ( $green < $blue ? 6 : 0 ), 6 );
				break;
			case $green:
				$hue = ( $blue - $red ) / $delta + 2;
				break;
			default:
				$hue = ( $red - $green ) / $delta + 4;
				break;
		}

		return array(
			'h' => $hue * 60,
			's' => $saturation,
			'l' => $lightness,
		);
	}

	/**
	 * Build the iframe filter string used by the editor preview.
	 *
	 * @param string $target_color Target color.
	 * @param bool   $invert_tone Whether invert tone is enabled.
	 * @return string
	 */
	protected static function build_shader_filter( $target_color, $invert_tone ) {
		$baseline_rgb = self::parse_hex_color( '#c8956c' );
		$target_rgb   = self::parse_hex_color( $target_color );

		if ( ! $baseline_rgb || ! $target_rgb ) {
			return $invert_tone ? 'invert(1) hue-rotate(180deg)' : 'none';
		}

		$baseline_hsl = self::rgb_to_hsl( $baseline_rgb );
		$target_hsl   = self::rgb_to_hsl( $target_rgb );
		$hue_delta    = fmod( ( $target_hsl['h'] - $baseline_hsl['h'] + 540 ), 360 ) - 180;
		$saturation_ratio = self::clamp(
			$target_hsl['s'] / max( $baseline_hsl['s'], 0.01 ),
			0,
			2.5
		);
		$brightness_ratio = self::clamp(
			$target_hsl['l'] / max( $baseline_hsl['l'], 0.01 ),
			0.75,
			1.35
		);
		$parts = array();

		if ( $invert_tone ) {
			$parts[] = 'invert(1)';
			$parts[] = 'hue-rotate(180deg)';
		}

		if ( abs( $hue_delta ) > 0.5 ) {
			$parts[] = 'hue-rotate(' . round( $hue_delta ) . 'deg)';
		}

		if ( abs( $saturation_ratio - 1 ) > 0.02 ) {
			$parts[] = 'saturate(' . number_format( $saturation_ratio, 2, '.', '' ) . ')';
		}

		if ( abs( $brightness_ratio - 1 ) > 0.03 ) {
			$parts[] = 'brightness(' . number_format( $brightness_ratio, 2, '.', '' ) . ')';
		}

		return empty( $parts ) ? 'none' : implode( ' ', $parts );
	}

	/**
	 * Build the wrapper surface color used beneath the blended shader iframe.
	 *
	 * @param string $target_color Target color.
	 * @return string
	 */
	protected static function build_shader_surface( $target_color ) {
		$base_rgb   = self::parse_hex_color( '#120804' );
		$target_rgb = self::parse_hex_color( $target_color );

		if ( ! $base_rgb || ! $target_rgb ) {
			return '#120804';
		}

		$mix_ratio = 0.18;
		$red   = (int) round( $target_rgb['r'] * $mix_ratio + $base_rgb['r'] * ( 1 - $mix_ratio ) );
		$green = (int) round( $target_rgb['g'] * $mix_ratio + $base_rgb['g'] * ( 1 - $mix_ratio ) );
		$blue  = (int) round( $target_rgb['b'] * $mix_ratio + $base_rgb['b'] * ( 1 - $mix_ratio ) );

		return sprintf( 'rgb(%d, %d, %d)', $red, $green, $blue );
	}

	/**
	 * Sanitize persisted iframe props.
	 *
	 * @param mixed $value Raw iframe props.
	 * @return array
	 */
	protected static function get_iframe_props( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$filter = isset( $value['filter'] ) ? sanitize_text_field( $value['filter'] ) : '';
		$filter = preg_match( '/^[a-z0-9().,%#\s-]+$/i', $filter ) ? $filter : '';
		$mix_blend_mode = isset( $value['mixBlendMode'] ) ? sanitize_key( $value['mixBlendMode'] ) : '';

		if ( ! in_array( $mix_blend_mode, self::get_shader_blend_modes(), true ) ) {
			$mix_blend_mode = '';
		}

		if ( '' === $filter || '' === $mix_blend_mode ) {
			return array();
		}

		return array(
			'filter'       => $filter,
			'mixBlendMode' => $mix_blend_mode,
		);
	}

	/**
	 * Build the direct shader iframe URL with persisted params.
	 *
	 * @param array $config Normalized block config.
	 * @return string
	 */
	protected static function get_shader_src_url( $config ) {
		if ( empty( $config['shader']['file'] ) ) {
			return '';
		}

		$url = RADIANT_SHADERS_BLOCK_URL . 'assets/radiant-static/' . ltrim( $config['shader']['file'], '/' );

		if ( empty( $config['params'] ) ) {
			return $url;
		}

		return $url . '?wp_radiant_params=' . rawurlencode( wp_json_encode( $config['params'] ) );
	}

	/**
	 * Enqueue the legacy frontend mount script for blocks missing iframe props.
	 *
	 * @return void
	 */
	protected static function enqueue_legacy_view_script() {
		if ( self::$legacy_view_script_enqueued ) {
			return;
		}

		wp_enqueue_script( 'wp-dom-ready' );
		wp_add_inline_script(
			'wp-dom-ready',
				'(function(){var init=function(){document.querySelectorAll(".radiant-shader-block__background[data-wp-radiant-config]").forEach(function(element){var rawConfig=element.getAttribute("data-wp-radiant-config");if(!rawConfig){return;}try{var config=JSON.parse(rawConfig);var iframe=element.querySelector("iframe.radiant-shader-block__iframe");if(!iframe){iframe=document.createElement("iframe");iframe.className="radiant-shader-block__iframe";iframe.setAttribute("title",config.shaderId||"Radiant shader");iframe.setAttribute("loading","lazy");iframe.setAttribute("aria-hidden","true");iframe.setAttribute("tabindex","-1");iframe.setAttribute("allow","autoplay; fullscreen");element.replaceChildren(iframe);}var params=new URLSearchParams();if(config.params&&Object.keys(config.params).length){params.set("wp_radiant_params",JSON.stringify(config.params));}var nextSrc=(config.assetsBaseUrl||"")+(config.shaderFile||"")+(params.toString()?"?"+params.toString():"");iframe.style.filter=config.computedFilter||"none";iframe.style.mixBlendMode=config.shaderBlendMode||"normal";if(iframe.dataset.src!==nextSrc){iframe.dataset.src=nextSrc;iframe.src=nextSrc;}}catch(error){}});};if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",init,{once:true});}else{init();}}());',
				'after'
		);

		self::$legacy_view_script_enqueued = true;
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
		$invert_tone = isset( $attributes['invertTone'] ) ? (bool) $attributes['invertTone'] : false;
		$shader_blend_mode = isset( $attributes['shaderBlendMode'] ) ? sanitize_key( $attributes['shaderBlendMode'] ) : 'normal';
		$iframe_props = isset( $attributes['iframeProps'] ) ? self::get_iframe_props( $attributes['iframeProps'] ) : array();

		if ( ! isset( $schemes[ $scheme ] ) ) {
			$scheme = 'amber';
		}

		if ( ! in_array( $color_mode, self::get_color_modes(), true ) ) {
			$color_mode = 'preset';
		}

		if ( ! in_array( $shader_blend_mode, self::get_shader_blend_modes(), true ) ) {
			$shader_blend_mode = 'normal';
		}

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

		$target_color = 'theme' === $color_mode ? $theme_color_value : $schemes[ $scheme ];
		$computed_filter = self::build_shader_filter( $target_color, $invert_tone );
		$surface_color = self::build_shader_surface( $target_color );

		return array(
			'shader'          => $shader,
			'shaderId'        => $shader_id,
			'colorMode'       => $color_mode,
			'scheme'          => $scheme,
			'computedFilter'  => $computed_filter,
			'surfaceColor'    => $surface_color,
			'themeColorSlug'  => $theme_color_slug,
			'themeColorValue' => $theme_color_value,
			'invertTone'      => $invert_tone,
			'shaderBlendMode' => $shader_blend_mode,
			'iframeProps'     => $iframe_props,
			'params'          => $params,
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
		$iframe_src = ! empty( $config['iframeProps'] ) ? self::get_shader_src_url( $config ) : '';
		$use_static_iframe = '' !== $iframe_src;

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'radiant-shader-block',
				'style' => sprintf(
					'--radiant-shader-block-blend-mode:%1$s;',
					esc_attr( $config['shaderBlendMode'] ),
					esc_attr( $config['surfaceColor'] )
				),
			)
		);

		if ( ! $use_static_iframe ) {
			self::enqueue_legacy_view_script();
		}

		$payload = array(
			'shaderId'        => $config['shaderId'],
			'shaderFile'      => $config['shader']['file'],
			'colorMode'       => $config['colorMode'],
			'scheme'          => $config['scheme'],
			'computedFilter'  => $config['computedFilter'],
			'themeColorSlug'  => $config['themeColorSlug'],
			'themeColorValue' => $config['themeColorValue'],
			'invertTone'      => $config['invertTone'],
			'shaderBlendMode' => $config['shaderBlendMode'],
			'params'          => $config['params'],
			'assetsBaseUrl'   => RADIANT_SHADERS_BLOCK_URL . 'assets/radiant-static/',
		);
		ob_start();
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div
				class="radiant-shader-block__background"
				aria-hidden="true"
				style="<?php echo esc_attr( '--radiant-shader-block-surface:' . $config['surfaceColor'] . ';' ); ?>"
				<?php if ( ! $use_static_iframe ) : ?>
					data-wp-radiant-config="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
				<?php endif; ?>
			>
				<?php if ( $use_static_iframe && '' !== $iframe_src ) : ?>
					<iframe
						class="radiant-shader-block__iframe"
						title="<?php echo esc_attr( $config['shaderId'] ); ?>"
						loading="lazy"
						aria-hidden="true"
						tabindex="-1"
						allow="autoplay; fullscreen"
						style="<?php echo esc_attr( sprintf( 'filter:%1$s;mix-blend-mode:%2$s;', $config['iframeProps']['filter'], $config['iframeProps']['mixBlendMode'] ) ); ?>"
						src="<?php echo esc_url( $iframe_src ); ?>"
					></iframe>
				<?php endif; ?>
			</div>
			<div class="radiant-shader-block__overlay"></div>
			<div class="radiant-shader-block__content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
