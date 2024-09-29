/**
 * WordPress dependencies
 */
import {
	Component,
	createPortal,
} from '@wordpress/element';

class FeedbackPortal extends Component {
	render() {
		return createPortal(
			this.props.children,
			document.querySelector( "#bp-activity-block-editor-notices" )
		);
	}
}

export default FeedbackPortal;
