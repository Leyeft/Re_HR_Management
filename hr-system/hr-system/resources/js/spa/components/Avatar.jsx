// Profile photo, or the person's initials in a brand-coloured circle when there is none.
const SIZES = { xs: 'h-6 w-6 text-[10px]', sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-20 w-20 text-2xl' };

export default function Avatar({ user, size = 'sm', className = '' }) {
    const initials = (user?.name ?? '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('');

    const base = `inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full ${SIZES[size] ?? SIZES.sm} ${className}`;

    if (user?.avatar_url) {
        return <img src={user.avatar_url} alt="" className={`${base} object-cover`} />;
    }

    return (
        <span className={`${base} bg-accent-tint font-semibold text-accent-text`} aria-hidden="true">
            {initials}
        </span>
    );
}
