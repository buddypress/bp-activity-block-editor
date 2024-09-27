/**
 * WordPress dependencies
 */
import {
	createRoot,
	unmount,
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
import UserFeedbacks from './components/feedbacks';
import { BP_ACTIVITY_STORE_KEY } from './store';

const Editor = ( { settings } ) => {
	const {
		activeComponents,
	} = settings;
	const {
		setActiveComponents,
		updateContent,
		resetActivity,
		resetJustPostedActivity,
	} = useDispatch( BP_ACTIVITY_STORE_KEY );
	const availableComponents = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getActiveComponents();
	}, [] );
	const blocks = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getContent();
	}, [] );
	const postedActivity = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getJustPostedActivity();
	}, [] );
	const documentRef = useRef( document );
	const togglePopover = useCallback( ( e ) => {
		if ( e.target.dataset.bpParentId ) {
			documentRef.current.querySelector( '#bp-activity-editor' ).classList.remove( 'popunder' );
			documentRef.current.querySelector( '#bp-activity-post-form' ).classList.add( 'popunder' );
		}
	}, [] );

	// Set active components.
	if ( ! availableComponents || availableComponents.length === 0 ) {
		setActiveComponents( activeComponents );
	}

	useEffect( () => {
		documentRef.current.querySelector( '#bp-activity-post-form' ).classList.remove( 'activity-editor-not-supported' );
		documentRef.current.addEventListener( 'click', togglePopover );

		return () => {
			documentRef.current.removeEventListener( 'click', togglePopover );
		};
	}, [] );

	const resetEditor = () => {
		resetActivity();
		resetJustPostedActivity();
		reInitializeActivityEditor();
		documentRef.current.querySelector( '#bp-activity-editor' ).classList.add( 'popunder' );
		documentRef.current.querySelector( '#bp-activity-post-form' ).classList.remove( 'popunder' );
	}

	useEffect( () => {
		if ( postedActivity.link ) {
			resetActivity();
			resetJustPostedActivity();
		}
	}, [ postedActivity ] );

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
			<ActionButtons onReset={ resetEditor } />
			<UserFeedbacks />
		</div>
	);
}

// We need to edit root.
let root;

/**
 * Generate the Actvitiiy Block Editor.
 *
 * @since 1.0.2
 *
 * @param {boolean} register True to register format & blocks. False otherwise.
 */
const initializeActivityEditor = ( register = true ) => {
	const target = document.querySelector( '#bp-activity-editor' );

	// Create a node after the textarea
	const activityEditor = document.createElement( 'div' );
	activityEditor.classList.add( 'block-editor' );

	target.append( activityEditor );

	root = createRoot( activityEditor );
	const settings = window.bpActivityEditor || {};

	if ( register ) {
		setDefaultActivityBlocks();
		setDefaultActivityFormats();
	}

	root.render( <Editor settings={ settings } /> );
}
domReady( function() { initializeActivityEditor(); } );

/**
 * Recreate root once unmounted.
 *
 * Block Editor's resetBlocks() has no effect on Block Canvas.
 *
 * To make sure the blocks added to the editor are removed once the user clicked
 * on the "Cancel" or "Post update" buttons, we need to unmount and recreate root.
 *
 * @since 1.0.2
 */
const reInitializeActivityEditor = () => {
	const target = document.querySelector( '#bp-activity-editor .block-editor' );
	root.unmount();
	target.remove();

	initializeActivityEditor( false );
}

addFilter(
	'blocks.registerBlockType',
	'bp/activity/disableSupport',
	disableBlockSupports
);
