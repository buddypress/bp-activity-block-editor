/**
 * WordPress dependencies.
 */
import { Notice } from '@wordpress/components';
import {
	useSelect,
	useDispatch,
} from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';
import FeedbackPortal from '../elements/portal';

/**
 * Manage User notices display.
 *
 * @returns A Notice React Element or null.
 */
const UserFeedbacks = () => {
	const postedActivity = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getJustPostedActivity();
	}, [] );

	let output = null;

	if ( postedActivity.link ) {
		postedActivity.message = 'bpActivityPosted';
		window.postMessage( postedActivity, '*' );

	} else if ( postedActivity.error ) {
		output = (
			<FeedbackPortal>
				<Notice
					status="error"
					isDismissible={ false }
				>
					<p>{ postedActivity.error }</p>
				</Notice>
			</FeedbackPortal>
		);
	}

	return output;
}

export default UserFeedbacks;
