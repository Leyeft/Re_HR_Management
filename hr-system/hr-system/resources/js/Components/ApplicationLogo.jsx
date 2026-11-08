// Crewly logo image. `dark` = the light-coloured version for the navy top bar.
export default function ApplicationLogo({ dark = false, ...props }) {
    return (
        <img
            src={dark ? '/logo/crewly-logo-on-dark.svg' : '/logo/crewly-logo.svg'}
            alt="Crewly"
            {...props}
        />
    );
}
