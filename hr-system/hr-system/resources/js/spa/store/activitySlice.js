import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Activity log / audit trail (Admin/HR): who created, changed, deleted or restored what.
 */
export const fetchActivity = apiThunk('activity/fetch', async (params) => (await api.get('/activity', { params })).data);

const initialState = { items: [], meta: null, status: 'idle', error: null };

const activitySlice = createSlice({
    name: 'activity',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchActivity.pending, (state) => {
                state.status = 'loading';
                state.error = null;
            })
            .addCase(fetchActivity.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.items = payload.data;
                state.meta = payload.meta;
            })
            .addCase(fetchActivity.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load the activity log.';
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default activitySlice.reducer;
