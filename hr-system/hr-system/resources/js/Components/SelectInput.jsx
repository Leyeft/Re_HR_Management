// Styled <select> dropdown in the Crewly theme. Same props as a <select>.
export default function SelectInput({ className = '', children, ...props }) {
    return (
        <select
            {...props}
            className={
                'rounded-md border-line bg-surface text-content shadow-sm focus:border-accent focus:ring-accent ' +
                className
            }
        >
            {children}
        </select>
    );
}
