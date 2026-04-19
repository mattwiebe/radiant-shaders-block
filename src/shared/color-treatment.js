import { BASELINE_AMBER, COLOR_SCHEME_MAP } from './color-schemes';

function clamp( value, min, max ) {
	return Math.min( Math.max( value, min ), max );
}

function normalizeHex( value ) {
	const hex = value.replace( '#', '' ).trim();

	if ( hex.length === 3 ) {
		return `#${ hex
			.split( '' )
			.map( ( char ) => `${ char }${ char }` )
			.join( '' ) }`;
	}

	if ( hex.length === 6 ) {
		return `#${ hex }`;
	}

	return '';
}

function parseRgbColor( value ) {
	const match = value.match(
		/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*[\d.]+\s*)?\)/i
	);

	if ( ! match ) {
		return null;
	}

	return {
		r: clamp( Number( match[ 1 ] ), 0, 255 ),
		g: clamp( Number( match[ 2 ] ), 0, 255 ),
		b: clamp( Number( match[ 3 ] ), 0, 255 ),
	};
}

function hueToRgb( p, q, t ) {
	let next = t;

	if ( next < 0 ) {
		next += 1;
	}

	if ( next > 1 ) {
		next -= 1;
	}

	if ( next < 1 / 6 ) {
		return p + ( q - p ) * 6 * next;
	}

	if ( next < 1 / 2 ) {
		return q;
	}

	if ( next < 2 / 3 ) {
		return p + ( q - p ) * ( 2 / 3 - next ) * 6;
	}

	return p;
}

function parseHslColor( value ) {
	const match = value.match(
		/hsla?\(\s*([\d.]+)(?:deg)?\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%(?:\s*,\s*[\d.]+\s*)?\)/i
	);

	if ( ! match ) {
		return null;
	}

	const h = ( Number( match[ 1 ] ) % 360 ) / 360;
	const s = clamp( Number( match[ 2 ] ) / 100, 0, 1 );
	const l = clamp( Number( match[ 3 ] ) / 100, 0, 1 );

	if ( s === 0 ) {
		const channel = Math.round( l * 255 );

		return {
			r: channel,
			g: channel,
			b: channel,
		};
	}

	const q = l < 0.5 ? l * ( 1 + s ) : l + s - l * s;
	const p = 2 * l - q;

	return {
		r: Math.round( hueToRgb( p, q, h + 1 / 3 ) * 255 ),
		g: Math.round( hueToRgb( p, q, h ) * 255 ),
		b: Math.round( hueToRgb( p, q, h - 1 / 3 ) * 255 ),
	};
}

export function parseCssColor( value ) {
	if ( ! value || typeof value !== 'string' ) {
		return null;
	}

	const trimmed = value.trim();

	if ( trimmed.startsWith( '#' ) ) {
		const normalized = normalizeHex( trimmed );

		if ( ! normalized ) {
			return null;
		}

		return {
			r: Number.parseInt( normalized.slice( 1, 3 ), 16 ),
			g: Number.parseInt( normalized.slice( 3, 5 ), 16 ),
			b: Number.parseInt( normalized.slice( 5, 7 ), 16 ),
		};
	}

	if ( trimmed.startsWith( 'rgb' ) ) {
		return parseRgbColor( trimmed );
	}

	if ( trimmed.startsWith( 'hsl' ) ) {
		return parseHslColor( trimmed );
	}

	return null;
}

function rgbToHsl( { r, g, b } ) {
	const red = r / 255;
	const green = g / 255;
	const blue = b / 255;
	const max = Math.max( red, green, blue );
	const min = Math.min( red, green, blue );
	const lightness = ( max + min ) / 2;
	const delta = max - min;

	if ( delta === 0 ) {
		return { h: 0, s: 0, l: lightness };
	}

	const saturation =
		lightness > 0.5 ? delta / ( 2 - max - min ) : delta / ( max + min );

	let hue = 0;

	switch ( max ) {
		case red:
			hue = ( green - blue ) / delta + ( green < blue ? 6 : 0 );
			break;
		case green:
			hue = ( blue - red ) / delta + 2;
			break;
		default:
			hue = ( red - green ) / delta + 4;
			break;
	}

	return {
		h: hue * 60,
		s: saturation,
		l: lightness,
	};
}

function getHueDelta( fromHue, toHue ) {
	return ( ( toHue - fromHue + 540 ) % 360 ) - 180;
}

export function buildShaderFilter( targetColor, { invertTone = false } = {} ) {
	const baselineRgb = parseCssColor( BASELINE_AMBER );
	const targetRgb = parseCssColor( targetColor );

	if ( ! baselineRgb || ! targetRgb ) {
		return invertTone ? 'invert(1) hue-rotate(180deg)' : 'none';
	}

	const baselineHsl = rgbToHsl( baselineRgb );
	const targetHsl = rgbToHsl( targetRgb );
	const hueDelta = getHueDelta( baselineHsl.h, targetHsl.h );
	const saturationRatio = clamp(
		targetHsl.s / Math.max( baselineHsl.s, 0.01 ),
		0,
		2.5
	);
	const brightnessRatio = clamp(
		targetHsl.l / Math.max( baselineHsl.l, 0.01 ),
		0.75,
		1.35
	);
	const parts = [];

	if ( invertTone ) {
		parts.push( 'invert(1)', 'hue-rotate(180deg)' );
	}

	if ( Math.abs( hueDelta ) > 0.5 ) {
		parts.push( `hue-rotate(${ Math.round( hueDelta ) }deg)` );
	}

	if ( Math.abs( saturationRatio - 1 ) > 0.02 ) {
		parts.push( `saturate(${ saturationRatio.toFixed( 2 ) })` );
	}

	if ( Math.abs( brightnessRatio - 1 ) > 0.03 ) {
		parts.push( `brightness(${ brightnessRatio.toFixed( 2 ) })` );
	}

	return parts.length ? parts.join( ' ' ) : 'none';
}

export function buildShaderSurface( targetColor, mixRatio = 0.18 ) {
	const baseRgb = parseCssColor( '#120804' );
	const targetRgb = parseCssColor( targetColor );

	if ( ! baseRgb || ! targetRgb ) {
		return '#120804';
	}

	const blend = {
		r: Math.round( targetRgb.r * mixRatio + baseRgb.r * ( 1 - mixRatio ) ),
		g: Math.round( targetRgb.g * mixRatio + baseRgb.g * ( 1 - mixRatio ) ),
		b: Math.round( targetRgb.b * mixRatio + baseRgb.b * ( 1 - mixRatio ) ),
	};

	return `rgb(${ blend.r }, ${ blend.g }, ${ blend.b })`;
}

export function resolveSchemeColor( scheme ) {
	return COLOR_SCHEME_MAP[ scheme ]?.color || BASELINE_AMBER;
}
