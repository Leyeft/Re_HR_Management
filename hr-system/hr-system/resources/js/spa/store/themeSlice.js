import { createSlice } from '@reduxjs/toolkit';

/**
 * Light / dark mode, remembered per user on this browser.
 *   mode       – the choice: 'light', 'dark' or 'system' (follow the device)
 *   systemDark – whether the device is currently in dark mode
 *   userId     – whose choice `mode` is (null = nobody signed in)
 *
 * Saved in localStorage, not on the server:
 *   crewly-theme:<userId>  – each person's own choice
 *   crewly-theme           – the choice in use right now; spa.blade.php reads it before
 *                            React loads, so a refresh never flashes the wrong colours
 *
 * Signed out (the sign-in and password pages) always follows the device, so one
 * person's choice doesn't carry over to the next person on the same computer.
 * The theme itself is just data-theme="dark" on <html>; app.css swaps the colours.
 */
const ACTIVE_KEY = 'crewly-theme';
const userKey = (id) => `crewly-theme:${id}`;
const MODES = ['light', 'dark', 'system'];
const media = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function read(key) {
    try {
        const mode = localStorage.getItem(key);
        return MODES.includes(mode) ? mode : null;
    } catch {
        return null; // storage blocked (private mode etc.)
    }
}

function write(key, mode) {
    try {
        if (mode) localStorage.setItem(key, mode);
        else localStorage.removeItem(key);
    } catch {
        /* not saved; the choice still works until the page is closed */
    }
}

const themeSlice = createSlice({
    name: 'theme',
    // Until we know who is signed in, keep what the page loaded with (no flash on refresh).
    initialState: { mode: read(ACTIVE_KEY) ?? 'system', systemDark: media?.matches ?? false, userId: null },
    reducers: {
        setThemeMode(state, action) {
            if (MODES.includes(action.payload)) state.mode = action.payload;
        },
        systemThemeChanged(state, action) {
            state.systemDark = action.payload;
        },
        // Someone signed in (their saved choice) or out (follow the device).
        themeOwnerChanged(state, action) {
            state.userId = action.payload.userId;
            state.mode = action.payload.mode;
        },
    },
});

export const { setThemeMode, systemThemeChanged, themeOwnerChanged } = themeSlice.actions;
export default themeSlice.reducer;

export const selectThemeMode = (state) => state.theme.mode;

// The theme actually shown: 'light' or 'dark'.
export const selectTheme = (state) =>
    state.theme.mode === 'system' ? (state.theme.systemDark ? 'dark' : 'light') : state.theme.mode;

/**
 * Keep <html data-theme> and localStorage in step with the store, switch to the right
 * person's choice when someone signs in or out, and follow the device when it switches
 * between light and dark. Called once, in store/index.js.
 */
export function syncTheme(store) {
    let lastOwner; // 'guest', a user id, or undefined while sign-in is still being checked
    let lastTheme = null;

    const apply = () => {
        const state = store.getState();
        const { status, user } = state.auth;

        // 1. Who is signed in changed: load their choice (or the device's, when signed out).
        const owner = status === 'authenticated' ? user?.id : status === 'guest' ? 'guest' : undefined;
        if (owner !== undefined && owner !== lastOwner) {
            lastOwner = owner;
            if (owner === 'guest') {
                write(ACTIVE_KEY, null);
                store.dispatch(themeOwnerChanged({ userId: null, mode: 'system' }));
            } else {
                // First sign-in since per-user themes: keep the choice the browser already had.
                const mode = read(userKey(owner)) ?? read(ACTIVE_KEY) ?? 'system';
                store.dispatch(themeOwnerChanged({ userId: owner, mode }));
            }
            return; // the dispatch above runs apply() again with the new state
        }

        // 2. Save the signed-in person's choice.
        if (state.theme.userId !== null) {
            write(userKey(state.theme.userId), state.theme.mode);
            write(ACTIVE_KEY, state.theme.mode);
        }

        // 3. Show it.
        const theme = selectTheme(state);
        if (theme !== lastTheme) {
            document.documentElement.dataset.theme = theme;
            lastTheme = theme;
        }
    };

    apply();
    store.subscribe(apply);
    media?.addEventListener('change', (event) => store.dispatch(systemThemeChanged(event.matches)));
}
