export default function PageHeader({ children }) {
    return (
        <h2 className="text-xl font-semibold leading-tight text-gray-800">
            {children}
        </h2>
    );
}
