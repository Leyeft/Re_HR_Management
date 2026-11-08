import { Route, Routes } from 'react-router-dom';
import AppLayout from '../layouts/AppLayout';
import ComingSoon from '../pages/ComingSoon';
import Dashboard from '../pages/Dashboard';
import Profile from '../pages/Profile';
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
                        <Route path="/leave" element={<ComingSoon title="My Leave" />} />
                    </Route>

                    <Route element={<RequireCan permission="approve_department_leave" />}>
                        <Route path="/approvals" element={<ComingSoon title="Approvals" />} />
                        <Route path="/approvals/history" element={<ComingSoon title="Department leave history" />} />
                    </Route>

                    <Route element={<RequireCan permission="sign_off_leave" />}>
                        <Route path="/hr/leave" element={<ComingSoon title="All Leave" />} />
                    </Route>

                    <Route element={<RequireCan permission="manage_staff" />}>
                        <Route path="/departments" element={<ComingSoon title="Departments" />} />
                        <Route path="/employees" element={<ComingSoon title="Employees" />} />
                        <Route path="/settings/leave" element={<ComingSoon title="Leave Settings" />} />
                    </Route>

                    <Route path="*" element={<NotFound />} />
                </Route>
            </Route>
        </Routes>
    );
}
