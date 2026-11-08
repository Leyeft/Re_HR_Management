import { createSelector, createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Public holidays. Everyone loads them (the leave form skips them when counting
 * working days); HR/Admin add and remove them on the Leave Settings page.
 */
export const fetchHolidays = apiThunk('holidays/fetch', async () => (await api.get('/holidays')).data.data);
export const addHoliday = apiThunk('holidays/add', async (values) => (await api.post('/holidays', values)).data);
export const removeHoliday = apiThunk('holidays/remove', async (id) => {
    const { data } = await api.delete(`/holidays/${id}`);
    return { id, message: data.message };
});

const initialState = { items: [], status: 'idle', error: null };

const holidaysSlice = createSlice({
    name: 'holidays',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchHolidays.pending, (state) => {
                state.status = 'loading';
            })
            .addCase(fetchHolidays.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.items = payload;
            })
            .addCase(fetchHolidays.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load holidays.';
            })
            .addCase(addHoliday.fulfilled, (state, { payload }) => {
                state.items.push(payload.data);
                // Yearly ones first, then by date — same order as the API.
                state.items.sort((a, b) => Number(b.recurring) - Number(a.recurring) || a.date.localeCompare(b.date));
            })
            .addCase(removeHoliday.fulfilled, (state, { payload }) => {
                state.items = state.items.filter((h) => h.id !== payload.id);
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

/**
 * Holidays in the shape the working-day counter in Utils/leave.js expects:
 *   { dates: { '2026-04-09': true }, yearly: { '12-25': true } }
 */
export const selectHolidayLookup = createSelector(
    (state) => state.holidays.items,
    (items) => {
        const lookup = { dates: {}, yearly: {} };
        for (const h of items) {
            if (h.recurring) lookup.yearly[h.date.slice(5)] = true;
            else lookup.dates[h.date] = true;
        }
        return lookup;
    },
);

export default holidaysSlice.reducer;
