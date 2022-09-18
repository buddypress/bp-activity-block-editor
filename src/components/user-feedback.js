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
	const { activityPosted, activityEdits } = useSelect( ( select ) => {
		const store = select( BP_ACTIVITY_STORE_KEY );

		return {
			activityPosted: store.getJustPostedActivity(),
			activityEdits: store.getActivityEdits(),
		};
	}, [] );

	const resetActivity = ( activity ) => {
		const blocks = parse( activity.content );
		resetBlocks( blocks );

		updateContent( activity.content );
	};

	if ( activityPosted.link ) {
		if ( getSettings.isActivityAdminScreen && true === getSettings.isActivityAdminScreen ) {
			return (
				<ActivityElementPortal>
					<Notice
						status="success"
						isDismissible={ false }
						actions={
							[
								{
									label: __( 'View Activity', 'bp-activity-block-editor' ),
									url: activityPosted.link,
								}
							]
						}
					>
						<p>{ !! activityEdits.blocks ? __( 'Activity successfully updated', 'bp-activity-block-editor' )  : __( 'Activity successfully posted', 'bp-activity-block-editor' ) }</p>
					</Notice>
				</ActivityElementPortal>
			);
		} else {
			activityPosted.message = __( 'View Activity', 'bp-activity-block-editor' );
			window.parent.postMessage( activityPosted, window.parent.location.href );
		}
	} else if ( activityPosted.error ) {
		let errorActions = [];
		if ( ! activityEdits.blocks ) {
			errorActions = [
				{
					label: __( 'Restore Activity content', 'bp-activity-block-editor' ),
					onClick: () => { resetActivity( activityPosted ); },
				}
			];
		}

		return (
			<ActivityElementPortal>
				<Notice
					status="error"
					isDismissible={ false }
					actions={ errorActions }
				>
					<p>{ activityPosted.error }</p>
				</Notice>
			</ActivityElementPortal>
		);
	} else {
		return null;
	}
};

export default ActivityUserFeedbacks;
