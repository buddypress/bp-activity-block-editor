/**
 * WordPress dependencies.
 */
const {
	i18n: {
		__,
		sprintf,
	},
	element: {
		createElement,
	},
	data: {
		useSelect,
	},
	components: {
		Button,
		Dashicon,
	},
} = wp;

/**
 * Internal dependencies.
 */
import { BP_ACTIVITY_STORE_KEY } from '../store';

const ActivityUserAvatar = () => {
	const currentUser = useSelect( ( select ) => {
		return select( BP_ACTIVITY_STORE_KEY ).getCurrentUser();
	}, [] );

	let buttonVisual = (
		<Dashicon icon="buddicons-activity" />
	);

	if ( currentUser.avatar_urls && currentUser.avatar_urls.thumb ) {
		buttonVisual = (
			<img src={ currentUser.avatar_urls.thumb.replaceAll( '&#038;', '&' ) } />
		);
	}

	return (
		<div className="activity-editor-user-avatar__container">
			<Button
				className="activity-editor-user-avatar__button"
				href={ currentUser.link }
				label={ sprintf(
					/* translators: %s is the user's name */
					__( 'View all %s‘s activities', 'buddypress' ),
					currentUser.name
				) }
			>
				{ buttonVisual }
			</Button>
		</div>
	)
};

export default ActivityUserAvatar;
