<template>
    <div class="auth-page">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">Регистрация</div>
                    <div class="card-body">
                        <form @submit.prevent="handleRegister">
                            <div v-if="errorMessage" class="alert alert-danger">
                                {{ errorMessage }}
                            </div>

                            <div class="form-group">
                                <label for="name">Имя</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    required
                                    autocomplete="name"
                                />
                                <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
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
                                <label for="phone">Телефон *</label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.phone }"
                                    placeholder="+7 (___) ___-__-__"
                                    required
                                    autocomplete="tel"
                                    maxlength="18"
                                    @input="formatPhoneInput"
                                />
                                <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone[0] }}</div>
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
                                    autocomplete="new-password"
                                />
                                <div v-if="errors.password" class="invalid-feedback">{{ errors.password[0] }}</div>
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Пароль ещё раз</label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.password_confirmation }"
                                    required
                                    autocomplete="new-password"
                                />
                                <div v-if="errors.password_confirmation" class="invalid-feedback">
                                    {{ errors.password_confirmation[0] }}
                                </div>
                            </div>

                            <button type="submit" class="btn" :disabled="loading">
                                {{ loading ? 'Регистрация…' : 'Зарегистрироваться' }}
                            </button>

                            <p class="auth-footer">
                                Уже есть аккаунт?
                                <router-link to="/login">Войти</router-link>
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
const { register, isAuthenticated } = useAuth();

const form = ref({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

function formatPhone(val) {
    const digits = (val || '').replace(/\D/g, '');
    if (digits.length === 0) return '';
    let d = digits;
    if (d.startsWith('8')) d = '7' + d.slice(1);
    if (d.startsWith('7')) d = d.slice(0, 11);
    else d = '7' + d.slice(0, 10);
    if (d.length <= 1) return d ? '+' + d : '';
    if (d.length <= 4) return `+${d[0]} (${d.slice(1)}`;
    if (d.length <= 7) return `+${d[0]} (${d.slice(1, 4)}) ${d.slice(4)}`;
    if (d.length <= 9) return `+${d[0]} (${d.slice(1, 4)}) ${d.slice(4, 7)}-${d.slice(7)}`;
    return `+${d[0]} (${d.slice(1, 4)}) ${d.slice(4, 7)}-${d.slice(7, 9)}-${d.slice(9, 11)}`;
}

function formatPhoneInput() {
    form.value.phone = formatPhone(form.value.phone);
}
const loading = ref(false);
const errorMessage = ref('');
const errors = ref({});

onMounted(() => {
    if (isAuthenticated.value) router.push('/');
});

const handleRegister = async () => {
    loading.value = true;
    errorMessage.value = '';
    errors.value = {};
    const result = await register(
        form.value.name,
        form.value.email,
        form.value.phone,
        form.value.password,
        form.value.password_confirmation
    );
    if (result.success) router.push('/');
    else {
        errorMessage.value = result.message;
        errors.value = result.errors;
    }
    loading.value = false;
};
</script>

<style scoped>
/* Register использует глобальные стили из app.css */
</style>
