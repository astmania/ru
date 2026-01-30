import axios from 'axios';
import api from './services/api';

// Экспортируем для обратной совместимости
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Экспортируем настроенный API клиент
window.api = api;
