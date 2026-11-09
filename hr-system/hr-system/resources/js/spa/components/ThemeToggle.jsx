import { useDispatch, useSelector } from 'react-redux';
import { selectThemeMode, setThemeMode } from '../store/themeSlice';

const ICONS = {
    light: (
        <>
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </>
    ),
    dark: <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />,
    system: (
        <>
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path d="M8 20h8M12 16v4" />
        </>
    ),
};

const OPTIONS = [
    { mode: 'light', label: 'Light' },
    { mode: 'dark', label: 'Dark' },
    { mode: 'system', label: 'System' },
];

/**
 * Light / Dark / System switch. `onNav` = the version for the dark top bar (phone menu);
 * otherwise it's styled for a light card such as the user menu.
 */
export default function ThemeToggle({ onNav = false }) {
    const dispatch = useDispatch();
    const current = useSelector(selectThemeMode);

    const base = 'flex flex-1 items-center justify-center gap-1.5 rounded px-2 py-1 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent';
    const active = onNav ? 'bg-white/15 text-white' : 'bg-surface text-content shadow-sm';
    const idle = onNav ? 'text-white/70 hover:text-white' : 'text-muted hover:text-content';

    return (
        <div role="radiogroup" aria-label="Colour theme" className={`flex gap-1 rounded-md p-1 ${onNav ? 'bg-white/5' : 'bg-bg'}`}>
            {OPTIONS.map(({ mode, label }) => (
                <button
                    key={mode}
                    type="button"
                    role="radio"
                    aria-checked={current === mode}
                    onClick={() => dispatch(setThemeMode(mode))}
                    className={`${base} ${current === mode ? active : idle}`}
                >
                    <svg className="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                        {ICONS[mode]}
                    </svg>
                    {label}
                </button>
            ))}
        </div>
    );
}
