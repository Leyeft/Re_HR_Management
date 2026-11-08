import axios from 'axios';

/**
 * One Axios instance for every API call.
 *
 * - Sends the session cookie and the XSRF token (Sanctum SPA auth).
 * - Turns every failure into the same shape the API uses:
 *     { status, code, message, errors }
 *   so pages and slices handle errors one way.
 * - On 419 (CSRF token expired) it fetches a fresh token and retries once.
 * - On 401 it tells the store the session has ended (see setUnauthenticatedHandler).
 */
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Sets the XSRF-TOKEN cookie. Call before signing in.
export const csrf = () => axios.get('/sanctum/csrf-cookie', { withCredentials: true });

let onUnauthenticated = () => {};
export function setUnauthenticatedHandler(handler) {
    onUnauthenticated = handler;
}

export function normalizeError(error) {
    const res = error?.response;

    if (!res) {
        return {
            status: 0,
            code: 'network_error',
            message: 'Cannot reach the server. Check your connection and try again.',
            errors: {},
        };
    }

    return {
        status: res.status,
        code: res.data?.code ?? 'http_error',
        message: res.data?.message ?? 'Something went wrong. Please try again.',
        errors: res.data?.errors ?? {},
    };
}

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const original = error.config;

        // Expired CSRF token: refresh it and retry the request once.
        if (error.response?.status === 419 && original && !original._retried) {
            original._retried = true;
            await csrf();
            return api(original);
        }

        const normalized = normalizeError(error);

        // Session ended (or never started). Checking "who am I" on page load is
        // allowed to fail quietly, so it opts out with { silent401: true }.
        if (normalized.status === 401 && !original?.silent401) {
            onUnauthenticated(normalized);
        }

        return Promise.reject(normalized);
    },
);

export default api;
