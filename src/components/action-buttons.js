/**
 * WordPress dependencies.
 */
const {
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

const ActivityActionButtons = () => {
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
	const { insertActivity, initActivityEdits } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const { resetBlocks } = useDispatch( 'core/block-editor' );
	const isDisabled = ! content || isInserting || ( !! activityEdits && content === activityEdits.content );
	const isBusy = !! isInserting;

	// This is where Posting the activity is handled.
	const postActivity = () => {
		const activity = {
			user_id: user.id,
			type: 'activity_update',
			component: 'activity',
			content: content,
		};

		if ( !! group && group.id ) {
			activity.primary_item_id = group.id;
			activity.component = 'groups';
		}

		if ( !! activityEdits.id ) {
			activity.id = activityEdits.id;
			activity.date = activityEdits.date;
		}

		if ( ! isBusy ) {
			insertActivity( activity );
		}

		// @todo in case of an edit, the blocks shouldn't be reset.
		resetBlocks( [] );
	}

	const cancelActivity = () => {
		if ( !! activityEdits.blocks ) {
			initActivityEdits( activityEdits );

			resetBlocks( activityEdits.blocks );
		} else {
			resetBlocks( [] );
		}
	}

	let publishButtonLabel = __( 'Post Update', 'bp-gutenberg' );
	if ( !! activityEdits.id ) {
		publishButtonLabel = __( 'Update Activity', 'bp-gutenberg' );
	}

	return (
		<div className="activity-editor-footer__action-buttons">
			<Button
				className="activity-editor-footer__reset-button"
				variant="secondary"
				disabled={ isDisabled }
				onClick={ () => cancelActivity() }
			>
				{ __( 'Cancel', 'bp-gutenberg' ) }
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
