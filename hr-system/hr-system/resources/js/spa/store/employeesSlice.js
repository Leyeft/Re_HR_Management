import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Employees. The list is searched, filtered and paged on the server
 * (GET /api/employees?search=&department=&rank=&sort=&page=).
 *   list    – the current page of the list + its pagination info
 *   current – the employee open on the profile/edit page
 *   history – that employee's leave history (paged)
 */
export const fetchEmployees = apiThunk('employees/fetchList', async (params) => (await api.get('/employees', { params })).data);

export const fetchEmployee = apiThunk('employees/fetchOne', async (id) => (await api.get(`/employees/${id}`)).data.data);

export const fetchEmployeeHistory = apiThunk('employees/fetchHistory', async ({ id, page = 1 }) => (await api.get(`/employees/${id}/leave-requests`, { params: { page } })).data);

export const createEmployee = apiThunk('employees/create', async (values) => (await api.post('/employees', values)).data);

export const updateEmployee = apiThunk('employees/update', async ({ id, ...values }) => (await api.put(`/employees/${id}`, values)).data);

export const restoreEmployee = apiThunk('employees/restore', async (id) => (await api.post(`/employees/${id}/restore`)).data);

// Soft delete — the person can be restored from the Deleted list.
export const deleteEmployee = apiThunk('employees/delete', async (id) => {
        const { data } = await api.delete(`/employees/${id}`);
        return { id, message: data.message };
    });

const initialState = {
    list: { items: [], meta: null, status: 'idle', error: null },
    current: { item: null, status: 'idle', error: null },
    history: { items: [], meta: null, status: 'idle' },
};

const employeesSlice = createSlice({
    name: 'employees',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchEmployees.pending, (state) => {
                state.list.status = 'loading';
                state.list.error = null;
            })
            .addCase(fetchEmployees.fulfilled, (state, { payload }) => {
                state.list = { items: payload.data, meta: payload.meta, status: 'succeeded', error: null };
            })
            .addCase(fetchEmployees.rejected, (state, { payload }) => {
                state.list.status = 'failed';
                state.list.error = payload?.message ?? 'Could not load employees.';
            })

            .addCase(fetchEmployee.pending, (state, { meta }) => {
                state.current.status = 'loading';
                state.current.error = null;
                // Don't flash the previous person's details while the next one loads.
                if (state.current.item?.id !== Number(meta.arg)) state.current.item = null;
            })
            .addCase(fetchEmployee.fulfilled, (state, { payload }) => {
                state.current = { item: payload, status: 'succeeded', error: null };
            })
            .addCase(fetchEmployee.rejected, (state, { payload }) => {
                state.current.status = 'failed';
                state.current.error = payload?.message ?? 'Could not load this employee.';
            })

            .addCase(fetchEmployeeHistory.pending, (state) => {
                state.history.status = 'loading';
            })
            .addCase(fetchEmployeeHistory.fulfilled, (state, { payload }) => {
                state.history = { items: payload.data, meta: payload.meta, status: 'succeeded' };
            })

            .addCase(updateEmployee.fulfilled, (state, { payload }) => {
                if (state.current.item?.id === payload.data.id) {
                    state.current.item = { ...state.current.item, ...payload.data };
                }
            })
            .addCase(restoreEmployee.fulfilled, (state, { payload }) => {
                state.list.items = state.list.items.filter((e) => e.id !== payload.data.id);
                if (state.list.meta) state.list.meta.total -= 1;
            })
            .addCase(deleteEmployee.fulfilled, (state, { payload }) => {
                state.list.items = state.list.items.filter((e) => e.id !== payload.id);
                if (state.list.meta) state.list.meta.total -= 1;
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default employeesSlice.reducer;
