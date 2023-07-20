/**
 * WordPress dependencies
 */
import {
	Component,
	createPortal,
} from '@wordpress/element';

class ActivityElementPortal extends Component {
	render() {
		return createPortal(
			this.props.children,
			document.querySelector( "#bp-activity-block-editor-notices" )
		);
	}
}

export default ActivityElementPortal;
