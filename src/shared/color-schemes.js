export const BASELINE_AMBER = '#c8956c';

export const COLOR_SCHEMES = [
	{ label: 'Amber', value: 'amber', color: '#c8956c' },
	{ label: 'Mono', value: 'mono', color: '#a7a298' },
	{ label: 'Blue', value: 'blue', color: '#6ca7c8' },
	{ label: 'Rose', value: 'rose', color: '#d96c97' },
	{ label: 'Emerald', value: 'emerald', color: '#59b88d' },
	{ label: 'Arctic', value: 'arctic', color: '#8ebfd1' },
];

export const COLOR_SCHEME_MAP = Object.fromEntries(
	COLOR_SCHEMES.map( ( scheme ) => [ scheme.value, scheme ] )
);
