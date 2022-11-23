/**
 * External dependencies.
 */
const {
	merge,
} = lodash;

/**
 * WordPress dependencies.
 */
const {
	blocks: {
		parse,
		serialize,
	},
	components: {
		Button,
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

const ActivityActionButtons = ( { parentActivity } ) => {
	const { content, isInserting, user, group, activityEdits } = useSelect( ( select ) => {
		const store = select( BP_ACTIVITY_STORE_KEY );

		return {
			content: store.getContent(),
			isInserting: store.isInsertingActivity(),
			user: store.getCurrentUser(),
			group: store.getActivityGroup(),
			activityEdits: store.getActivityEdits(),
		};
	}, [] );
	const { saveActivity, updateActivityEdits } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const { resetBlocks } = useDispatch( 'core/block-editor' );
	const isDisabled = ! content || isInserting || ( !! activityEdits.blocks && ( content === serialize( activityEdits.blocks ) || content === activityEdits.content ) );
	const isBusy = !! isInserting;

	// This is where Posting the activity is handled.
	const postActivity = () => {
		const activity = {
			user_id: user.id,
			type: 'activity_update',
			component: 'activity',
			content: content,
		};

		if ( ( parentActivity && !! parentActivity.id ) || ( !! activityEdits.type && 'activity_comment' === activityEdits.type ) ) {
			activity.type = 'activity_comment';

			if ( !! activityEdits.type ) {
				activity.primary_item_id = activityEdits.item_id;
				activity.secondary_item_id = activityEdits.secondary_item_id;
			} else {
				activity.primary_item_id = parentActivity.id;
			}
		}

		if ( !! group && group.id ) {
			activity.primary_item_id = group.id;
			activity.component = 'groups';
		}

		if ( !! activityEdits.id ) {
			activity.id = activityEdits.id;
			activity.date = activityEdits.date;
		}

		if ( ! isBusy ) {
			saveActivity( activity );
		}

		if ( !! activityEdits.id ) {
			const updatedActivity = merge(
				activityEdits,
				{
					component: activity.component,
					content: activity.content,
					'item_id' : activity.primary_item_id ? activity.primary_item_id : 0,
					blocks: parse( activity.content ),
				}
			);

			updateActivityEdits( updatedActivity );
		} else {
			resetBlocks( [] );
		}
	}

	const cancelActivity = () => {
		if ( !! activityEdits.blocks ) {
			updateActivityEdits( activityEdits );

			resetBlocks( activityEdits.blocks );
		} else {
			resetBlocks( [] );
		}
	}

	let publishButtonLabel = __( 'Post Update', 'bp-activity-block-editor' );
	if ( !! activityEdits.id ) {
		publishButtonLabel = __( 'Update Activity', 'bp-activity-block-editor' );
	} else if ( parentActivity && !! parentActivity.id ) {
		publishButtonLabel = __( 'Reply', 'bp-activity-block-editor' );
	}

	return (
		<div className="activity-editor-footer__action-buttons">
			<Button
				className="activity-editor-footer__reset-button"
				variant="secondary"
				disabled={ isDisabled }
				onClick={ () => cancelActivity() }
			>
				{ __( 'Cancel', 'bp-activity-block-editor' ) }
			</Button>
			<Button
				className="activity-editor-footer__publish-button"
				variant="primary"
				disabled={ isDisabled }
				isBusy={ isBusy }
				onClick={ () => postActivity() }
			>
				{ publishButtonLabel }
			</Button>
		</div>
	);
};

export default ActivityActionButtons;
