import { createSlice, nanoid } from '@reduxjs/toolkit';

/**
 * Toast notifications (short messages in the corner): "Leave approved", errors, etc.
 * Any part of the app can show one with dispatch(showToast({ type, message })).
 */
const toastSlice = createSlice({
    name: 'toasts',
    initialState: [],
    reducers: {
        showToast: {
            reducer(state, { payload }) {
                state.push(payload);
                // Keep at most 4 on screen.
                if (state.length > 4) state.shift();
            },
            prepare({ type = 'success', message }) {
                return { payload: { id: nanoid(), type, message } };
            },
        },
        dismissToast(state, { payload: id }) {
            return state.filter((toast) => toast.id !== id);
        },
    },
});

export const { showToast, dismissToast } = toastSlice.actions;

// Shortcuts
export const toastSuccess = (message) => showToast({ type: 'success', message });
export const toastError = (message) => showToast({ type: 'error', message });
export const toastInfo = (message) => showToast({ type: 'info', message });

export default toastSlice.reducer;
