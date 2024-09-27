/**
 * WordPress dependencies.
 */
import { Button } from '@wordpress/components';
import {
	useSelect,
	useDispatch,
} from '@wordpress/data';
import {
	serialize,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const ActionButtons = ( { onReset } ) => {
	const { content, user, isInserting } = useSelect( ( select ) => {
		const store = select( BP_ACTIVITY_STORE_KEY );

		return {
			content: store.getContent(),
			isInserting: store.isInsertingActivity(),
			user: store.getCurrentUser(),
		};
	}, [] );
	const { saveActivity } = useDispatch( BP_ACTIVITY_STORE_KEY );
	const isBusy = !! isInserting;

	const postActivity = () => {
		const activity = {
			user_id: user.id,
			type: 'activity_update',
			component: 'activity',
			content: serialize( content ),
		};

		saveActivity( activity );
	}

	return (
		<div className="activity-editor-footer__action-buttons">
			<Button
				className="activity-editor-footer__reset-button"
				variant="secondary"
				onClick={ () => onReset() }
			>
				{ __( 'Cancel', 'bp-activity-block-editor' ) }
			</Button>
			<Button
				className="activity-editor-footer__publish-button"
				variant="primary"
				isBusy={ isBusy }
				onClick={ () => postActivity() }
			>
				{ __( 'Post Update', 'bp-activity' ) }
			</Button>
		</div>
	);
}

export default ActionButtons;
