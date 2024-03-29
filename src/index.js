/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import {
	useSelect,
	useDispatch,
} from '@wordpress/data';
import { unregisterFormatType } from '@wordpress/rich-text';

/**
 * Automattic dependency.
 */
import IsolatedBlockEditor, { EditorHeadingSlot, DocumentSection, FooterSlot } from '@automattic/isolated-block-editor';

/**
 * Internal dependency.
 */
import './style.scss';
import { BP_ACTIVITY_STORE_KEY } from './store';
import ActivitySidebar from './components/sidebar';
import ActivityActionButtons from './components/action-buttons';
import ActivityUserAvatar from './components/user-avatar';
import ActivityUserFeedbacks from './components/user-feedback';

const ActivityEditor = ( { settings } ) => {
	const {
		editor: {
			activeComponents,
			activityEdit,
			parentActivity,
		}
	} = settings;
	const { setActiveComponents, updateContent, updateActivityEdits } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const availableComponents = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getActiveComponents();
	}, [] );

	// Set active components.
	if ( ! availableComponents || availableComponents.length === 0 ) {
		setActiveComponents( activeComponents );
	}

	const loadInitialContent = ( parse ) => {
		let content = '';
		if ( null !== activityEdit && activityEdit.content ) {
			activityEdit.blocks = parse( activityEdit.content );
			updateActivityEdits( activityEdit );

			content = activityEdit.blocks;
		}

		return content;
	}

	return (
		<IsolatedBlockEditor
			settings={ settings }
			onSaveContent={ ( html ) => updateContent( html ) }
			onLoad={ ( parse ) => loadInitialContent( parse ) }
			onError={ () => document.location.reload() }
		>
			{ ! parentActivity && (
				<DocumentSection>
					<ActivitySidebar />
				</DocumentSection>
			) }
			<ActivityUserFeedbacks />
			<EditorHeadingSlot>
				<ActivityUserAvatar />
			</EditorHeadingSlot>
			<FooterSlot>
				<ActivityActionButtons parentActivity={ parentActivity } />
			</FooterSlot>
		</IsolatedBlockEditor>
	);
}

domReady( function() {
	const target = document.querySelector( '#bp-activity-block-editor' )
	const root = createRoot( target );
	const settings = window.bpActivityBlockEditor || {};

	// Remove some formatting buttons.
	['core/text-color', 'core/keyboard', 'core/subscript', 'core/superscript'].forEach( ( format ) => {
		unregisterFormatType( format );
	} );

	root.render( <ActivityEditor settings={ settings } /> );
} );
