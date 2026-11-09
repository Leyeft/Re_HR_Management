import { createSlice } from '@reduxjs/toolkit';

/**
 * Light / dark mode.
 *   mode       – the user's choice: 'light', 'dark' or 'system' (follow the device)
 *   systemDark – whether the device is currently in dark mode
 *
 * The choice is saved in this browser (localStorage), not on the server.
 * The theme itself is just data-theme="dark" on <html>; app.css swaps the colour
 * values for it. spa.blade.php sets the attribute before React loads, so the page
 * never flashes the wrong colours.
 */
const STORAGE_KEY = 'crewly-theme';
const MODES = ['light', 'dark', 'system'];
const media = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function savedMode() {
    try {
        const mode = localStorage.getItem(STORAGE_KEY);
        return MODES.includes(mode) ? mode : 'system';
    } catch {
        return 'system'; // storage blocked (private mode etc.)
    }
}

const themeSlice = createSlice({
    name: 'theme',
    initialState: { mode: savedMode(), systemDark: media?.matches ?? false },
    reducers: {
        setThemeMode(state, action) {
            if (MODES.includes(action.payload)) state.mode = action.payload;
        },
        systemThemeChanged(state, action) {
            state.systemDark = action.payload;
        },
    },
});

export const { setThemeMode, systemThemeChanged } = themeSlice.actions;
export default themeSlice.reducer;

export const selectThemeMode = (state) => state.theme.mode;

// The theme actually shown: 'light' or 'dark'.
export const selectTheme = (state) =>
    state.theme.mode === 'system' ? (state.theme.systemDark ? 'dark' : 'light') : state.theme.mode;

/**
 * Keep <html data-theme> and localStorage in step with the store, and follow the
 * device when it switches between light and dark. Called once, in store/index.js.
 */
export function syncTheme(store) {
    let lastMode = null;
    let lastTheme = null;

    const apply = () => {
        const state = store.getState();
        const theme = selectTheme(state);
        if (theme !== lastTheme) {
            document.documentElement.dataset.theme = theme;
            lastTheme = theme;
        }
        if (state.theme.mode !== lastMode) {
            try {
                localStorage.setItem(STORAGE_KEY, state.theme.mode);
            } catch {
                /* not saved; the choice still works until the page is closed */
            }
            lastMode = state.theme.mode;
        }
    };

    apply();
    store.subscribe(apply);
    media?.addEventListener('change', (event) => store.dispatch(systemThemeChanged(event.matches)));
}
