<template>
    <div class="profile-edit">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title">Редактирование профиля</h1>
                <p class="page-subtitle">Управление данными аккаунта</p>
            </header>

            <div v-if="loading && !form" class="loading">Загрузка…</div>
            <div v-else-if="!form" class="empty">Войдите для редактирования профиля</div>
            <form v-else @submit.prevent="save" class="profile-form">
                <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

                <div class="form-row form-row--two">
                    <div class="form-section">
                        <div class="form-group">
                            <label>Имя *</label>
                            <input v-model="form.name" type="text" class="form-control" required maxlength="255" />
                            <div v-if="errors.name" class="invalid-feedback">{{ errors.name[0] }}</div>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input v-model="form.email" type="email" class="form-control" required maxlength="255" />
                            <div v-if="errors.email" class="invalid-feedback">{{ errors.email[0] }}</div>
                        </div>
                        <div class="form-group">
                            <label>Телефон *</label>
                            <input
                                ref="phoneInput"
                                v-model="form.phone"
                                type="tel"
                                class="form-control"
                                placeholder="+7 (___) ___-__-__"
                                required
                                maxlength="18"
                                @input="onPhoneInput"
                            />
                            <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone[0] }}</div>
                        </div>
                    </div>
                    <div class="form-section form-section--avatar">
                        <div class="form-group">
                            <label>Фотография</label>
                            <AvatarDropzone v-model="form.avatar" @uploaded="onAvatarUploaded" />
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <router-link to="/" class="btn btn-secondary">Отмена</router-link>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        {{ saving ? 'Сохранение…' : 'Сохранить' }}
                    </button>
                </div>

                <!-- Смена пароля -->
                <section class="password-section">
                    <h2 class="section-title">Смена пароля</h2>
                    <div v-if="passwordSuccess" class="alert alert-success">{{ passwordSuccess }}</div>
                    <div v-if="passwordError" class="alert alert-danger">{{ passwordError }}</div>
                    <div class="form-group">
                        <label>Текущий пароль *</label>
                        <input
                            v-model="passwordForm.current_password"
                            type="password"
                            class="form-control"
                            placeholder="Введите текущий пароль"
                            autocomplete="current-password"
                        />
                        <div v-if="passwordErrors.current_password" class="invalid-feedback">
                            {{ passwordErrors.current_password[0] }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Новый пароль *</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            class="form-control"
                            placeholder="Минимум 8 символов"
                            autocomplete="new-password"
                        />
                        <div v-if="passwordErrors.password" class="invalid-feedback">
                            {{ passwordErrors.password[0] }}
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Подтверждение нового пароля *</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            class="form-control"
                            placeholder="Повторите новый пароль"
                            autocomplete="new-password"
                        />
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="passwordSaving"
                        @click="changePassword"
                    >
                        {{ passwordSaving ? 'Сохранение…' : 'Изменить пароль' }}
                    </button>
                </section>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../services/api';
import { useAuth } from '../composables/useAuth';
import AvatarDropzone from './AvatarDropzone.vue';

const { user, loadUser } = useAuth();
const form = ref(null);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const errors = ref({});
const phoneInput = ref(null);

const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordSaving = ref(false);
const passwordError = ref('');
const passwordSuccess = ref('');
const passwordErrors = ref({});

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

function initForm() {
    const u = user.value;
    if (!u) return null;
    const phone = (u.phone || '').toString();
    const formatted = phone ? formatPhone(phone) : '';
    return {
        name: u.name || '',
        email: u.email || '',
        phone: formatted,
        avatar: u.avatar || '',
    };
}

async function loadProfile() {
    loading.value = true;
    try {
        await loadUser();
        form.value = initForm();
    } catch (e) {
        form.value = initForm();
    } finally {
        loading.value = false;
    }
}

function onPhoneInput() {
    if (!form.value) return;
    form.value.phone = formatPhone(form.value.phone);
}

async function save() {
    if (!form.value) return;
    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    errors.value = {};

    const phoneDigits = (form.value.phone || '').replace(/\D/g, '');
    if (phoneDigits.length < 10) {
        errors.value = { phone: ['Некорректный номер телефона'] };
        saving.value = false;
        return;
    }

    try {
        const { data } = await api.put('/user/profile', {
            name: form.value.name.trim(),
            email: form.value.email.trim(),
            phone: form.value.phone,
            avatar: form.value.avatar || null,
        });
        if (data.user) {
            await loadUser();
            form.value = initForm();
            successMessage.value = 'Профиль обновлён';
        }
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Ошибка сохранения';
        errors.value = e.response?.data?.errors || {};
    } finally {
        saving.value = false;
    }
}

function onAvatarUploaded(userData) {
    if (userData) {
        loadUser();
    }
}

async function changePassword() {
    passwordSaving.value = true;
    passwordError.value = '';
    passwordSuccess.value = '';
    passwordErrors.value = {};

    if (!passwordForm.value.current_password || !passwordForm.value.password || !passwordForm.value.password_confirmation) {
        passwordError.value = 'Заполните все поля';
        passwordSaving.value = false;
        return;
    }
    if (passwordForm.value.password.length < 8) {
        passwordError.value = 'Пароль должен быть не менее 8 символов';
        passwordSaving.value = false;
        return;
    }
    if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        passwordError.value = 'Пароли не совпадают';
        passwordSaving.value = false;
        return;
    }

    try {
        await api.put('/user/password', {
            current_password: passwordForm.value.current_password,
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.password_confirmation,
        });
        passwordSuccess.value = 'Пароль успешно изменён';
        passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    } catch (e) {
        passwordError.value = e.response?.data?.message || 'Ошибка смены пароля';
        passwordErrors.value = e.response?.data?.errors || {};
    } finally {
        passwordSaving.value = false;
    }
}

onMounted(() => {
    loadProfile();
});
</script>

<style scoped>
.profile-edit {
    padding: 28px 0 40px;
}

.page-header {
    margin-bottom: 28px;
}

.page-title {
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 700;
    color: var(--color-text);
}

.page-subtitle {
    margin: 0;
    font-size: 15px;
    color: var(--color-text-muted);
}

.profile-form {
    max-width: 700px;
}

.form-row--two {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 32px;
}

@media (max-width: 768px) {
    .form-row--two {
        grid-template-columns: 1fr;
    }
}

.form-section--avatar {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    max-width: 400px;
    padding: 10px 14px;
    font-size: 15px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
}

.invalid-feedback {
    font-size: 13px;
    color: #dc2626;
    margin-top: 4px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--color-border);
}

.password-section {
    margin-top: 40px;
    padding-top: 32px;
    border-top: 1px solid var(--color-border);
}

.section-title {
    margin: 0 0 20px;
    font-size: 18px;
    font-weight: 600;
    color: var(--color-text);
}

.btn {
    padding: 10px 20px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-flex;
}

.btn-primary {
    background: var(--color-accent);
    color: #fff;
}

.btn-secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 1px solid var(--color-border);
}
</style>
