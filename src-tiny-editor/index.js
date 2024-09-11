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
	const styles = [
		{
			css: `
			body {
				font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen-Sans, Ubuntu, Cantarell, Helvetica Neue, sans-serif;
				font-size: 14px;
				line-height: 1.55;
			}

			.components-button svg { fill: currentColor }
			.block-editor-inserter__toggle.components-button {
				align-items: center;
				border: none;
				cursor: pointer;
				display: inline-flex;
				outline: none;
				padding: 0;
				transition: color .2s ease;
			}
			.components-tooltip {
				background: #000;
				border-radius: 2px;
				color: #f0f0f0;
				font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Oxygen-Sans, Ubuntu, Cantarell, Helvetica Neue, sans-serif;
				font-size: 12px;
				line-height: 1.4;
				padding: 4px 8px;
				text-align: center;
				z-index: 1000002;
			}
			`,
		}
	];

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
