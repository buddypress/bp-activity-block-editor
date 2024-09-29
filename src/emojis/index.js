import { renderToString } from '@wordpress/element';
import {
	create,
	getTextContent,
} from '@wordpress/rich-text';
import { addFilter } from '@wordpress/hooks';
import apiFetch from '@wordpress/api-fetch';

const emojis = {
	name: 'emoji',
	triggerPrefix: ':',
	options( search ) {
		let payload = '';
		if ( search ) {
			payload = '?search=' + encodeURIComponent( search );
		}
		return apiFetch( { path: '/buddypress/v2/activity-emojis' + payload } );
	},
	isDebounced: true,
	getOptionLabel: ( option ) => {
		const emoji = getTextContent( create( { html: renderToString( option.char ) } ) );
		return `${ emoji } ${ option.name }`;
	},
	getOptionKeywords: ( option ) => [ option.name ],
	getOptionCompletion: ( option ) => {
		const emoji = getTextContent( create( { html: renderToString( option.char ) } ) );
		return emoji;
	}
};

const appendEmojiAutoCompleter = ( completers, blockName ) => {
	return blockName === 'core/paragraph' ? [ ...completers, emojis ] : completers;
};

addFilter(
	'editor.Autocomplete.completers',
	'bp-activity/emojis',
	appendEmojiAutoCompleter,
	11
);
