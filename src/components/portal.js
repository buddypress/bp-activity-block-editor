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
			document.querySelector( "#bp-gutenberg-notices" )
		);
	}
}

export default ActivityElementPortal;
