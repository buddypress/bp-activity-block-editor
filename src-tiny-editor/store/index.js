/**
 * WordPress dependencies.
 */
import {
	createReduxStore,
	register,
} from '@wordpress/data';

/**
 * Internal dependencies.
 */
import { STORE_KEY } from './constants';
import * as selectors from './selectors';
import * as actions from './actions';
import * as resolvers from './resolvers';
import reducer from './reducers';
import { controls } from './controls';

const store = createReduxStore( STORE_KEY, {
	reducer,
	actions,
	selectors,
	controls,
	resolvers,
} );

register( store );

export const BP_ACTIVITY_STORE_KEY = STORE_KEY;
