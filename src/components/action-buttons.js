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
	const { content, isInserting, user } = useSelect( ( select ) => {
		const store = select( BP_ACTIVITY_STORE_KEY );

		return {
			content: store.getContent(),
			isInserting: store.isInsertingActivity(),
			user: store.getCurrentUser(),
		};
	}, [] );
	const { insertActivity } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const { resetBlocks } = useDispatch( 'core/block-editor' );
	const isDisabled = ! content || isInserting;
	const isBusy = !! isInserting;

	// This is where Posting the activity is handled.
	const postActivity = () => {
		const activity = {
			user_id: user.id,
			type: 'activity_update',
			component: 'activity',
			content: content,
		};

		if ( ! isBusy ) {
			insertActivity( activity );
		}

		resetBlocks( [] );
	}

	const cancelActivity = () => {
		resetBlocks( [] );
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
				{ __( 'Post Update', 'bp-gutenberg' ) }
			</Button>
		</div>
	);
};

export default ActivityActionButtons;
