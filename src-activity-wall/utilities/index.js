/**
 * External dependencies
 */
const { template } = lodash;

/**
 * Uses the given template id to customize the output according to data.
 *
 * @since 1.0.0
 *
 * @param {string}  string The template ID.
 * @returns {string} The output.
 */
function setTemplate( tmpl ) {
	const options = {
		evaluate:    /<#([\s\S]+?)#>/g,
		interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
		escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
		variable:    'data'
	};

	return template( document.querySelector( '#tmpl-' + tmpl ).innerHTML, options );
}

export default setTemplate;

/**
 * Transform an HTML string into a list of HTML elements.
 *
 * @since 1.0.0
 *
 * @param {string}  string The HTML string to transform into HTML elements.
 * @param {boolean} text   Whether to return only the text or not.
 * @returns {HTMLCollection|string} The list of HTML elements or only the text.
 */
export const stringToElements = ( string, text ) => {
	const parser = new DOMParser();
	const elements = parser.parseFromString( string, 'text/html' );

	if ( !! text ) {
		return elements.documentElement.textContent;
	}

	return elements.body.firstChild;
}
