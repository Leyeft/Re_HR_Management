import { useEffect } from 'react';
import { APP_NAME } from '../config';

// Sets the browser tab title, e.g. "Employees · Crewly".
export default function useDocumentTitle(title) {
    useEffect(() => {
        document.title = title ? `${title} · ${APP_NAME}` : APP_NAME;
    }, [title]);
}
