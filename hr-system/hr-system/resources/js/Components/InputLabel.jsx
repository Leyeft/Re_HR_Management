// Label for a form field. `value` = the text; `htmlFor` links it to the input id.
export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={
                `block text-sm font-medium text-content ` +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
