import LeaveStatus from '@/Components/LeaveStatus';
import LeaveTypeBadge from '@/Components/LeaveTypeBadge';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { selectCan } from '../store/authSlice';
import SkillChips from './SkillChips';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

function Row({ label, children }) {
    return (
        <div className="flex justify-between gap-4 border-b border-line py-2 last:border-0">
            <dt className="text-sm text-muted">{label}</dt>
            <dd className="text-right text-sm font-medium text-content">{children ?? '—'}</dd>
        </div>
    );
}

/**
 * Quick-look popup for an employee (click a name in a list). `employee` is an
 * EmployeeResource; when it includes leave_summary, this year's leave is listed too.
 */
export default function EmployeeDetailsModal({ employee, onClose }) {
    const can = useSelector(selectCan);
    const ls = employee?.leave_summary;

    return (
        <Modal show={!!employee} onClose={onClose} maxWidth="lg">
            {employee && (
                <div className="p-6">
                    <div className="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold text-content">{employee.user?.name}</h2>
                            <p className="text-sm text-muted">{employee.user?.email}</p>
                        </div>
                        <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">{employee.rank_label}</span>
                    </div>

                    <dl>
                        <Row label="Employee ID"><span className="font-mono">{employee.employee_code}</span></Row>
                        <Row label="Department">{employee.department?.name}</Row>
                        <Row label="System role">{ROLE_LABELS[employee.user?.role]}</Row>
                        <Row label="Date hired">{employee.date_hired}</Row>
                        {ls && (
                            <>
                                <Row label="Years of service">{ls.years_of_service}</Row>
                                <Row label="Annual leave">{ls.annual.remaining}/{ls.annual.total} left ({ls.tier})</Row>
                                <Row label="Sick leave">{ls.sick.remaining}/{ls.sick.cap} left</Row>
                                <Row label="Professional leave">{ls.professional.taken} used · no limit</Row>
                            </>
                        )}
                    </dl>

                    {employee.skills && (
                        <div className="mt-4">
                            <h3 className="mb-2 text-sm font-medium text-muted">Skills</h3>
                            <SkillChips skills={employee.skills} />
                        </div>
                    )}

                    {ls && (
                        <div className="mt-6">
                            <h3 className="mb-2 text-sm font-medium text-muted">Leave this year ({ls.year})</h3>
                            {ls.entries?.length ? (
                                <div className="max-h-64 overflow-y-auto rounded-lg border border-line">
                                    <table className="w-full text-sm">
                                        <thead className="bg-bg text-left text-xs uppercase text-muted">
                                            <tr>
                                                <th className="p-2">Type</th>
                                                <th className="p-2">Dates</th>
                                                <th className="p-2 text-right">Days</th>
                                                <th className="p-2">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {ls.entries.map((e) => (
                                                <tr key={e.id} className="border-t border-line align-top">
                                                    <td className="p-2"><LeaveTypeBadge type={e.type} /></td>
                                                    <td className="p-2">{e.start_date} → {e.end_date}</td>
                                                    <td className="p-2 text-right tabular-nums">{e.days}</td>
                                                    <td className="p-2"><LeaveStatus leave={e} /></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-sm text-muted">No leave requests this year.</p>
                            )}
                        </div>
                    )}

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={onClose}>Close</SecondaryButton>
                        {can.manage_staff && (
                            <Link
                                to={`/employees/${employee.id}`}
                                onClick={onClose}
                                className="inline-flex items-center rounded-md bg-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-primary-hover"
                            >
                                View profile
                            </Link>
                        )}
                    </div>
                </div>
            )}
        </Modal>
    );
}
