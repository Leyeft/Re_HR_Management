// Page title shown in the white bar at the top of each page.
export default function PageHeader({ children }) {
    return (
        <h2 className="text-xl font-semibold leading-tight text-primary">
            {children}
        </h2>
    );
}
