/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies.
 */
import { TYPES as types } from './action-types';

/**
 * Returns an action object used to set active components.
 *
 * @param {array} list The active components.
 * @return {Object} Object for action.
 */
export function setActiveComponents( list ) {
	return {
		type: types.SET_ACTIVE_COMPONENTS,
		list,
	};
}

/**
 * Returns the Saved Activity Promise.
 *
 * @since 1.0.2
 *
 * @param {Object} activity
 * @returns {Promise}
 */
export const saveActivity = ( activity ) => async ( { dispatch } ) => {
	let inserting = true, method = 'POST';
	let path = '/buddypress/v1/activity';

    dispatch( { type: 'SAVE_START', inserting } );

	if ( activity.id  ) {
		method = 'PUT';
		path += '/' + activity.id;
	}

    const created = await apiFetch( {
		path: path,
		method: method,
		data: activity,
	} ).then( ( succeeded ) => {
		inserting = false;
		dispatch( { type: 'SAVE_END', inserting, succeeded } );

	} ).catch( ( error ) => {
		inserting = false;
		const failed = {
			id: 0,
			error: error.message,
		};

		Object.assign( failed, activity );

		dispatch( { type: 'ADD_ERROR', inserting, failed } );
	} );

    return created;
}

/**
 * Returns an action object used to get the current user.
 *
 * @param {Object} user Current user object.
 * @return {Object} Object for action.
 */
export function getCurrentUser( user ) {
	return {
		type: types.GET_CURRENT_USER,
		user,
	};
}

/**
 * Returns an action object used to get the user's groups.
 *
 * @param {Array} groups An array of groups.
 * @return {Object} Object for action.
 */
export function getUserGroups( groups ) {
	return {
		type: types.GET_USER_GROUPS,
		groups,
	};
}

/**
 * Returns an action object used to fetch something from the API.
 *
 * @param {string} path Endpoint path.
 * @param {boolean} parse Should we parse the request.
 * @return {Object} Object for action.
 */
export function fetchFromAPI( path, parse ) {
	return {
		type: types.FETCH_FROM_API,
		path,
		parse,
	};
}

/**
 * Returns an action object used to create an object via the API.
 *
 * @param {string} path Endpoint path.
 * @param {Object} data The data to be created.
 * @return {Object} Object for action.
 */
export function createFromAPI( path, data ) {
	return {
		type: types.CREATE_FROM_API,
		path,
		data,
	};
}

/**
 * Returns an action object used to update an object via the API.
 *
 * @param {string} path Endpoint path.
 * @param {Object} data The data used for the update.
 * @return {Object} Object for action.
 */
 export function updateFromAPI( path, data ) {
	return {
		type: types.UPDATE_FROM_API,
		path,
		data,
	};
}

/**
 * Returns an action object used to update activity content.
 *
 * @param {string} content Activity content.
 * @return {Object} Object for action.
 */
export function updateContent( content ) {
	return {
		type: types.UPDATE_CONTENT,
		content,
	};
}

/**
 * Returns an action object used to init the activity edits.
 *
 * @param {Object} activity Activity object.
 * @return {Object} Object for action.
 */
 export function updateActivityEdits( activity ) {
	return {
		type: types.UPDATE_ACTIVITY_EDITS,
		activity,
	};
}

/**
 * Returns an action object used to reset the activity recently posted.
 *
 * @return {Object} Object for action.
 */
export function resetJustPostedActivity() {
	return {
		type: types.RESET_CREATED,
	};
}

/**
 * Returns an action object used to set an activity date.
 *
 * @param {string} date An activity date.
 * @return {Object} Object for action.
 */
export function setActivityDate( date ) {
	return {
		type: types.SET_ACTIVITY_DATE,
		date,
	};
}

/**
 * Returns an action object used to set a group for an activity.
 *
 * @param {integer} groupId A group ID.
 * @return {Object} Object for action.
 */
export function setActivityGroup( groupId ) {
	return {
		type: types.SET_ACTIVITY_GROUP,
		groupId,
	};
}

/**
 * Returns an action object used to reset activity group.
 *
 * @return {Object} Object for action.
 */
export function resetActivityGroup() {
	return {
		type: types.RESET_ACTIVITY_GROUP,
	};
}

/**
 * Returns an action object used to reset activity.
 *
 * @return {Object} Object for action.
 */
export function resetActivity() {
	return {
		type: types.RESET_ACTIVITY,
	};
}

/**
 * Returns an action object used to set whether the activity can be commented.
 *
 * @param {boolean} canReply True if the activity can be commented false otherwise.
 * @return {Object} Object for action.
 */
export function setActivityReplies( canReply ) {
	return {
		type: types.SET_ACTIVITY_REPLIES,
		canReply,
	};
}
