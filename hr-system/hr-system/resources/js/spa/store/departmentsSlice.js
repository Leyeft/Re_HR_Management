import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Departments. The whole list is loaded once (there are only a handful) and then
 * searched in the browser; it also feeds the department dropdowns.
 */
export const fetchDepartments = apiThunk('departments/fetch', async () => (await api.get('/departments', { params: { per_page: 'all' } })).data.data);

export const createDepartment = apiThunk('departments/create', async (values) => (await api.post('/departments', values)).data);

export const updateDepartment = apiThunk('departments/update', async ({ id, ...values }) => (await api.put(`/departments/${id}`, values)).data);

export const deleteDepartment = apiThunk('departments/delete', async (id) => {
        const { data } = await api.delete(`/departments/${id}`);
        return { id, message: data.message };
    });

const byName = (a, b) => a.name.localeCompare(b.name);
const initialState = { items: [], status: 'idle', error: null };

const departmentsSlice = createSlice({
    name: 'departments',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchDepartments.pending, (state) => {
                state.status = 'loading';
                state.error = null;
            })
            .addCase(fetchDepartments.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.items = payload;
            })
            .addCase(fetchDepartments.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load departments.';
            })
            .addCase(createDepartment.fulfilled, (state, { payload }) => {
                state.items.push(payload.data);
                state.items.sort(byName);
            })
            .addCase(updateDepartment.fulfilled, (state, { payload }) => {
                state.items = state.items.map((d) => (d.id === payload.data.id ? payload.data : d)).sort(byName);
            })
            .addCase(deleteDepartment.fulfilled, (state, { payload }) => {
                state.items = state.items.filter((d) => d.id !== payload.id);
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default departmentsSlice.reducer;
