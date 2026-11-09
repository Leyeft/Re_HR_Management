// Red error text under a form field (shows nothing when `message` is empty).
export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            className={'text-sm text-danger ' + className}
        >
            {message}
        </p>
    ) : null;
}
