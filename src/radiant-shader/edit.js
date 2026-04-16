import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	InspectorControls,
	BlockControls,
	AlignmentToolbar,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	BaseControl,
	ColorPicker,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

import shaders from '../shared/radiant-shaders.json';
import { COLOR_SCHEMES } from '../shared/color-schemes';
import {
	buildShaderFilter,
	resolveSchemeColor,
} from '../shared/color-treatment';
import { mountRadiantShader } from '../shared/runtime';

function getShader( shaderId ) {
	return shaders.find( ( shader ) => shader.id === shaderId ) || shaders[ 0 ];
}

function getShaderType( technique ) {
	return technique === 'webgl' ? '3d' : '2d';
}

function getFilteredShaderOptions( shaderType ) {
	return shaders
		.filter(
			( shader ) =>
				shaderType === 'all' ||
				getShaderType( shader.technique ) === shaderType
		)
		.map( ( shader ) => ( {
			label: `${ shader.title } [${ getShaderType(
				shader.technique
			).toUpperCase() }]`,
			value: shader.id,
		} ) );
}

function getDefaultParams( shader ) {
	return Object.fromEntries(
		( shader?.params || [] ).map( ( param ) => [
			param.name,
			param.default,
		] )
	);
}

function getThemePaletteOptions( colorSettings ) {
	if ( ! Array.isArray( colorSettings ) ) {
		return [];
	}

	const themeGroup = colorSettings.find(
		( entry ) =>
			entry?.origin === 'theme' &&
			Array.isArray( entry.colors ) &&
			entry.colors.length
	);

	const palette = themeGroup?.colors || colorSettings;

	return palette
		.filter(
			( entry ) =>
				entry?.slug &&
				entry?.name &&
				( entry?.origin === 'theme' || ! entry?.origin )
		)
		.map( ( entry ) => ( {
			label: entry.name,
			value: entry.slug,
			color: entry.color,
		} ) );
}

function getThemeColorValue( options, slug ) {
	return options.find( ( option ) => option.value === slug )?.color || '';
}

function normalizeHexColor( value ) {
	if ( ! value || typeof value !== 'string' ) {
		return '';
	}

	const hex = value.trim().replace( '#', '' ).toLowerCase();

	if ( hex.length === 3 ) {
		return `#${ hex
			.split( '' )
			.map( ( char ) => `${ char }${ char }` )
			.join( '' ) }`;
	}

	if ( hex.length === 6 ) {
		return `#${ hex }`;
	}

	return value.trim().toLowerCase();
}

const SHADER_BLEND_MODE_OPTIONS = [
	{ label: 'Normal', value: 'normal' },
	{ label: 'Multiply', value: 'multiply' },
	{ label: 'Screen', value: 'screen' },
	{ label: 'Overlay', value: 'overlay' },
	{ label: 'Soft Light', value: 'soft-light' },
	{ label: 'Hard Light', value: 'hard-light' },
	{ label: 'Difference', value: 'difference' },
	{ label: 'Exclusion', value: 'exclusion' },
	{ label: 'Luminosity', value: 'luminosity' },
];

const COLOR_MODE_OPTIONS = [
	{ label: 'Preset', value: 'preset' },
	{ label: 'Theme Color', value: 'theme' },
];

const SHADER_TYPE_OPTIONS = [
	{ label: 'All', value: 'all' },
	{ label: '2D', value: '2d' },
	{ label: '3D', value: '3d' },
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		align,
		shaderId,
		colorMode,
		scheme,
		themeColorSlug,
		themeColorValue,
		showShaderLabel,
		invertTone,
		shaderBlendMode,
		params,
	} = attributes;
	const shader = useMemo( () => getShader( shaderId ), [ shaderId ] );
	const [ shaderType, setShaderType ] = useState( 'all' );
	const previewRef = useRef( null );
	const assetsBaseUrl = window.WPRadiantShadersBlock?.assetsBaseUrl || '';
	const themePaletteOptions = useSelect(
		( select ) =>
			getThemePaletteOptions(
				select( blockEditorStore ).getSettings()?.colors
			),
		[]
	);
	const resolvedThemeColorValue = getThemeColorValue(
		themePaletteOptions,
		themeColorSlug
	);
	const effectiveThemeColorValue =
		themeColorValue || resolvedThemeColorValue || '';
	const hasCustomThemeColor =
		colorMode === 'theme' &&
		themeColorSlug &&
		effectiveThemeColorValue &&
		normalizeHexColor( effectiveThemeColorValue ) !==
			normalizeHexColor( resolvedThemeColorValue );
	const targetShaderColor =
		colorMode === 'theme'
			? effectiveThemeColorValue
			: resolveSchemeColor( scheme );
	const computedShaderFilter = buildShaderFilter( targetShaderColor, {
		invertTone,
	} );
	const shaderOptions = useMemo(
		() => getFilteredShaderOptions( shaderType ),
		[ shaderType ]
	);

	useEffect( () => {
		const defaultParams = getDefaultParams( shader );
		const nextKeys = Object.keys( defaultParams );
		const hasMismatch =
			nextKeys.length !== Object.keys( params || {} ).length ||
			nextKeys.some( ( key ) => typeof params?.[ key ] !== 'number' );

		if ( hasMismatch ) {
			setAttributes( { params: defaultParams } );
		}
	}, [ shader, params, setAttributes ] );

	useEffect( () => {
		if ( colorMode !== 'theme' || ! themeColorSlug ) {
			return;
		}

		if ( resolvedThemeColorValue && ! themeColorValue ) {
			setAttributes( { themeColorValue: resolvedThemeColorValue } );
		}
	}, [
		colorMode,
		themeColorSlug,
		themeColorValue,
		resolvedThemeColorValue,
		setAttributes,
	] );

	useEffect( () => {
		if ( ! previewRef.current ) {
			return;
		}

		mountRadiantShader( previewRef.current, {
			shaderId: shader.id,
			shaderFile: shader.file,
			colorMode,
			scheme,
			themeColorSlug,
			themeColorValue,
			showShaderLabel,
			invertTone,
			shaderBlendMode,
			params: params || getDefaultParams( shader ),
			assetsBaseUrl,
		} );
	}, [
		shader,
		colorMode,
		scheme,
		themeColorSlug,
		themeColorValue,
		showShaderLabel,
		invertTone,
		shaderBlendMode,
		params,
		assetsBaseUrl,
	] );

	const blockProps = useBlockProps( {
		className: 'wp-radiant-shader',
		style: {
			'--wp-radiant-shader-blend-mode': shaderBlendMode,
			'--wp-radiant-surface':
				colorMode === 'theme' && effectiveThemeColorValue
					? `color-mix(in srgb, ${ effectiveThemeColorValue } 18%, #120804)`
					: '#120804',
		},
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wp-radiant-shader__content',
		},
		{
			templateLock: false,
		}
	);

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( nextAlign ) =>
						setAttributes( { align: nextAlign } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Shader', 'wp-radiant-shaders' ) }
					initialOpen
				>
					<SelectControl
						label={ __( 'Shader type', 'wp-radiant-shaders' ) }
						value={ shaderType }
						options={ SHADER_TYPE_OPTIONS }
						onChange={ ( value ) => setShaderType( value ) }
					/>
					<SelectControl
						label={ __( 'Shader', 'wp-radiant-shaders' ) }
						value={ shader.id }
						options={ shaderOptions }
						onChange={ ( value ) => {
							const nextShader = getShader( value );
							setAttributes( {
								shaderId: value,
								params: getDefaultParams( nextShader ),
							} );
						} }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Color', 'wp-radiant-shaders' ) }
					initialOpen={ false }
				>
					<SelectControl
						label={ __( 'Color source', 'wp-radiant-shaders' ) }
						value={ colorMode }
						options={ COLOR_MODE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { colorMode: value } )
						}
					/>
					{ colorMode === 'preset' && (
						<SelectControl
							label={ __(
								'Preset palette',
								'wp-radiant-shaders'
							) }
							value={ scheme }
							options={ COLOR_SCHEMES.map( ( option ) => ( {
								label: option.label,
								value: option.value,
							} ) ) }
							onChange={ ( value ) =>
								setAttributes( { scheme: value } )
							}
							help={ computedShaderFilter }
						/>
					) }
					{ colorMode === 'theme' && (
						<SelectControl
							label={ __( 'Theme color', 'wp-radiant-shaders' ) }
							value={
								hasCustomThemeColor
									? '__custom__'
									: themeColorSlug
							}
							options={ [
								{
									label: __(
										'Choose a color',
										'wp-radiant-shaders'
									),
									value: '',
								},
								...( hasCustomThemeColor
									? [
											{
												label: __(
													'Custom',
													'wp-radiant-shaders'
												),
												value: '__custom__',
											},
									  ]
									: [] ),
								...themePaletteOptions,
							] }
							onChange={ ( value ) => {
								if ( value === '__custom__' ) {
									return;
								}

								setAttributes( {
									themeColorSlug: value,
									themeColorValue:
										getThemeColorValue(
											themePaletteOptions,
											value
										) || '',
								} );
							} }
							help={
								themePaletteOptions.length
									? __(
											'Uses the selected theme palette color as the basis for shader hue rotation.',
											'wp-radiant-shaders'
									  )
									: __(
											'No theme palette colors were detected for this site.',
											'wp-radiant-shaders'
									  )
							}
						/>
					) }
					{ colorMode === 'theme' &&
						themeColorSlug &&
						effectiveThemeColorValue && (
							<BaseControl
								id="wp-radiant-shader-theme-color-value"
								label={ __(
									'Dialed-in color',
									'wp-radiant-shaders'
								) }
								help={ __(
									'Starts from the selected theme color, then lets you tune it.',
									'wp-radiant-shaders'
								) }
							>
								<ColorPicker
									color={ effectiveThemeColorValue }
									enableAlpha={ false }
									onChange={ ( value ) =>
										setAttributes( {
											themeColorValue: value,
										} )
									}
								/>
							</BaseControl>
						) }
					<ToggleControl
						label={ __( 'Invert tone', 'wp-radiant-shaders' ) }
						checked={ invertTone }
						onChange={ ( value ) =>
							setAttributes( { invertTone: value } )
						}
						help={ __(
							'Applies invert plus 180-degree hue swapping before the computed color treatment.',
							'wp-radiant-shaders'
						) }
					/>
					<SelectControl
						label={ __(
							'Shader blend mode',
							'wp-radiant-shaders'
						) }
						value={ shaderBlendMode }
						options={ SHADER_BLEND_MODE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { shaderBlendMode: value } )
						}
						help={ __(
							'Blends the shader layer against the block surface.',
							'wp-radiant-shaders'
						) }
					/>
					<BaseControl
						id="wp-radiant-shader-computed-filter"
						label={ __( 'Computed filter', 'wp-radiant-shaders' ) }
						help={ computedShaderFilter }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Parameters', 'wp-radiant-shaders' ) }
					initialOpen
				>
					{ shader.params?.length ? (
						shader.params.map( ( param ) => (
							<RangeControl
								key={ param.name }
								label={ param.label }
								value={
									params?.[ param.name ] ?? param.default
								}
								min={ param.min }
								max={ param.max }
								step={ param.step || 0.1 }
								onChange={ ( value ) =>
									setAttributes( {
										params: {
											...params,
											[ param.name ]: value,
										},
									} )
								}
							/>
						) )
					) : (
						<BaseControl
							help={ __(
								'This shader does not expose adjustable parameters.',
								'wp-radiant-shaders'
							) }
						/>
					) }
					<ToggleControl
						label={ __( 'Show shader name', 'wp-radiant-shaders' ) }
						checked={ showShaderLabel }
						onChange={ ( value ) =>
							setAttributes( { showShaderLabel: value } )
						}
						help={ __(
							'Toggles the title label rendered inside the shader iframe.',
							'wp-radiant-shaders'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div
					ref={ previewRef }
					className="wp-radiant-shader__background"
					aria-hidden="true"
				/>
				<div className="wp-radiant-shader__overlay" />
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
