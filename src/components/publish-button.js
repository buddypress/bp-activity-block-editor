/**
 * WordPress dependencies.
 */
const {
	i18n: {
		__,
	},
	element: {
		createElement,
	},
	data: {
		useSelect,
		useDispatch,
	},
	components: {
		Button,
	},
} = wp;

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const ActivityPublishButton = () => {
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

		insertActivity( activity );
		resetBlocks( [] );
	}

	return (
		<Button
			className="activity-editor-header__publish-button"
			isPrimary
			disabled={ isDisabled }
			isBusy={ isBusy }
			onClick={ () => postActivity() }
		>
			{ __( 'Post Update', 'bp-gutenberg' ) }
		</Button>
	);
};

export default ActivityPublishButton;
