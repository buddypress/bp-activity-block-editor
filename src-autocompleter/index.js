import { renderToString } from '@wordpress/element';
import {
	create,
	getTextContent,
} from '@wordpress/rich-text';
import { addFilter } from '@wordpress/hooks';

const emojis = {
	name: 'emoji',
	triggerPrefix: ':',
	options: [
		{
			char: '&#x1F004;',
			name: 'mahjong',
		},
		{
			char: '&#x1F63D;',
			name: 'kissing cat',
		}
	],
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
	'bp-activity-block-editor/emojis',
	appendEmojiAutoCompleter,
	11
);
