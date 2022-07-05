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
} = wp;

/**
 * Automattic dependency.
 */
import IsolatedBlockEditor, { ToolbarSlot } from '@automattic/isolated-block-editor';

/**
 * Internal dependency.
 */
import './style.scss';
import { BP_ACTIVITY_STORE_KEY } from './store';
import ActivityPublishButton from './components/publish-button';

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
			<ToolbarSlot>
				<ActivityPublishButton />
			</ToolbarSlot>
		</IsolatedBlockEditor>
	);
}

domReady( function() {
	const settings = window.bpGutenbergSettings || {};

	render( <ActivityEditor settings={ settings } />, document.querySelector( '#bp-gutenberg' ) );
} );
