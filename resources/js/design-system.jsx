import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import DesignSystemApp from './components/design-system/DesignSystemApp';
import '../css/design-system.css';
import 'flatpickr/dist/flatpickr.css';

const rootElement = document.getElementById('react-design-system');

if (rootElement) {
    createRoot(rootElement).render(
        <StrictMode>
            <DesignSystemApp />
        </StrictMode>,
    );
}
