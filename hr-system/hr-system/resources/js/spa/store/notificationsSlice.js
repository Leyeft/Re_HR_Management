import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * In-app notifications (the bell). The unread count is checked every 30 seconds;
 * the list is loaded when the bell is opened or the Notifications page is visited.
 */
export const fetchUnreadCount = apiThunk('notifications/unreadCount',
    async () => (await api.get('/notifications/unread-count')).data.data.unread_count);

export const fetchNotifications = apiThunk('notifications/fetch',
    async (params = {}) => (await api.get('/notifications', { params })).data);

export const markNotificationRead = apiThunk('notifications/markRead', async (id) => {
    const { data } = await api.post(`/notifications/${id}/read`);
    return { id, unread: data.data.unread_count };
});

export const markAllNotificationsRead = apiThunk('notifications/markAllRead',
    async () => (await api.post('/notifications/read-all')).data);

const initialState = { items: [], meta: null, unreadCount: 0, status: 'idle' };

const notificationsSlice = createSlice({
    name: 'notifications',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchUnreadCount.fulfilled, (state, { payload }) => {
                state.unreadCount = payload;
            })
            .addCase(fetchNotifications.pending, (state) => {
                state.status = 'loading';
            })
            .addCase(fetchNotifications.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.items = payload.data;
                state.meta = payload.meta;
                state.unreadCount = payload.unread_count;
            })
            .addCase(fetchNotifications.rejected, (state) => {
                state.status = 'failed';
            })
            .addCase(markNotificationRead.fulfilled, (state, { payload }) => {
                const note = state.items.find((n) => n.id === payload.id);
                if (note) note.read_at = new Date().toISOString();
                state.unreadCount = payload.unread;
            })
            .addCase(markAllNotificationsRead.fulfilled, (state) => {
                const now = new Date().toISOString();
                state.items.forEach((n) => {
                    n.read_at ??= now;
                });
                state.unreadCount = 0;
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default notificationsSlice.reducer;
