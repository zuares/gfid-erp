import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const isPusher = import.meta.env.VITE_BROADCAST_CONNECTION === 'pusher';

window.Echo = new Echo({
    broadcaster: isPusher ? 'pusher' : 'reverb',
    key: isPusher ? import.meta.env.VITE_PUSHER_APP_KEY : import.meta.env.VITE_REVERB_APP_KEY,
    cluster: isPusher ? import.meta.env.VITE_PUSHER_APP_CLUSTER : undefined,
    wsHost: isPusher ? undefined : import.meta.env.VITE_REVERB_HOST,
    wsPort: isPusher ? import.meta.env.VITE_PUSHER_PORT ?? 80 : import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: isPusher ? import.meta.env.VITE_PUSHER_PORT ?? 443 : import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: isPusher ? true : (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
