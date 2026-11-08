import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api, { csrf } from '../api/client';

/**
 * Who is signed in, and what they may do.
 *
 * status:
 *   'idle'          – app just started, nothing checked yet
 *   'checking'      – asking the API "who am I?"
 *   'authenticated' – signed in (user/employee/can are filled)
 *   'guest'         – not signed in
 */
const initialState = {
    status: 'idle',
    user: null,
    employee: null,
    // Permission flags from GET /api/auth/me — used to show/hide menu items and guard routes.
    // The API checks them again on every request, so hiding is only for convenience.
    can: {},
    // Shown on the login page after being signed out (e.g. "Your session ended").
    notice: null,
};

// On page load: is there already a session?
export const fetchMe = createAsyncThunk('auth/fetchMe', async (_, { rejectWithValue }) => {
    try {
        const { data } = await api.get('/auth/me', { silent401: true });
        return data.data;
    } catch (error) {
        return rejectWithValue(error);
    }
});

export const login = createAsyncThunk('auth/login', async (credentials, { rejectWithValue }) => {
    try {
        await csrf();
        const { data } = await api.post('/auth/login', credentials);
        return data.data;
    } catch (error) {
        return rejectWithValue(error);
    }
});

export const logout = createAsyncThunk('auth/logout', async () => {
    try {
        await api.post('/auth/logout');
    } catch {
        // Already signed out on the server — fine, we clear locally either way.
    }
});

const signedIn = (state, { payload }) => {
    state.status = 'authenticated';
    state.user = payload.user;
    state.employee = payload.employee;
    state.can = payload.can;
    state.notice = null;
};

const signedOut = (state) => {
    state.status = 'guest';
    state.user = null;
    state.employee = null;
    state.can = {};
};

const authSlice = createSlice({
    name: 'auth',
    initialState,
    reducers: {
        // The API answered 401 to some request: the session is gone.
        sessionEnded(state) {
            if (state.status === 'authenticated') {
                state.notice = 'Your session has ended. Please sign in again.';
            }
            signedOut(state);
        },
        // After editing your own profile.
        userUpdated(state, { payload }) {
            state.user = { ...state.user, ...payload };
        },
        clearNotice(state) {
            state.notice = null;
        },
    },
    extraReducers: (builder) => {
        builder
            .addCase(fetchMe.pending, (state) => {
                state.status = 'checking';
            })
            .addCase(fetchMe.fulfilled, signedIn)
            .addCase(fetchMe.rejected, signedOut)
            .addCase(login.fulfilled, signedIn)
            .addCase(logout.fulfilled, signedOut);
    },
});

export const { sessionEnded, userUpdated, clearNotice } = authSlice.actions;

// Selectors
export const selectAuth = (state) => state.auth;
export const selectUser = (state) => state.auth.user;
export const selectCan = (state) => state.auth.can;

export default authSlice.reducer;
