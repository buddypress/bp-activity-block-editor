/**
 * Internal dependencies
 */
import { TYPES as types } from './action-types';

/**
 * Default state.
 */
const DEFAULT_STATE = {
	user: {},
	content: '',
	date: '',
	inserting: false,
	created: {},
	activeComponents: [],
	groups: [],
	groupId: 0,
};

/**
 * Reducer for the BuddyPress block editor.
 *
 * @param   {Object}  state   The current state in the store.
 * @param   {Object}  action  Action object.
 *
 * @return  {Object}          New or existing state.
 */
const reducer = ( state = DEFAULT_STATE, action ) => {
	switch ( action.type ) {
		case types.SET_ACTIVE_COMPONENTS:
			return {
				...state,
				activeComponents: action.list,
			};

		case types.GET_CURRENT_USER:
			return {
				...state,
				user: action.user,
			};

		case types.GET_USER_GROUPS:
			return {
				...state,
				groups: action.groups,
			};

		case types.CREATE_START:
			return {
				...state,
				inserting: action.inserting,
				created: action.created,
			};

		case types.CREATE_END:
			return {
				...state,
				inserting: action.inserting,
				created: !! action.created[0] ? action.created[0] : state.created,
				content: '',
				date: '',
				groupId: 0,
				blocks: [],
			};

		case types.ADD_ERROR:
			return {
				...state,
				created: { ...action.created },
			};

		case types.UPDATE_CONTENT:
			return {
				...state,
				content: action.content,
				created: {},
			};

		case types.SET_ACTIVITY_DATE:
			return {
				...state,
				date: action.date,
			};

		case types.SET_ACTIVITY_GROUP:
			return {
				...state,
				groupId: action.groupId,
			};

		case types.RESET_ACTIVITY_GROUP:
			return {
				...state,
				groupId: 0,
			};

		case types.RESET_CREATED:
			return {
				...state,
				created: {},
			};
	}

	return state;
};

export default reducer;
