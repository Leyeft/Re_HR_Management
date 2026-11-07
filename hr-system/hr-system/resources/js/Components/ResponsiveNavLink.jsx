import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-accent bg-white/10 text-white focus:border-accent focus:bg-white/20 focus:text-white'
                    : 'border-transparent text-white/70 hover:border-white/40 hover:bg-white/10 hover:text-white focus:border-white/40 focus:bg-white/10 focus:text-white'
            } text-base font-medium transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
