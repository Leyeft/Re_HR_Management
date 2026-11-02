import { usePage } from '@inertiajs/react';

// Shows the controller's ->with('success', '...') message, if there is one.
export default function FlashMessage() {
    const { flash } = usePage().props;

    if (!flash?.success) {
        return null;
    }

    return (
        <div className="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div className="rounded-lg bg-green-100 p-4 text-sm text-green-700">
                {flash.success}
            </div>
        </div>
    );
}
