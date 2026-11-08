import '../../css/app.css';

import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import Toasts from './components/Toasts';
import { BASE_PATH } from './config';
import AppRoutes from './routes/AppRoutes';
import { store } from './store';
import { fetchMe } from './store/authSlice';

// Find out straight away whether there's already a session (e.g. after a page refresh).
store.dispatch(fetchMe());

createRoot(document.getElementById('root')).render(
    <StrictMode>
        <Provider store={store}>
            <BrowserRouter basename={BASE_PATH}>
                <AppRoutes />
                <Toasts />
            </BrowserRouter>
        </Provider>
    </StrictMode>,
);
