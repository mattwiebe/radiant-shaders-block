import fs from 'node:fs/promises';
import path from 'node:path';
import vm from 'node:vm';

const pluginRoot = process.cwd();
const radiantRoot = path.join( pluginRoot, 'node_modules', 'radiant-source' );
const shadersSourcePath = path.join( radiantRoot, 'src', 'lib', 'shaders.ts' );
const staticSourceDir = path.join( radiantRoot, 'static' );

const targetStaticDir = path.join( pluginRoot, 'assets', 'radiant-static' );
const targetMetadataPath = path.join(
	pluginRoot,
	'assets',
	'radiant-shaders.json'
);
const targetSharedMetadataPath = path.join(
	pluginRoot,
	'src',
	'shared',
	'radiant-shaders.json'
);

async function ensurePathExists( targetPath ) {
	try {
		await fs.access( targetPath );
	} catch ( error ) {
		throw new Error(
			`Missing expected Radiant dependency path: ${ targetPath }\nRun "npm install" before building.`
		);
	}
}

function extractShadersArrayLiteral( source ) {
	const marker = 'export const shaders: Shader[] = [';
	const markerIndex = source.indexOf( marker );

	if ( markerIndex === -1 ) {
		throw new Error(
			'Could not locate the Radiant shader metadata array.'
		);
	}

	const startIndex = markerIndex + marker.length - 1;
	let depth = 0;
	let inString = false;
	let stringQuote = '';

	for ( let index = startIndex; index < source.length; index += 1 ) {
		const character = source[ index ];
		const previousCharacter = source[ index - 1 ];

		if ( inString ) {
			if ( character === stringQuote && previousCharacter !== '\\' ) {
				inString = false;
			}
			continue;
		}

		if ( character === "'" || character === '"' || character === '`' ) {
			inString = true;
			stringQuote = character;
			continue;
		}

		if ( character === '[' ) {
			depth += 1;
		}

		if ( character === ']' ) {
			depth -= 1;

			if ( depth === 0 ) {
				return source.slice( startIndex, index + 1 );
			}
		}
	}

	throw new Error( 'Could not parse the Radiant shader metadata array.' );
}

async function copyStaticShaders() {
	const entries = await fs.readdir( staticSourceDir, {
		withFileTypes: true,
	} );

	await fs.rm( targetStaticDir, { recursive: true, force: true } );
	await fs.mkdir( targetStaticDir, { recursive: true } );

	await Promise.all(
		entries
			.filter(
				( entry ) => entry.isFile() && entry.name.endsWith( '.html' )
			)
			.map( async ( entry ) => {
				const sourcePath = path.join( staticSourceDir, entry.name );
				const targetPath = path.join( targetStaticDir, entry.name );
				const html = await fs.readFile( sourcePath, 'utf8' );
				const withoutLabelMarkup = html.replace(
					/<div class="label">[\s\S]*?<\/div>\s*/i,
					''
				);
				const withoutLabelStyles = withoutLabelMarkup.replace(
					/\s*\.label\s*\{[\s\S]*?\}\s*/i,
					'\n'
				);
				const bootScript =
					'<script>(function(){var search=new URLSearchParams(window.location.search);var rawParams=search.get("wp_radiant_params");if(!rawParams){return;}var params;try{params=JSON.parse(decodeURIComponent(rawParams));}catch(error){return;}function apply(){Object.entries(params).forEach(function(entry){window.postMessage({type:"param",name:entry[0],value:entry[1]},"*");});}apply();window.addEventListener("load",apply,{once:true});setTimeout(apply,60);}());</script>';
				const transformedHtml = withoutLabelStyles.includes( '</body>' )
					? withoutLabelStyles.replace(
							/<\/body>/i,
							`${ bootScript }</body>`
					  )
					: `${ withoutLabelStyles }\n${ bootScript }`;

				await fs.writeFile( targetPath, transformedHtml );
			} )
	);
}

async function writeShaderMetadata() {
	const source = await fs.readFile( shadersSourcePath, 'utf8' );
	const arrayLiteral = extractShadersArrayLiteral( source );
	const shaders = vm.runInNewContext( `( ${ arrayLiteral } )` );

	if ( ! Array.isArray( shaders ) || ! shaders.length ) {
		throw new Error( 'Parsed Radiant shader metadata was empty.' );
	}

	const json = `${ JSON.stringify( shaders, null, 2 ) }\n`;

	await fs.mkdir( path.dirname( targetMetadataPath ), { recursive: true } );
	await fs.mkdir( path.dirname( targetSharedMetadataPath ), {
		recursive: true,
	} );
	await fs.writeFile( targetMetadataPath, json );
	await fs.writeFile( targetSharedMetadataPath, json );
}

await ensurePathExists( radiantRoot );
await ensurePathExists( shadersSourcePath );
await ensurePathExists( staticSourceDir );

await copyStaticShaders();
await writeShaderMetadata();
