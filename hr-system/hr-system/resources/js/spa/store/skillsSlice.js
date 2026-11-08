import { createSlice } from '@reduxjs/toolkit';
import api from '../api/client';
import apiThunk from './apiThunk';
import { logout, sessionEnded } from './authSlice';

/**
 * Skills (HR/Admin). Employees are linked to skills through the employee_skill
 * pivot table, which also stores each person's level.
 */
export const fetchSkills = apiThunk('skills/fetch', async () => (await api.get('/skills')).data.data);
export const createSkill = apiThunk('skills/create', async (values) => (await api.post('/skills', values)).data);
export const updateSkill = apiThunk('skills/update', async ({ id, ...values }) => (await api.put(`/skills/${id}`, values)).data);
export const deleteSkill = apiThunk('skills/delete', async (id) => {
    const { data } = await api.delete(`/skills/${id}`);
    return { id, message: data.message };
});

export const SKILL_LEVELS = [
    { value: 'beginner', label: 'Beginner' },
    { value: 'intermediate', label: 'Intermediate' },
    { value: 'expert', label: 'Expert' },
];

const byName = (a, b) => a.name.localeCompare(b.name);
const initialState = { items: [], status: 'idle', error: null };

const skillsSlice = createSlice({
    name: 'skills',
    initialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchSkills.pending, (state) => {
                state.status = 'loading';
                state.error = null;
            })
            .addCase(fetchSkills.fulfilled, (state, { payload }) => {
                state.status = 'succeeded';
                state.items = payload;
            })
            .addCase(fetchSkills.rejected, (state, { payload }) => {
                state.status = 'failed';
                state.error = payload?.message ?? 'Could not load skills.';
            })
            .addCase(createSkill.fulfilled, (state, { payload }) => {
                state.items.push(payload.data);
                state.items.sort(byName);
            })
            .addCase(updateSkill.fulfilled, (state, { payload }) => {
                state.items = state.items.map((s) => (s.id === payload.data.id ? payload.data : s)).sort(byName);
            })
            .addCase(deleteSkill.fulfilled, (state, { payload }) => {
                state.items = state.items.filter((s) => s.id !== payload.id);
            })
            .addCase(logout.fulfilled, () => initialState)
            .addCase(sessionEnded, () => initialState);
    },
});

export default skillsSlice.reducer;
