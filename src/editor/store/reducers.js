/**
 * Internal dependencies
 */
import { TYPES as types } from './action-types';

/**
 * Default state.
 */
const DEFAULT_STATE = {
	user: {},
	content: [],
	date: '',
	inserting: false,
	created: {},
	activeComponents: [],
	groups: [],
	groupId: 0,
	edits: {},
	canReply: true,
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

		case types.SAVE_START:
			return {
				...state,
				inserting: action.inserting,
				created: {},
			};

		case types.SAVE_END:
			return {
				...state,
				inserting: action.inserting,
				created: action.succeeded,
				content: [],
				date: '',
				groupId: 0,
				blocks: [],
			};

		case types.ADD_ERROR:
			return {
				...state,
				inserting: action.inserting,
				created: { ...action.failed },
			};

		case types.UPDATE_CONTENT:
			return {
				...state,
				content: action.content,
				created: {},
			};

		case types.UPDATE_ACTIVITY_EDITS:
			return {
				...state,
				content: action.activity.content,
				date: action.activity.date_recorded,
				groupId: 'item_id' in action.activity && 'groups' === action.activity.component ? action.activity.item_id : 0,
				edits: action.activity,
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

		case types.RESET_ACTIVITY:
			return {
				...state,
				content: [],
				date: '',
				inserting: false,
				groupId: 0,
				canReply: true,
			};

		case types.RESET_CREATED:
			return {
				...state,
				created: {},
			};

		case types.SET_ACTIVITY_REPLIES:
			return {
				...state,
				canReply: action.canReply,
			};
	}

	return state;
};

export default reducer;
