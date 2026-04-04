import { COLOR_SCHEME_MAP } from './color-schemes';

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

function syncIframePresentation( iframe, config ) {
	if ( ! iframe?.contentDocument ) {
		return;
	}

	const document = iframe.contentDocument;
	let styleNode = document.getElementById(
		'wp-radiant-shader-inline-styles'
	);

	if ( ! styleNode ) {
		styleNode = document.createElement( 'style' );
		styleNode.id = 'wp-radiant-shader-inline-styles';
		document.head.appendChild( styleNode );
	}

	styleNode.textContent = config.showShaderLabel
		? ''
		: '.label{display:none !important;}';
}

function getShaderSrc( config ) {
	const baseUrl = config.assetsBaseUrl || '';
	const file = config.shaderFile || '';

	return `${ baseUrl }${ file }`;
}

export function mountRadiantShader( element, config ) {
	if ( ! element || ! config?.shaderFile ) {
		return null;
	}

	let iframe = element.querySelector( 'iframe.wp-radiant-shader__iframe' );

	if ( ! iframe ) {
		iframe = document.createElement( 'iframe' );
		iframe.className = 'wp-radiant-shader__iframe';
		iframe.setAttribute( 'title', config.shaderId || 'Radiant shader' );
		iframe.setAttribute( 'loading', 'lazy' );
		iframe.setAttribute( 'aria-hidden', 'true' );
		iframe.setAttribute( 'tabindex', '-1' );
		iframe.setAttribute( 'allow', 'autoplay; fullscreen' );
		element.replaceChildren( iframe );
	}

	const nextSrc = getShaderSrc( config );
	const nextFilter =
		config.colorMode === 'theme'
			? 'none'
			: COLOR_SCHEME_MAP[ config.scheme ]?.filter ??
			  config.schemeFilter ??
			  'none';

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
