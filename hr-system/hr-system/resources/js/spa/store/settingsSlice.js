import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Company leave policy (HR/Admin): does unused annual leave roll over, and up to how many days.
 */
export const fetchLeaveSettings = apiThunk('settings/fetchLeave', async () => (await api.get('/settings/leave')).data.data);
export const saveLeaveSettings = apiThunk('settings/saveLeave', async (values) => (await api.put('/settings/leave', values)).data);

const initialState = { leave: null, status: 'idle', error: null };

const settingsSlice = createSlice({
    name: 'settings',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchLeaveSettings.pending, (state) => {
                state.status = 'loading';
                state.error = null;
            })
            .addCase(fetchLeaveSettings.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.leave = payload;
            })
            .addCase(fetchLeaveSettings.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load leave settings.';
            })
            .addCase(saveLeaveSettings.fulfilled, (state, { payload }) => {
                state.leave = payload.data;
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default settingsSlice.reducer;
