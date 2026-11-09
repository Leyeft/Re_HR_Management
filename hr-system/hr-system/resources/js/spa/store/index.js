import { configureStore } from '@reduxjs/toolkit';
import { setUnauthenticatedHandler } from '../api/client';
import authReducer, { sessionEnded } from './authSlice';
import dashboardReducer from './dashboardSlice';
import departmentsReducer from './departmentsSlice';
import employeesReducer from './employeesSlice';
import holidaysReducer from './holidaysSlice';
import leaveReducer from './leaveSlice';
import settingsReducer from './settingsSlice';
import skillsReducer from './skillsSlice';
import notificationsReducer from './notificationsSlice';
import activityReducer from './activitySlice';
import toastReducer from './toastSlice';
import themeReducer, { syncTheme } from './themeSlice';

/**
 * The global Redux store. Each feature has its own slice:
 *   auth        – who is signed in and what they may do
 *   toasts      – notification messages
 *   dashboard   – dashboard figures
 *   departments – all departments (searched in the browser)
 *   employees   – the employee list page, the open profile and its leave history
 *   leave       – my leave + balance, Head approvals/history, HR's all-leave list
 *   holidays    – public holidays (used to count working days)
 *   settings    – leave rollover policy
 *   skills      – skills employees can have (with a level, via the employee_skill pivot)
 *   notifications – in-app notifications + unread count (the bell)
 *   activity    – the audit trail (Admin/HR)
 *   theme       – light / dark mode (saved in this browser)
 */
export const store = configureStore({
    reducer: {
        auth: authReducer,
        toasts: toastReducer,
        dashboard: dashboardReducer,
        departments: departmentsReducer,
        employees: employeesReducer,
        leave: leaveReducer,
        holidays: holidaysReducer,
        settings: settingsReducer,
        skills: skillsReducer,
        notifications: notificationsReducer,
        activity: activityReducer,
        theme: themeReducer,
    },
});

// Apply the saved light/dark choice to the page and keep it in step.
syncTheme(store);

// Any 401 from the API ends the session in the store; the route guard then
// sends the user to the login page.
setUnauthenticatedHandler(() => store.dispatch(sessionEnded()));
