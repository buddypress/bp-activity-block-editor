/**
 * External dependencies.
 */
import {
	assignIn,
	uniqueId,
} from 'lodash';

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
 * Resolver for saving an activity.
 */
export function* saveActivity( activity ) {
	let inserting = true, created;

	yield { type: types.SAVE_START, inserting, activity };

	try {
		if ( ! activity.id ) {
			created = yield createFromAPI( '/buddypress/v1/activity', activity );
		} else {
			created = yield updateFromAPI( '/buddypress/v1/activity/' + activity.id, activity );
		}

	} catch ( error ) {
		created = assignIn( {
			id: uniqueId(),
			error: error.message,
		}, activity );


		yield { type: types.ADD_ERROR, created };
	}

	inserting = false;

	yield { type: types.SAVE_END, inserting, created };
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
