/**
 * WordPress dependencies.
 */
const {
	blocks: {
		parse,
	},
	data: {
		useSelect,
		useDispatch,
	},
	element: {
		createElement,
	},
	i18n: {
		__,
	},
} = wp;

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const ActivityUserFeedbacks = () => {
	const { resetJustPostedActivity, updateContent } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const { resetBlocks } = useDispatch( 'core/block-editor' );
	const { createSuccessNotice, createErrorNotice, removeNotice } = useDispatch( 'core/notices' );
	const getSettings = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSettings();
	}, [] );
	const activityCreated = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getJustPostedActivity();
	}, [] );

	const resetActivity = ( activity ) => {
		const blocks = parse( activity.content );
		resetBlocks( blocks );

		updateContent( activity.content );
		removeNotice( 'activity-posted-error' );
	};

	if ( activityCreated ) {
		if ( activityCreated.link ) {
			if ( getSettings.isActivityAdminScreen && true === getSettings.isActivityAdminScreen ) {
				createSuccessNotice( __( 'Activity successfully posted', 'bp-gutenberg' ), {
					isDismissible: true,
					actions: [ {
						label: __( 'View Activity', 'bp-gutenberg' ),
						url: activityCreated.link,
					} ],
				} );
			} else {
				activityCreated.message = __( 'View Activity', 'bp-gutenberg' );
				window.parent.postMessage( activityCreated, window.parent.location.href );
			}
		}

		if ( activityCreated.error ) {
			createErrorNotice( activityCreated.error, {
				id: 'activity-posted-error',
				isDismissible: true,
				actions: [ {
					label: __( 'Restore Activity content', 'bp-gutenberg' ),
					onClick: () => { resetActivity( activityCreated ); },
				} ],
			} );
		}

		if ( activityCreated.id ) {
			resetJustPostedActivity();
		}
	}

	return null;
};

export default ActivityUserFeedbacks;
