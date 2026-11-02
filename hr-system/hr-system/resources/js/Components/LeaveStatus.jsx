const styles = {
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
};

// Coloured badge for a leave request's status, plus the rejection reason.
export default function LeaveStatus({ leave }) {
    return (
        <>
            <span
                className={
                    'rounded px-2 py-1 text-xs capitalize ' +
                    (styles[leave.status] ?? 'bg-gray-100 text-gray-700')
                }
            >
                {leave.status}
            </span>

            {leave.status === 'rejected' && leave.rejection_reason && (
                <div className="mt-1 text-xs text-gray-500">
                    Reason: {leave.rejection_reason}
                </div>
            )}
        </>
    );
}
