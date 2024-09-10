/**
 * WordPress dependencies
 */
import { createRoot, useState } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import {
	BlockCanvas,
	BlockEditorProvider,
} from '@wordpress/block-editor';
import { registerCoreBlocks } from '@wordpress/block-library';
import '@wordpress/format-library';
import { unregisterFormatType } from '@wordpress/rich-text';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependency.
 */
import './styles.scss';

const Editor = ( { settings } ) => {
	const [ blocks, updateBlocks ] = useState( [] );

	return (
		<BlockEditorProvider
			value={ blocks }
			onInput={ ( blocks ) => updateBlocks( blocks ) }
			onChange={ ( blocks ) => updateBlocks( blocks ) }
			settings={ settings }
		>
			<BlockCanvas height="400px" />
		</BlockEditorProvider>
	);
}

const disableBlockSupport = ( settings ) => {
	return {
        ...settings,
        supports: {
            ...settings.supports,
            color: false,
			typography: false,
        },
    };
}

addFilter(
	'blocks.registerBlockType',
	'bp/activity/disableSupport',
	disableBlockSupport
);

domReady( function() {
	const target = document.querySelector( '#bp-activity-editor' )
	const root = createRoot( target );
	const settings = window.bpActivityEditor || {};

	registerCoreBlocks();

	// Remove some formatting buttons.
	['core/text-color', 'core/keyboard', 'core/subscript', 'core/superscript', 'core/language', 'core/strikethrough'].forEach( ( format ) => {
		unregisterFormatType( format );
	} );

	root.render( <Editor settings={ settings } /> );
} );
