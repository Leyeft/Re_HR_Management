import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import { logout, sessionEnded } from './authSlice';

/**
 * Dashboard figures from GET /api/dashboard.
 * scope 'organisation' (Admin/HR) or 'personal' (everyone else).
 */
export const fetchDashboard = createAsyncThunk('dashboard/fetch', async (_, { rejectWithValue }) => {
    try {
        const { data } = await api.get('/dashboard');
        return data.data;
    } catch (error) {
        return rejectWithValue(error);
    }
});

const initialState = { data: null, status: 'idle', error: null };

const dashboardSlice = createSlice({
    name: 'dashboard',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchDashboard.pending, (state) => {
                state.status = 'loading';
                state.error = null;
            })
            .addCase(fetchDashboard.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.data = payload;
            })
            .addCase(fetchDashboard.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load the dashboard.';
            })
            // Never show one person's figures to the next person who signs in.
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default dashboardSlice.reducer;
