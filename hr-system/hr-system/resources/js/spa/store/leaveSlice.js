import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Leave requests, for every role:
 *   mine      – my own requests (+ summary = my balance)
 *   approvals – requests in my department waiting for me (Head)
 *   history   – all leave in my department (Head)
 *   all       – every request, filtered (HR/Admin)
 *
 * Status flow: pending_head → pending_hr → approved  (rejected at either step; cancelled by the owner)
 */

/* ----- My leave ----- */
export const fetchMyLeave = apiThunk('leave/fetchMine', async (params) => (await api.get('/leave-requests', { params })).data);
export const fetchMySummary = apiThunk('leave/fetchSummary', async () => (await api.get('/leave-requests/summary')).data.data);
export const submitLeave = apiThunk('leave/submit', async (values) => (await api.post('/leave-requests', values)).data);
export const cancelLeave = apiThunk('leave/cancel', async ({ id, reason }) =>
    (await api.post(`/leave-requests/${id}/cancel`, { cancellation_reason: reason })).data);

/* ----- Head of Department ----- */
export const fetchApprovals = apiThunk('leave/fetchApprovals', async (params) => (await api.get('/approvals', { params })).data);
export const fetchDeptHistory = apiThunk('leave/fetchDeptHistory', async (params) => (await api.get('/approvals/history', { params })).data);
export const approveAsHead = apiThunk('leave/approveAsHead', async (id) => (await api.post(`/approvals/${id}/approve`)).data);
export const rejectAsHead = apiThunk('leave/rejectAsHead', async ({ id, reason }) =>
    (await api.post(`/approvals/${id}/reject`, { rejection_reason: reason })).data);

/* ----- HR / Admin ----- */
export const fetchAllLeave = apiThunk('leave/fetchAll', async (params) => (await api.get('/hr/leave-requests', { params })).data);
export const approveAsHr = apiThunk('leave/approveAsHr', async (id) => (await api.post(`/hr/leave-requests/${id}/approve`)).data);
export const rejectAsHr = apiThunk('leave/rejectAsHr', async ({ id, reason }) =>
    (await api.post(`/hr/leave-requests/${id}/reject`, { rejection_reason: reason })).data);

const emptyList = { items: [], meta: null, status: 'idle', error: null };
const initialState = {
    mine: emptyList,
    summary: { data: null, status: 'idle' },
    approvals: emptyList,
    history: emptyList,
    all: emptyList,
};

// pending / fulfilled / rejected for a paged list stored under state[key].
function addListCases(builder, thunk, key) {
    builder
        .addCase(thunk.pending, (state) => {
            state[key].status = 'loading';
            state[key].error = null;
        })
        .addCase(thunk.fulfilled, (state, { payload }) => {
            state[key] = { items: payload.data, meta: payload.meta, status: 'succeeded', error: null };
        })
        .addCase(thunk.rejected, (state, { payload }) => {
            state[key].status = 'failed';
            state[key].error = payload?.message ?? 'Could not load leave requests.';
        });
}

// Put the updated request back in a list, keeping the employee details already shown.
const replaceIn = (list, updated) => {
    list.items = list.items.map((l) => (l.id === updated.id ? { ...l, ...updated, employee: l.employee ?? updated.employee } : l));
};

const leaveSlice = createSlice({
    name: 'leave',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        addListCases(builder, fetchMyLeave, 'mine');
        addListCases(builder, fetchApprovals, 'approvals');
        addListCases(builder, fetchDeptHistory, 'history');
        addListCases(builder, fetchAllLeave, 'all');

        builder
            .addCase(fetchMySummary.pending, (state) => {
                state.summary.status = 'loading';
            })
            .addCase(fetchMySummary.fulfilled, (state, { payload }) => {
                state.summary = { data: payload, status: 'succeeded' };
            })
            .addCase(cancelLeave.fulfilled, (state, { payload }) => replaceIn(state.mine, payload.data))
            // A decided request leaves the Head's "waiting" list.
            .addCase(approveAsHead.fulfilled, (state, { payload }) => {
                state.approvals.items = state.approvals.items.filter((l) => l.id !== payload.data.id);
                if (state.approvals.meta) state.approvals.meta.total -= 1;
            })
            .addCase(rejectAsHead.fulfilled, (state, { payload }) => {
                state.approvals.items = state.approvals.items.filter((l) => l.id !== payload.data.id);
                if (state.approvals.meta) state.approvals.meta.total -= 1;
            })
            .addCase(approveAsHr.fulfilled, (state, { payload }) => replaceIn(state.all, payload.data))
            .addCase(rejectAsHr.fulfilled, (state, { payload }) => replaceIn(state.all, payload.data))
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default leaveSlice.reducer;
