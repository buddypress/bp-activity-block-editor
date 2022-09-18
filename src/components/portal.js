/**
 * WordPress dependencies
 */
const {
	element: {
		Component,
		createElement,
		createPortal,
	},
} = wp;

class ActivityElementPortal extends Component {
	render() {
		return createPortal(
			this.props.children,
			document.querySelector( "#bp-activity-block-editor-notices" )
		);
	}
}

export default ActivityElementPortal;
