/**
 * WordPress dependencies
 */
import { createRoot, useState } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import {
	BlockCanvas,
	BlockEditorProvider,
} from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependency.
 */
import './styles/index.scss';
import { styles } from './styles/iframe-styles'
import {
	setDefaultActivityBlocks,
	setDefaultActivityFormats,
	disableBlockSupports,
} from './omissions';

const Editor = ( { settings } ) => {
	const [ blocks, updateBlocks ] = useState( [] );

	return (
		<BlockEditorProvider
			value={ blocks }
			onInput={ ( blocks ) => updateBlocks( blocks ) }
			onChange={ ( blocks ) => updateBlocks( blocks ) }
			settings={ settings }
		>
			<BlockCanvas height="400px" styles={ styles } />
		</BlockEditorProvider>
	);
}

domReady( function() {
	const target = document.querySelector( '#bp-activity-editor' )
	const root = createRoot( target );
	const settings = window.bpActivityEditor || {};

	setDefaultActivityBlocks();
	setDefaultActivityFormats();

	root.render( <Editor settings={ settings } /> );
} );

addFilter(
	'blocks.registerBlockType',
	'bp/activity/disableSupport',
	disableBlockSupports
);
