import LeaveStatus from '@/Components/LeaveStatus';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';

const ROLE_LABELS = { admin: 'Admin', hr: 'HR', employee: 'Employee' };

function Row({ label, children }) {
    return (
        <div className="flex justify-between gap-4 border-b border-line py-2 last:border-0">
            <dt className="text-sm text-muted">{label}</dt>
            <dd className="text-right text-sm font-medium text-content">{children ?? '—'}</dd>
        </div>
    );
}

// Read-only details popup for an employee. `employee` should include its user,
// department, and (when available) its leave_summary.
export default function EmployeeDetails({ employee, show, onClose }) {
    const ls = employee?.leave_summary;

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg">
            <div className="p-6">
                <div className="mb-4 flex items-start justify-between">
                    <div>
                        <h2 className="text-lg font-semibold text-content">
                            {employee?.user?.name}
                        </h2>
                        <p className="text-sm text-muted">{employee?.user?.email}</p>
                    </div>
                    <span className="rounded bg-accent-tint px-2 py-1 text-xs font-medium text-accent-text">
                        {employee?.rank === 'head' ? 'Head of Department' : 'Employee'}
                    </span>
                </div>

                <dl>
                    <Row label="Department">{employee?.department?.name}</Row>
                    <Row label="System role">{ROLE_LABELS[employee?.user?.role] ?? employee?.user?.role}</Row>
                    <Row label="Date of birth">{employee?.date_of_birth}</Row>
                    <Row label="Date hired">{employee?.date_hired}</Row>
                    {ls && (
                        <>
                            <Row label="Years of service">{ls.years_of_service}</Row>
                            <Row label="Leave tier">{ls.tier} · {ls.entitlement} days/yr</Row>
                            <Row label={`Leave this year (${ls.year})`}>
                                {ls.remaining} left · {ls.taken} used · {ls.pending} pending
                            </Row>
                        </>
                    )}
                </dl>

                {ls && (
                    <div className="mt-6">
                        <h3 className="mb-2 text-sm font-medium text-muted">
                            Leave this year ({ls.year})
                        </h3>
                        {ls.entries?.length ? (
                            <div className="max-h-64 overflow-y-auto rounded-lg border border-line">
                                <table className="w-full text-sm">
                                    <thead className="bg-bg text-left text-xs uppercase text-muted">
                                        <tr>
                                            <th className="p-2">Dates</th>
                                            <th className="p-2">Days</th>
                                            <th className="p-2">Requested on</th>
                                            <th className="p-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {ls.entries.map((e) => (
                                            <tr key={e.id} className="border-t border-line align-top">
                                                <td className="p-2">{e.start_date} → {e.end_date}</td>
                                                <td className="p-2">{e.days}</td>
                                                <td className="p-2">{e.requested_on ?? '—'}</td>
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

                <div className="mt-6 flex justify-end">
                    <SecondaryButton onClick={onClose}>Close</SecondaryButton>
                </div>
            </div>
        </Modal>
    );
}
