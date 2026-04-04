export const COLOR_SCHEMES = [
	{ label: 'Amber', value: 'amber', filter: 'none' },
	{ label: 'Mono', value: 'mono', filter: 'grayscale(1)' },
	{ label: 'Blue', value: 'blue', filter: 'hue-rotate(175deg)' },
	{
		label: 'Rose',
		value: 'rose',
		filter: 'hue-rotate(300deg) saturate(1.1)',
	},
	{
		label: 'Emerald',
		value: 'emerald',
		filter: 'hue-rotate(90deg) saturate(1.2)',
	},
	{
		label: 'Arctic',
		value: 'arctic',
		filter: 'hue-rotate(180deg) saturate(0.5) brightness(1.1)',
	},
];

export const COLOR_SCHEME_MAP = Object.fromEntries(
	COLOR_SCHEMES.map( ( scheme ) => [ scheme.value, scheme ] )
);
