import domReady from '@wordpress/dom-ready';

import { mountRadiantShaderFromDataset } from '../shared/runtime';

domReady( () => {
	document
		.querySelectorAll(
			'.radiant-shader-block__background[data-wp-radiant-config]'
		)
		.forEach( ( element ) => mountRadiantShaderFromDataset( element ) );
} );
