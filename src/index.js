/**
 * WordPress dependencies
 */
 const {
	domReady,
	element: {
		createElement,
		render,
	},
	i18n: {
		__,
	},
	data: {
		useSelect,
		useDispatch,
	},
	richText: {
		unregisterFormatType,
	}
} = wp;

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
		}
	} = settings;
	const { setActiveComponents, updateContent, initActivityEdits } = useDispatch( BP_ACTIVITY_STORE_KEY );
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
			initActivityEdits( activityEdit );

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
			<DocumentSection>
				<ActivitySidebar />
			</DocumentSection>
			<ActivityUserFeedbacks />
			<EditorHeadingSlot>
				<ActivityUserAvatar />
			</EditorHeadingSlot>
			<FooterSlot>
				<ActivityActionButtons />
			</FooterSlot>
		</IsolatedBlockEditor>
	);
}

domReady( function() {
	const settings = window.bpGutenbergSettings || {};

	// Remove some formatting buttons.
	['core/text-color', 'core/keyboard', 'core/subscript', 'core/superscript'].forEach( ( format ) => {
		unregisterFormatType( format );
	} );

	render( <ActivityEditor settings={ settings } />, document.querySelector( '#bp-gutenberg' ) );
} );
