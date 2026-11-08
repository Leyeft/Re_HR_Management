import { Route, Routes } from 'react-router-dom';
import AppLayout from '../layouts/AppLayout';
import Dashboard from '../pages/Dashboard';
import Profile from '../pages/Profile';
import Departments from '../pages/departments/Departments';
import EmployeeForm from '../pages/employees/EmployeeForm';
import EmployeeProfile from '../pages/employees/EmployeeProfile';
import Employees from '../pages/employees/Employees';
import AllLeave from '../pages/leave/AllLeave';
import Approvals from '../pages/leave/Approvals';
import DeptHistory from '../pages/leave/DeptHistory';
import MyLeave from '../pages/leave/MyLeave';
import RequestLeave from '../pages/leave/RequestLeave';
import LeaveSettings from '../pages/settings/LeaveSettings';
import ForgotPassword from '../pages/auth/ForgotPassword';
import ResetPassword from '../pages/auth/ResetPassword';
import NotFound from '../pages/NotFound';
import Login from '../pages/auth/Login';
import { GuestOnly, RequireAuth, RequireCan } from './guards';

/**
 * Every page of the SPA and who may open it.
 *   GuestOnly  – login/password pages (signed-in users are sent on)
 *   RequireAuth – any signed-in user
 *   RequireCan  – signed-in users with a permission flag from /api/auth/me
 */
export default function AppRoutes() {
    return (
        <Routes>
            <Route element={<GuestOnly />}>
                <Route path="/login" element={<Login />} />
                <Route path="/forgot-password" element={<ForgotPassword />} />
                <Route path="/reset-password/:token" element={<ResetPassword />} />
            </Route>

            <Route element={<RequireAuth />}>
                <Route element={<AppLayout />}>
                    <Route index element={<Dashboard />} />
                    <Route path="/profile" element={<Profile />} />

                    <Route element={<RequireCan permission="request_leave" />}>
                        <Route path="/leave" element={<MyLeave />} />
                        <Route path="/leave/new" element={<RequestLeave />} />
                    </Route>

                    <Route element={<RequireCan permission="approve_department_leave" />}>
                        <Route path="/approvals" element={<Approvals />} />
                        <Route path="/approvals/history" element={<DeptHistory />} />
                    </Route>

                    <Route element={<RequireCan permission="sign_off_leave" />}>
                        <Route path="/hr/leave" element={<AllLeave />} />
                    </Route>

                    <Route element={<RequireCan permission="manage_staff" />}>
                        <Route path="/departments" element={<Departments />} />
                        <Route path="/employees" element={<Employees />} />
                        <Route path="/employees/new" element={<EmployeeForm />} />
                        <Route path="/employees/:id" element={<EmployeeProfile />} />
                        <Route path="/employees/:id/edit" element={<EmployeeForm />} />
                        <Route path="/settings/leave" element={<LeaveSettings />} />
                    </Route>

                    <Route path="*" element={<NotFound />} />
                </Route>
            </Route>
        </Routes>
    );
}
