// Styled <select> dropdown in the Crewly theme. Same props as a <select>.
export default function SelectInput({ className = '', children, ...props }) {
    return (
        <select
            {...props}
            className={
                'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ' +
                className
            }
        >
            {children}
        </select>
    );
}
