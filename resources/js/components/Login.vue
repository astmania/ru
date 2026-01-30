<template>
    <div class="auth-page">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">Вход</div>
                    <div class="card-body">
                        <form @submit.prevent="handleLogin">
                            <div v-if="errorMessage" class="alert alert-danger">
                                {{ errorMessage }}
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.email }"
                                    placeholder="you@example.com"
                                    required
                                    autocomplete="email"
                                />
                                <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
                            </div>

                            <div class="form-group">
                                <label for="password">Пароль</label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.password }"
                                    required
                                    autocomplete="current-password"
                                />
                                <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
                            </div>

                            <button type="submit" class="btn" :disabled="loading">
                                {{ loading ? 'Вход…' : 'Войти' }}
                            </button>

                            <p class="auth-footer">
                                Нет аккаунта?
                                <router-link to="/register">Регистрация</router-link>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const { login, isAuthenticated } = useAuth();

const form = ref({ email: '', password: '' });
const loading = ref(false);
const errorMessage = ref('');
const errors = ref({});

onMounted(() => {
    if (isAuthenticated.value) router.push('/');
});

const handleLogin = async () => {
    loading.value = true;
    errorMessage.value = '';
    errors.value = {};
    const result = await login(form.value.email, form.value.password);
    if (result.success) router.push('/');
    else {
        errorMessage.value = result.message;
        errors.value = result.errors;
    }
    loading.value = false;
};
</script>

<style scoped>
/* Login использует глобальные стили из app.css */
</style>
