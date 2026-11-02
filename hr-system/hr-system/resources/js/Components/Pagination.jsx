import { Link } from '@inertiajs/react';

// Renders the `links` array from a Laravel paginator (->paginate()).
export default function Pagination({ links }) {
    // Only "previous" and "next" means there is just one page.
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveScroll
                        className={
                            'rounded border px-3 py-1 text-sm ' +
                            (link.active
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-100')
                        }
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={index}
                        className="rounded border bg-white px-3 py-1 text-sm text-gray-400"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </div>
    );
}
