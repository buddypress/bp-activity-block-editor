/**
 * WordPress dependencies
 */
 const {
	domReady,
	url: {
		getPath,
	},
} = wp;

/**
 * Internal dependencies
 */
import './style.scss';
import { default as setTemplate, stringToElements } from './utilities';

/**
 * Activity Wall class.
 *
 * @since 11.0.0
 */
class bpActivityWall {
	/**
	 * Setup the Activity Wall.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} settings The REST API settings and preloaded data.
	 */
	constructor( settings ) {
		const { path, root, nonce, preloadedActivity, preloadedMember, currentActivity } = settings;
		this.endpoint = getPath( root.replace( '/wp-json', '' ) + path );
		this.root = root;
		this.nonce = nonce;
		this.activities = 'body' in preloadedActivity ? preloadedActivity.body : [];
		this.member = 'body' in preloadedMember ? preloadedMember.body : {};
		this.currentActivity = currentActivity;
		this.container = document.querySelector( '#bp-activity-wall-items' );
	}

	/**
	 * Renders the HTML of an activity item.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} props The activity item properties.
	 * @returns {string} HTML output.
	 */
	 renderItem( props ) {
		const Template = setTemplate( 'bp-activity-entry' );
		let activity_classes = [ props.component, props.type ];

		if ( ! 'rendered' in props.content || ! props.content.rendered ) {
			activity_classes.push( 'mini' );
		}

		if ( 'comment_count' in props && !! props.comment_count ) {
			activity_classes.push( 'has-comments' );
		}

		props.activity_class = activity_classes.join( ' ' );
		props.id_attribute = 'activity_comment' === props.type ? 'activity-comment' : 'activity';

		// Finally return the rendered activity.
		return Template( props )
	}

	/**
	 * Builds the Activity directory loop.
	 *
	 * @since 1.0.0
	 *
	 * @param {Array} activities The list of activity items.
	 */
	loop( activities ) {
		activities.forEach( ( activity ) => {
			activity.author_link = activity._embedded.user[0].link;
			this.container.innerHTML += this.renderItem( activity );
		} );
	}

	/**
	 * Adds the just posted activity to the Wall.
	 *
	 * @since 1.0.0
	 *
	 * @param {MessageEvent} event The Window posted message event.
	 */
	addItem( event ) {
		const activity = event.data && event.data.message && 'postedBPActivity' === event.data.message ? event.data : null;

		if ( !! activity && ! this.activities.find( existingActivity => existingActivity.id === activity.id ) ) {
			delete activity.message;

			if ( 'activity_comment' === activity.type ) {
				this.activities.push( activity );

				this.container.append( stringToElements( this.renderItem( activity ) ) );
			} else {
				this.activities.unshift( activity );

				this.container.prepend( stringToElements( this.renderItem( activity ) ) );
			}
		}
	}

	/**
	 * Add various listeners to the Activity Wall.
	 *
	 * @since 1.0.0
	 */
	setUpListeners() {
		window.addEventListener( 'message', this.addItem.bind( this ), false );
	}

	/**
	 * Activity Wall Class starter.
	 *
	 * @since 1.0.0
	 */
	start() {
		if ( this.activities && 0 !== this.activities.length ) {
			this.loop( this.activities );
		}

		if ( !! this.currentActivity ) {
			document.querySelector( '#bp-activity-view' ).innerHTML = this.renderItem( this.currentActivity );
		}

		this.setUpListeners();
	}
}

const settings = window.bpActivityWallSettings || {};
window.bp = window.bp || {};
window.bp.Activity = new bpActivityWall( settings );

domReady( () => window.bp.Activity.start() );
