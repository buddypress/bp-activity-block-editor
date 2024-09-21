/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { getPath } from '@wordpress/url';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Activity Wall class.
 *
 * @since 1.0.0
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
	 * Renders the HTML of an activity entry's actions.
	 *
	 * @since 1.0.2
	 *
	 * @param {Object} props The activity item properties.
	 * @return {string} HTML output.
	 */
	renderActions( props ) {
		const template = document.querySelector( '#bp-activity-entry-actions-template' );
		const entryActions = document.createDocumentFragment();

		props.actions.forEach( ( action ) => {
			const entryAction = template.content.cloneNode( true );
			entryAction.querySelector( 'a' ).setAttribute( 'href', action['url'] );
			entryAction.querySelector( 'a' ).classList.add( 'bp-activity-' + action.name );
			entryAction.querySelector( 'a' ).innerHTML = action.text;
			entryActions.appendChild( entryAction );
		} );

		return entryActions;
	}

	/**
	 * Renders the HTML of an activity entry's major actions.
	 *
	 * @since 1.0.2
	 *
	 * @param {Object} props The activity item properties.
	 * @return {string} HTML output.
	 */
	renderMajorActions( props ) {
		const template = document.querySelector( '#bp-activity-entry-major-actions-template' );
		const majorActions = document.importNode( template.content, true );

		majorActions.querySelector( '[popovertarget="activity-major-actions"]' ).setAttribute( 'popovertarget', 'activity-major-actions-' + props.id );
		majorActions.querySelector( '#activity-major-actions' ).setAttribute( 'id', 'activity-major-actions-' + props.id );

		props.majorActions.forEach( ( action ) => {
			majorActions.querySelector( '.activity-major-action-links' ).innerHTML += '<li><span class="dashicons dashicons-' + action['name'] + '"></span><a href="' +  action['url'] + '" class="bp-activity-' + action['name'] + '" role="button">' + action['text'] + '</a></li>';
		} );

		return majorActions;
	}

	/**
	 * Renders the HTML of an activity entry.
	 *
	 * @since 1.0.0
	 *
	 * @param {Object} props The activity item properties.
	 * @return {string} HTML output.
	 */
	 renderEntry( props ) {
		const template = document.querySelector( '#bp-activity-entry-template' );
		const activityEntry = document.importNode( template.content, true );

		if ( 'user' in props._embedded && !! props._embedded.user ) {
			activityEntry.querySelector( '.activity-avatar a' ).setAttribute( 'href', props._embedded.user[0].link );
		}

		if ( props.user_avatar ) {
			activityEntry.querySelector( '.activity-avatar a' ).innerHTML = '<img loading="lazy" src="' + props.user_avatar.thumb + '" class="avatar user-' + props.user_id + '-avatar avatar-50 photo" width="50" alt="' + props.altAvatar + '">';
		} else {
			activityEntry.querySelector( '.activity-avatar a' ).innerHTML = '<div class="avatar user-' + props.user_id + '-avatar avatar-50 photo">&nbsp;</div>';
		}

		activityEntry.querySelector( '.activity-title-text' ).innerHTML = props.title;
		activityEntry.querySelector( '.activity-title .activity-time-since' ).setAttribute( 'href', props.link );
		activityEntry.querySelector( '.activity-title .time-since' ).textContent = props.timediff;

		if ( !! props.majorActions ) {
			activityEntry.querySelector( '.activity-major-actions' ).appendChild( this.renderMajorActions( props ) );
		}

		if ( 'rendered' in props.content && !! props.content.rendered ) {
			activityEntry.querySelector( '.activity-inner' ).innerHTML = props.content.rendered;
		}

		if ( !! props.actions ) {
			activityEntry.querySelector( '.activity-action-buttons' ).appendChild( this.renderActions( props ) );
		}

		return activityEntry;
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
			this.container.appendChild( this.renderEntry( activity ) );
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

				this.container.append( stringToElements( this.renderEntry( activity ) ) );
			} else {
				this.activities.unshift( activity );

				this.container.prepend( stringToElements( this.renderEntry( activity ) ) );
			}
		}
	}

	/**
	 * Deletes an Activity item.
	 *
	 * @since 1.0.0
	 *
	 * @param {HTMLElement} activityContainer The Activity `<article>` container.
	 * @return {void}
	 */
	deleteItem( activityContainer ) {
		const activityId = activityContainer.dataset ? parseInt( activityContainer.dataset.bpActivityId, 10 ) : 0;

		if ( ! activityId ) {
			return;
		}

		const activityAt = this.activities.findIndex( ( activity ) => activity.id === activityId );
		const errorDialog = activityContainer.querySelector( '#activity-error-' + activityId );

		errorDialog.querySelector( 'button' ).addEventListener( 'click', () => errorDialog.close() );

		if ( -1 === activityAt ) {
			return;
		}

		apiFetch( {
			path: 'buddypress/v1/activity/' + activityId,
			method: 'DELETE'
		} ).then( ( response ) => {
			if ( response && true === response.deleted ) {
				// Remove the Activity from the global list.
				this.activities.splice( activityAt, 1 );

				// Remove the HTML output of this activity.
				activityContainer.remove();
			} else {
				errorDialog.showModal();
			}

		} ).catch( ( error ) => {
			errorDialog.querySelector( 'p' ).innerHTML = error.message;
			errorDialog.showModal();
		} );
	}

	/**
	 * Favorites or Unfavorites an Activity item.
	 *
	 * @since 1.0.0
	 *
	 * @param {HTMLElement} activityContainer The Activity `<article>` container.
	 * @return {void}
	 */
	favoriteItem( activityContainer ) {
		const activityId = activityContainer.dataset ? parseInt( activityContainer.dataset.bpActivityId, 10 ) : 0;

		if ( ! activityId ) {
			return;
		}

		const activityAt = this.activities.findIndex( ( activity ) => activity.id === activityId );

		if ( -1 === activityAt ) {
			return;
		}

		const errorDialog = activityContainer.querySelector( '#activity-error-' + activityId );

		errorDialog.querySelector( 'button' ).addEventListener( 'click', () => errorDialog.close() );

		apiFetch( {
			path: 'buddypress/v1/activity/' + activityId + '/favorite',
			method: 'POST'
		} ).then( ( response ) => {
			if ( response && response[0] ) {
				const activity = response[0];
				const buttonLabel = true === activity.favorited ? __( 'Remove favorite', 'bp-activity-block-editor' ) : __( 'Favorite', 'bp-activity-block-editor' );

				// Update the `favorited` property of the activity.
				this.activities[ activityAt ]['favorited'] = activity.favorited;

				console.log( this.activities[ activityAt ] );

				// Update the activity favorite label button.
				activityContainer.querySelector( '.bp-activity-favorite' ).innerHTML = buttonLabel;
			} else {
				errorDialog.showModal();
			}

		} ).catch( ( error ) => {
			errorDialog.querySelector( 'p' ).innerHTML = error.message;
			errorDialog.showModal();
		} );
	}

	/**
	 * Adjusts popover position and update toggle state.
	 *
	 * @since 1.0.0
	 *
	 * @param {HTMLButtonElement} invoker The Popover invoker.
	 */
	togglePopover( invoker ) {
		const popover = document.getElementById( invoker.getAttribute( 'popovertarget' ) );
		const position = invoker.getBoundingClientRect();

		popover.style.top = position.bottom + 'px';
		popover.style.left = position.left + 'px';

		popover.addEventListener( 'toggle', ( e ) => {
			if ( 'open' === e.newState ) {
				if ( ! invoker.classList.contains( 'is-open' ) ) {
					invoker.classList.add( 'is-open' );
				}
			} else {
				invoker.classList.remove( 'is-open' );
			}
		} );
	}

	/**
	 * Catches all stream click events to find the right handler.
	 *
	 * @since 1.0.0
	 *
	 * @param {PointerEvent} event The click event.
	 */
	catchStreamEvents( event ) {
		let target = event.target;

		if ( target.classList.contains( 'dashicons-ellipsis' ) ) {
			target = target.closest( 'button' );
		}

		if ( target.getAttribute( 'popovertarget' ) ) {
			return this.togglePopover( target );
		}

		if ( target.classList.contains( 'bp-activity-delete' ) ) {
			event.preventDefault();

			const confirmDialog = document.querySelector( '#bp-confirm-action' );
			const activityContainer = target.closest( '[data-bp-activity-id]' );
			const self = this;

			confirmDialog.showModal();
			confirmDialog.querySelector( '[value="confirm"]' ).addEventListener( 'click',
				( event ) => {
					event.preventDefault();
					confirmDialog.close();

					return self.deleteItem( activityContainer );
				}
			);
		}

		if ( target.classList.contains( 'bp-activity-favorite' ) ) {
			event.preventDefault();

			return this.favoriteItem( target.closest( '[data-bp-activity-id]' ) );
		}
	}

	/**
	 * Add various listeners to the Activity Wall.
	 *
	 * @since 1.0.0
	 */
	setUpListeners() {
		window.addEventListener( 'message', this.addItem.bind( this ), false );

		// Use event delegation to catch any events.
		this.container.addEventListener( 'click', this.catchStreamEvents.bind( this ), false );
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
			document.querySelector( '#bp-activity-view' ).innerHTML = this.renderEntry( this.currentActivity );
		}

		this.setUpListeners();
	}
}

const settings = window.bpActivityWallSettings || {};
window.bp = window.bp || {};
window.bp.Activity = new bpActivityWall( settings );

domReady( () => window.bp.Activity.start() );
