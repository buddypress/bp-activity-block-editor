/**
 * WordPress dependencies
 */
import {
	createRoot,
	useCallback,
	useEffect,
	useRef,
} from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import {
	BlockCanvas,
	BlockEditorProvider,
} from '@wordpress/block-editor';
import {
	useSelect,
	useDispatch,
} from '@wordpress/data';
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
import ActionButtons from './components/action-buttons';
import SettingsPopover from './components/settings';
import { BP_ACTIVITY_STORE_KEY } from './store';

const Editor = ( { settings } ) => {
	const {
		activeComponents,
	} = settings;
	const { setActiveComponents, updateContent } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const availableComponents = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getActiveComponents();
	}, [] );
	const blocks = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getContent();
	}, [] );
	const documentRef = useRef( document );
	const togglePopover = useCallback( ( e ) => {
		if ( e.target.dataset.bpParentId ) {
			documentRef.current.querySelector( '#bp-activity-editor' ).classList.remove( 'popunder' );
		}
	}, [] );

	// Set active components.
	if ( ! availableComponents || availableComponents.length === 0 ) {
		setActiveComponents( activeComponents );
	}

	useEffect( () => {
		documentRef.current.addEventListener( 'click', togglePopover );

		return () => {
			documentRef.current.removeEventListener( 'click', togglePopover );
		};
	}, [] );

	return (
		<div className="activity-editor-ui">
			<SettingsPopover />
			<BlockEditorProvider
				value={ blocks }
				onInput={ ( blocks ) => updateContent( blocks ) }
				onChange={ ( blocks ) => updateContent( blocks ) }
				settings={ settings }
			>
				<BlockCanvas height="150px" styles={ styles } />
			</BlockEditorProvider>
			<ActionButtons />
		</div>
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
