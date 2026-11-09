// Red button for destructive actions (delete, remove all). Same props as a <button>.
export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center rounded-md border border-transparent bg-danger px-4 py-2 text-xs font-semibold uppercase tracking-widest text-on-status transition duration-150 ease-in-out hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 active:opacity-100 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
