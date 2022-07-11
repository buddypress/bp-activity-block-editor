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
	components: {
		Notice,
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
import ActivityElementPortal from './portal';

/**
 * Manage notices display.
 *
 * NB: we are not using `createSuccessNotice` or `createErrorNotice` to avoid a
 * rendering error.
 *
 * @returns A Notice React Element or null.
 */
const ActivityUserFeedbacks = () => {
	const { updateContent } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const { resetBlocks } = useDispatch( 'core/block-editor' );
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
	};

	if ( activityCreated.link ) {
		if ( getSettings.isActivityAdminScreen && true === getSettings.isActivityAdminScreen ) {
			return (
				<ActivityElementPortal>
					<Notice
						status="success"
						isDismissible={ false }
						actions={
							[
								{
									label: __( 'View Activity', 'bp-gutenberg' ),
									url: activityCreated.link,
								}
							]
						}
					>
						<p>{ __( 'Activity successfully posted', 'bp-gutenberg' ) }</p>
					</Notice>
				</ActivityElementPortal>
			);
		} else {
			activityCreated.message = __( 'View Activity', 'bp-gutenberg' );
			window.parent.postMessage( activityCreated, window.parent.location.href );
		}
	} else if ( activityCreated.error ) {
		return (
			<ActivityElementPortal>
				<Notice
					status="error"
					isDismissible={ false }
					actions={
						[
							{
								label: __( 'Restore Activity content', 'bp-gutenberg' ),
								onClick: () => { resetActivity( activityCreated ); },
							}
						]
					}
				>
					<p>{ activityCreated.error }</p>
				</Notice>
			</ActivityElementPortal>
		);
	} else {
		return null;
	}
};

export default ActivityUserFeedbacks;
