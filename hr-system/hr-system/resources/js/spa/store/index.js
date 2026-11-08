import { configureStore } from '@reduxjs/toolkit';
import { setUnauthenticatedHandler } from '../api/client';
import authReducer, { sessionEnded } from './authSlice';
import dashboardReducer from './dashboardSlice';
import departmentsReducer from './departmentsSlice';
import employeesReducer from './employeesSlice';
import toastReducer from './toastSlice';

/**
 * The global Redux store. Each feature has its own slice:
 *   auth        – who is signed in and what they may do
 *   toasts      – notification messages
 *   dashboard   – dashboard figures
 *   departments – all departments (searched in the browser)
 *   employees   – the employee list page, the open profile and its leave history
 */
export const store = configureStore({
    reducer: {
        auth: authReducer,
        toasts: toastReducer,
        dashboard: dashboardReducer,
        departments: departmentsReducer,
        employees: employeesReducer,
    },
});

// Any 401 from the API ends the session in the store; the route guard then
// sends the user to the login page.
setUnauthenticatedHandler(() => store.dispatch(sessionEnded()));
