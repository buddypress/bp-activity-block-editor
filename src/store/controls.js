/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Default export for registering the controls with the store.
 *
 * @return {Object} An object with the controls to register with the store on
 *                  the controls property of the registration object.
 */
export const controls = {
	FETCH_FROM_API( { path, parse } ) {
		return apiFetch( { path, parse } );
	},
	CREATE_FROM_API( { path, data } ) {
		return apiFetch( { path, method: 'POST', data } );
	},
	UPDATE_FROM_API( { path, data } ) {
		return apiFetch( { path, method: 'PUT', data } );
	},
};
