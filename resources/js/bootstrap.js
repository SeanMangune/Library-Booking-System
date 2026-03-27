import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
	const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
	const wsPort = Number(import.meta.env.VITE_REVERB_PORT || 8080);
	const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
	const csrfToken = document
		.querySelector('meta[name="csrf-token"]')
		?.getAttribute('content');

	window.Echo = new Echo({
		broadcaster: 'reverb',
		key: reverbKey,
		wsHost,
		wsPort,
		wssPort: wsPort,
		forceTLS: reverbScheme === 'https',
		authEndpoint: '/broadcasting/auth',
		auth: {
			headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
		},
		enabledTransports: ['ws', 'wss'],
	});
}
