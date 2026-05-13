import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

try {
    if (import.meta.env.VITE_BROADCAST_ENABLED === 'true') {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
            encrypted: true,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
        });
    } else {
        window.Echo = null;
    }
} catch (error) {
    console.error('Failed to initialize Echo:', error);
    window.Echo = null;
}
