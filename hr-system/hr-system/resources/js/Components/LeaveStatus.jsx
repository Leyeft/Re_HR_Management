const styles = {
    pending_head: 'bg-warning-bg text-warning',
    pending_hr: 'bg-info-bg text-info',
    approved: 'bg-success-bg text-success',
    rejected: 'bg-danger-bg text-danger',
    cancelled: 'bg-bg text-muted',
};

const labels = {
    pending_head: 'Awaiting Head',
    pending_hr: 'Awaiting HR',
    approved: 'Approved',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
};

// Coloured badge for a leave request's status in the two-step (Head → HR) flow,
// plus the rejection reason when it was turned down.
export default function LeaveStatus({ leave }) {
    return (
        <>
            <span
                className={
                    'rounded px-2 py-1 text-xs ' +
                    (styles[leave.status] ?? 'bg-bg text-muted')
                }
            >
                {labels[leave.status] ?? leave.status}
            </span>

            {leave.status === 'pending_hr' && (
                <div className="mt-1 text-xs text-muted">Head approved ✓</div>
            )}

            {leave.status === 'rejected' && leave.rejection_reason && (
                <div className="mt-1 text-xs text-muted">
                    Reason: {leave.rejection_reason}
                </div>
            )}

            {leave.status === 'cancelled' && leave.cancellation_reason && (
                <div className="mt-1 text-xs text-muted">
                    Cancelled: {leave.cancellation_reason}
                </div>
            )}
        </>
    );
}
