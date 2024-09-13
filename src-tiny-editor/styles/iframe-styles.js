/**
 * When first opened the styles are not loaded into the Block Canvas. These style rules are making sure
 * the display is consistent between initial layout and once the blocks list starts to be populated.
 */
export const styles = [
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
