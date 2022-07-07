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
import ActivityPublishButton from './components/publish-button';
import ActivityUserAvatar from './components/user-avatar';

const ActivityEditor = ( { settings } ) => {
	const {
		editor: {
			activeComponents,
		}
	} = settings;
	const { setActiveComponents, updateContent, resetJustPostedActivity } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const activityCreated = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getJustPostedActivity();
	}, [] );

	// Set active components.
	setActiveComponents( activeComponents );

	if ( activityCreated && activityCreated.id ) {
		resetJustPostedActivity();
	}

	return (
		<IsolatedBlockEditor
			settings={ settings }
			onSaveContent={ ( html ) => updateContent( html ) }
			onError={ () => document.location.reload() }
		>
			<DocumentSection><h2>Activity</h2></DocumentSection>
			<FooterSlot>
				<ActivityPublishButton />
			</FooterSlot>
			<EditorHeadingSlot>
				<ActivityUserAvatar />
			</EditorHeadingSlot>
		</IsolatedBlockEditor>
	);
}

domReady( function() {
	const settings = window.bpGutenbergSettings || {};

	render( <ActivityEditor settings={ settings } />, document.querySelector( '#bp-gutenberg' ) );
} );
