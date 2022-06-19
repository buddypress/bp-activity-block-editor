/**
 * WordPress dependencies
 */
 const {
	domReady,
	element: {
		createElement,
		render,
		Fragment,
	},
	i18n: {
		__,
	},
	data: {
		useSelect,
		useDispatch,
	},
} = wp;

import IsolatedBlockEditor, { EditorLoaded } from '@automattic/isolated-block-editor';

const saveContent = ( html ) => {
	console.log( html );
};

const loadInitialContent = ( parse ) => {
	console.log( parse );
};

const setLoaded = ( container ) => {
	const closest = container.closest( '.iso-editor__loading' );

	if ( closest ) {
		closest.classList.remove( 'iso-editor__loading' );
	}
};

domReady( function() {
	const settings = window.bpGutenbergSettings || {};
	const container = document.querySelector( '#bp-gutenberg' );

	render(
		<IsolatedBlockEditor
			settings={ settings }
			onSaveContent={ ( html ) => saveContent( html ) }
			onLoad={ ( parse ) => loadInitialContent( parse ) }
			onError={ () => document.location.reload() }
		>
			<EditorLoaded onLoaded={ () => setLoaded( container ) } />
		</IsolatedBlockEditor>,
		container
	);
} );
