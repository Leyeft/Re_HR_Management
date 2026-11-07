import { usePage } from '@inertiajs/react';

// Shows the controller's ->with('success', '...') message, if there is one.
export default function FlashMessage() {
    const { flash } = usePage().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
            {flash.success && (
                <div className="rounded-lg bg-success-bg p-4 text-sm text-success">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg bg-danger-bg p-4 text-sm text-danger">
                    {flash.error}
                </div>
            )}
        </div>
    );
}
