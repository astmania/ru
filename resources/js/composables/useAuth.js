import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const user = ref(JSON.parse(localStorage.getItem('user') || 'null'));
const accessToken = ref(localStorage.getItem('access_token') || null);

export function useAuth() {
    const router = useRouter();
    const isAuthenticated = computed(() => !!accessToken.value && !!user.value);

    const login = async (email, password) => {
        try {
            const response = await api.post('/login', { email, password });
            const { user: userData, access_token } = response.data;
            
            // Сохраняем данные
            user.value = userData;
            accessToken.value = access_token;
            localStorage.setItem('access_token', access_token);
            localStorage.setItem('user', JSON.stringify(userData));
            
            return { success: true };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Ошибка при авторизации',
                errors: error.response?.data?.errors || {},
            };
        }
    };

    const register = async (name, email, phone, password, password_confirmation) => {
        try {
            const response = await api.post('/register', {
                name,
                email,
                phone: phone ? String(phone).replace(/\D/g, '') : '',
                password,
                password_confirmation,
            });
            const { user: userData, access_token } = response.data;
            
            // Сохраняем данные
            user.value = userData;
            accessToken.value = access_token;
            localStorage.setItem('access_token', access_token);
            localStorage.setItem('user', JSON.stringify(userData));
            
            return { success: true };
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'Ошибка при регистрации',
                errors: error.response?.data?.errors || {},
            };
        }
    };

    const logout = async () => {
        try {
            await api.post('/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Очищаем данные в любом случае
            user.value = null;
            accessToken.value = null;
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            router.push('/login');
        }
    };

    const loadUser = async () => {
        try {
            const response = await api.get('/user');
            user.value = response.data.user;
            localStorage.setItem('user', JSON.stringify(response.data.user));
        } catch (error) {
            // Если ошибка авторизации, очищаем данные
            user.value = null;
            accessToken.value = null;
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
        }
    };

    // Загружаем пользователя из localStorage при инициализации (если есть токен)
    if (accessToken.value && !user.value) {
        loadUser().catch(() => {
            // Игнорируем ошибки при начальной загрузке
        });
    }

    return {
        user: computed(() => user.value),
        isAuthenticated,
        login,
        register,
        logout,
        loadUser,
    };
}
