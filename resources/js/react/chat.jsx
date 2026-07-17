import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatApp from './components/ChatApp.jsx';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('react-chat-root');
    console.log("React container found:", container);
    if (container) {
        try {
            const root = createRoot(container);
            root.render(<ChatApp />);
            console.log("React mounted successfully!");
        } catch (e) {
            console.error("React failed to mount:", e);
            container.innerHTML = `<div style="padding: 20px; color: red;">React Error: ${e.message}</div>`;
        }
    }
});
