import { useCallback, useState } from 'react';
import { useDispatch } from 'react-redux';
import { toastError } from '../store/toastSlice';

/**
 * State for one form that is sent to the API.
 *
 *   const form = useApiForm({ name: '', email: '' });
 *   <TextInput value={form.data.name} onChange={form.field('name')} />
 *   <InputError message={form.errors.name} />
 *   form.submit((data) => dispatch(saveThing(data)).unwrap(), { onSuccess })
 *
 * - Field errors from the API ({ errors: { name: ["…"] } }) appear under each input.
 * - A rule error with no field (e.g. "not enough leave left") is shown as a toast.
 * The typed values are local to the form; saved records live in the Redux store.
 */
export default function useApiForm(initial) {
    const dispatch = useDispatch();
    const [data, setDataState] = useState(initial);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const setData = useCallback((keyOrValues, value) => {
        setDataState((d) => (typeof keyOrValues === 'string' ? { ...d, [keyOrValues]: value } : { ...d, ...keyOrValues }));
    }, []);

    // onChange handler for an input, select or checkbox.
    const field = useCallback(
        (key) => (e) => setData(key, e.target.type === 'checkbox' ? e.target.checked : e.target.value),
        [setData],
    );

    const reset = useCallback((values = initial) => {
        setDataState(values);
        setErrors({});
    }, [initial]);

    const submit = useCallback(
        async (send, { onSuccess, onError } = {}) => {
            setProcessing(true);
            setErrors({});
            try {
                const result = await send(data);
                onSuccess?.(result);
                return result;
            } catch (error) {
                const fieldErrors = Object.fromEntries(
                    Object.entries(error?.errors ?? {}).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : messages]),
                );
                setErrors(fieldErrors);
                if (!Object.keys(fieldErrors).length || error.code !== 'validation_error') {
                    dispatch(toastError(error?.message ?? 'Something went wrong.'));
                }
                onError?.(error);
                return undefined;
            } finally {
                setProcessing(false);
            }
        },
        [data, dispatch],
    );

    return { data, setData, field, errors, setErrors, processing, submit, reset };
}
