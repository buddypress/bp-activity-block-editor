/**
 * External dependencies.
 */
import { find } from 'lodash';

/**
 * Returns the list of Active BP Components.
 *
 * @param {Object} state The current state.
 * @return {array} The list of Active BP Components.
 */
export const getActiveComponents = ( state ) => {
	return state.activeComponents || [];
};

/**
 * Retrieves the current user object.
 *
 * @param {Object} state The current state.
 * @return {Object} The data to return.
 */
export const getCurrentUser = ( state ) => {
	return state.user || {};
};

/**
 * Retrieves user's groups.
 *
 * @param {Object} state The current state.
 * @return {Array} Array of user's groups.
 */
export const getUserGroups = ( state ) => {
	return state.groups || [];
};

/**
 * Retrieves the content.
 *
 * @param {Object} state The current state.
 * @return {string} The data to return.
 */
export const getContent = ( state )  => {
	return state.content || '';
};

/**
 * Retrieves the activity edits.
 *
 * @param {Object} state The current state.
 * @return {Object} The data to return.
 */
 export const getActivityEdits = ( state )  => {
	return state.edits || {};
};

/**
 * Retrieves blocks.
 *
 * @param {Object} state The current state.
 * @return {Array} Array of of blocks.
 */
export const getBlocks = ( state )  => {
	return state.blocks || [];
};

/**
 * Returns true if an activity is being added.
 *
 * @param {Object} state The current state.
 * @return {boolean} True if a activity is being added.
 */
export const isInsertingActivity = ( state )  => {
	return state.inserting || false;
};

/**
 * Retrieves activity just posted.
 *
 * @param {Object} state The current state.
 * @return {Object} The data to return.
 */
export const getJustPostedActivity = ( state )  => {
	return state.created || {};
};

/**
 * Retrieves the activity date.
 *
 * @param {Object} state The current state.
 * @return {string} The data to return.
 */
export const getActivityDate = ( state ) => {
	return state.date || '';
};

/**
 * Returns the activity group.
 *
 * @param {Object} state The current state.
 * @return {integer} The group ID.
 */
export const getActivityGroup = ( state ) => {
	return find( state.groups || [], { id: state.groupId || 0 } );
};
