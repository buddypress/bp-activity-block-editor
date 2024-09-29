import { registerCoreBlocks } from '@wordpress/block-library';
import {
	getBlockTypes,
	unregisterBlockType,
} from '@wordpress/blocks';
import '@wordpress/format-library';
import { unregisterFormatType } from '@wordpress/rich-text';

export const setDefaultActivityBlocks = () => {
	registerCoreBlocks();

	const allowedList = [
		'core/paragraph',
		'core/missing',
		'core/block',
	];

	getBlockTypes().forEach( ( { name } ) => {
		if ( -1 === allowedList.indexOf( name ) ) {
			unregisterBlockType( name );
		}
	} );
}

export const setDefaultActivityFormats = () => {
	// Remove some formatting buttons.
	['core/text-color', 'core/keyboard', 'core/subscript', 'core/superscript', 'core/language', 'core/strikethrough'].forEach( ( format ) => {
		unregisterFormatType( format );
	} );
}

export const disableBlockSupports = ( settings ) => {
	return {
        ...settings,
        supports: {
            ...settings.supports,
            color: false,
			typography: false,
        },
    };
}
