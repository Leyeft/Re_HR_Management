import { createAsyncThunk } from '@reduxjs/toolkit';

/**
 * createAsyncThunk for an API call. If the call fails, the error the Axios client
 * produced ({ status, code, message, errors }) becomes the rejected payload, so
 * `dispatch(thunk(arg)).unwrap()` throws that same object.
 *
 *   export const fetchThings = apiThunk('things/fetch', async (params) => (await api.get('/things', { params })).data);
 */
export default function apiThunk(type, request) {
    return createAsyncThunk(type, async (arg, { rejectWithValue }) => {
        try {
            return await request(arg);
        } catch (error) {
            return rejectWithValue(error);
        }
    });
}
