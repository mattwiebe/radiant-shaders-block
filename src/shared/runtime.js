import { buildShaderFilter, resolveSchemeColor } from './color-treatment';

export function buildRadiantShaderIframeProps( {
	colorMode,
	scheme,
	themeColorValue,
	invertTone,
	shaderBlendMode,
} ) {
	const targetColor =
		colorMode === 'theme' ? themeColorValue : resolveSchemeColor( scheme );

	return {
		filter: buildShaderFilter( targetColor, {
			invertTone: Boolean( invertTone ),
		} ),
		mixBlendMode: shaderBlendMode || 'normal',
	};
}

function applyShaderParams( iframe, params ) {
	if ( ! iframe?.contentWindow || ! params ) {
		return;
	}

	Object.entries( params ).forEach( ( [ name, value ] ) => {
		iframe.contentWindow.postMessage(
			{
				type: 'param',
				name,
				value,
			},
			'*'
		);
	} );
}

function syncIframePresentation() {}

function getShaderSrc( config ) {
	const baseUrl = config.assetsBaseUrl || '';
	const file = config.shaderFile || '';
	const params = new URLSearchParams();

	if ( config.params && Object.keys( config.params ).length ) {
		params.set( 'wp_radiant_params', JSON.stringify( config.params ) );
	}

	const query = params.toString();

	return query ? `${ baseUrl }${ file }?${ query }` : `${ baseUrl }${ file }`;
}

function resolveThemeColor( element, config ) {
	if ( config.themeColorValue ) {
		return config.themeColorValue;
	}

	if ( ! config.themeColorSlug || ! element ) {
		return '';
	}

	const styles = window.getComputedStyle(
		element.closest(
			'.wp-block-radiant-shaders-block-radiant-shader, .wp-block-wp-radiant-shaders-radiant-shader'
		) || document.documentElement
	);

	return styles
		.getPropertyValue( `--wp--preset--color--${ config.themeColorSlug }` )
		.trim();
}

function getShaderFilter( element, config ) {
	const targetColor =
		config.colorMode === 'theme'
			? resolveThemeColor( element, config )
			: resolveSchemeColor( config.scheme );

	return buildShaderFilter( targetColor, {
		invertTone: Boolean( config.invertTone ),
	} );
}

export function mountRadiantShader( element, config ) {
	if ( ! element || ! config?.shaderFile ) {
		return null;
	}

	let iframe = element.querySelector( 'iframe.radiant-shader-block__iframe' );

	if ( ! iframe ) {
		iframe = document.createElement( 'iframe' );
		iframe.className = 'radiant-shader-block__iframe';
		iframe.setAttribute( 'title', config.shaderId || 'Radiant shader' );
		iframe.setAttribute( 'loading', 'lazy' );
		iframe.setAttribute( 'aria-hidden', 'true' );
		iframe.setAttribute( 'tabindex', '-1' );
		iframe.setAttribute( 'allow', 'autoplay; fullscreen' );
		element.replaceChildren( iframe );
	}

	const nextSrc = getShaderSrc( config );
	const nextFilter = getShaderFilter( element, config );

	iframe.style.filter = nextFilter;
	iframe.style.mixBlendMode = config.shaderBlendMode || 'normal';

	if ( iframe.dataset.src !== nextSrc ) {
		iframe.dataset.src = nextSrc;
		iframe.onload = () => {
			syncIframePresentation( iframe, config );
			applyShaderParams( iframe, config.params );
		};
		iframe.src = nextSrc;
	} else {
		syncIframePresentation( iframe, config );
		applyShaderParams( iframe, config.params );
	}

	return iframe;
}

export function mountRadiantShaderFromDataset( element ) {
	if ( ! element ) {
		return null;
	}

	const rawConfig = element.getAttribute( 'data-wp-radiant-config' );

	if ( ! rawConfig ) {
		return null;
	}

	try {
		return mountRadiantShader( element, JSON.parse( rawConfig ) );
	} catch ( error ) {
		return null;
	}
}
