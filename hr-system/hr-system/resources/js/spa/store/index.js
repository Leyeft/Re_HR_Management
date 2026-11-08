import { configureStore } from '@reduxjs/toolkit';
import { setUnauthenticatedHandler } from '../api/client';
import authReducer, { sessionEnded } from './authSlice';
import dashboardReducer from './dashboardSlice';
import toastReducer from './toastSlice';

/**
 * The global Redux store. Each feature has its own slice:
 *   auth   – who is signed in and what they may do
 *   toasts – notification messages
 * Feature slices (employees, departments, leave, …) are added as pages move over.
 */
export const store = configureStore({
    reducer: {
        auth: authReducer,
        toasts: toastReducer,
        dashboard: dashboardReducer,
    },
});

// Any 401 from the API ends the session in the store; the route guard then
// sends the user to the login page.
setUnauthenticatedHandler(() => store.dispatch(sessionEnded()));
