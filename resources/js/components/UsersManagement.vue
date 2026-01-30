<template>
    <div class="users-management">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title">Управление пользователями</h1>
                <p class="page-subtitle">Создание, редактирование и удаление сотрудников</p>
                <div class="header-actions">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Поиск по имени, email, телефону..."
                        class="form-control search-input"
                        @input="loadUsers"
                    />
                    <button type="button" class="btn btn-primary" @click="openCreate">
                        + Добавить пользователя
                    </button>
                </div>
            </header>

            <div v-if="loading" class="loading">Загрузка…</div>
            <div v-else-if="!users.length" class="empty-state">Нет пользователей</div>
            <div v-else class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Пользователь</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th>Роли</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td>
                                <div class="user-cell">
                                    <img v-if="u.avatar" :src="u.avatar" :alt="u.name" class="user-avatar" />
                                    <div v-else class="user-avatar-placeholder">{{ (u.name || '?')[0] }}</div>
                                    <div>
                                        <strong>{{ u.name }}</strong>
                                    </div>
                                </div>
                            </td>
                            <td>{{ u.email }}</td>
                            <td>{{ formatPhone(u.phone) }}</td>
                            <td>
                                <span v-if="u.is_super_admin" class="badge badge-super">Супер-админ</span>
                                <span v-if="u.is_admin" class="badge badge-admin">Админ</span>
                                <span v-if="u.is_moderator" class="badge badge-moderator">Модератор</span>
                                <span v-if="!u.is_super_admin && !u.is_admin && !u.is_moderator" class="badge badge-user">Пользователь</span>
                            </td>
                            <td>
                                <button type="button" class="btn-icon" @click="openEdit(u)" title="Редактировать">✎</button>
                                <button type="button" class="btn-icon" @click="openChangePassword(u)" title="Сменить пароль">🔑</button>
                                <button
                                    type="button"
                                    class="btn-icon btn-icon--danger"
                                    :disabled="u.is_super_admin && superAdminCount <= 1"
                                    @click="openDelete(u)"
                                    title="Удалить"
                                >
                                    ×
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <div v-if="showUserModal" class="modal-overlay" @click.self="showUserModal = false">
            <div class="modal modal--wide">
                <h3 class="modal-title">{{ editingUser ? 'Редактировать пользователя' : 'Новый пользователь' }}</h3>
                <form @submit.prevent="saveUser" class="user-form">
                    <div v-if="userError" class="alert alert-danger">{{ userError }}</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Имя *</label>
                            <input v-model="userForm.name" type="text" class="form-control" required maxlength="255" />
                            <div v-if="userErrors.name" class="invalid-feedback">{{ userErrors.name[0] }}</div>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <input
                                v-model="userForm.email"
                                type="email"
                                class="form-control"
                                required
                                maxlength="255"
                                :readonly="!!editingUser"
                            />
                            <div v-if="userErrors.email" class="invalid-feedback">{{ userErrors.email[0] }}</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input
                            v-model="userForm.phone"
                            type="tel"
                            class="form-control"
                            placeholder="+7 (___) ___-__-__"
                            required
                            maxlength="18"
                            @input="formatPhoneInput"
                        />
                        <div v-if="userErrors.phone" class="invalid-feedback">{{ userErrors.phone[0] }}</div>
                    </div>
                    <div v-if="!editingUser" class="form-row">
                        <div class="form-group">
                            <label>Пароль *</label>
                            <input v-model="userForm.password" type="password" class="form-control" required minlength="8" />
                            <div v-if="userErrors.password" class="invalid-feedback">{{ userErrors.password[0] }}</div>
                        </div>
                        <div class="form-group">
                            <label>Подтверждение пароля *</label>
                            <input v-model="userForm.password_confirmation" type="password" class="form-control" required />
                        </div>
                    </div>
                    <div class="form-group form-group--roles">
                        <label>Роли</label>
                        <div class="roles-checkboxes">
                            <label class="checkbox-label">
                                <input v-model="userForm.is_admin" type="checkbox" />
                                Администратор
                            </label>
                            <label class="checkbox-label">
                                <input v-model="userForm.is_moderator" type="checkbox" />
                                Модератор (Шежіре)
                            </label>
                            <label class="checkbox-label">
                                <input v-model="userForm.is_super_admin" type="checkbox" />
                                Супер-администратор
                            </label>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="showUserModal = false">Отмена</button>
                        <button type="submit" class="btn btn-primary" :disabled="userSaving">
                            {{ userSaving ? 'Сохранение…' : (editingUser ? 'Сохранить' : 'Создать') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Модальное окно смены пароля -->
        <div v-if="passwordUser" class="modal-overlay" @click.self="passwordUser = null">
            <div class="modal">
                <h3 class="modal-title">Смена пароля: {{ passwordUser.name }}</h3>
                <form @submit.prevent="savePassword" class="user-form">
                    <div v-if="passwordError" class="alert alert-danger">{{ passwordError }}</div>
                    <div class="form-group">
                        <label>Новый пароль *</label>
                        <input v-model="passwordForm.password" type="password" class="form-control" required minlength="8" />
                        <div v-if="passwordErrors.password" class="invalid-feedback">{{ passwordErrors.password[0] }}</div>
                    </div>
                    <div class="form-group">
                        <label>Подтверждение пароля *</label>
                        <input v-model="passwordForm.password_confirmation" type="password" class="form-control" required />
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" @click="passwordUser = null">Отмена</button>
                        <button type="submit" class="btn btn-primary" :disabled="passwordSaving">
                            {{ passwordSaving ? 'Сохранение…' : 'Изменить пароль' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Модальное окно удаления -->
        <div v-if="deletingUser" class="modal-overlay" @click.self="deletingUser = null">
            <div class="modal">
                <h3 class="modal-title">Удалить пользователя?</h3>
                <p>Вы уверены, что хотите удалить <strong>{{ deletingUser.name }}</strong> ({{ deletingUser.email }})? Это действие нельзя отменить.</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" @click="deletingUser = null">Отмена</button>
                    <button type="button" class="btn btn-danger" :disabled="deleteSaving" @click="confirmDelete">
                        {{ deleteSaving ? 'Удаление…' : 'Удалить' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';

const users = ref([]);
const loading = ref(true);
const search = ref('');
const superAdminCount = ref(0);

const showUserModal = ref(false);
const editingUser = ref(null);
const userForm = ref({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
    is_moderator: false,
    is_super_admin: false,
});
const userSaving = ref(false);
const userError = ref('');
const userErrors = ref({});

const passwordUser = ref(null);
const passwordForm = ref({ password: '', password_confirmation: '' });
const passwordSaving = ref(false);
const passwordError = ref('');
const passwordErrors = ref({});

const deletingUser = ref(null);
const deleteSaving = ref(false);

function formatPhone(val) {
    if (!val) return '—';
    const digits = String(val).replace(/\D/g, '');
    if (digits.length < 10) return val;
    return `+${digits[0]} (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9, 11)}`;
}

function formatPhoneInput() {
    if (!userForm.value) return;
    userForm.value.phone = formatPhoneForInput(userForm.value.phone);
}

function formatPhoneForInput(val) {
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

async function loadUsers() {
    loading.value = true;
    try {
        const { data } = await api.get('/admin/users', {
            params: { search: search.value || undefined, per_page: 100 },
        });
        const list = Array.isArray(data) ? data : (data?.data || []);
        users.value = list;
        superAdminCount.value = data?.total_super_admins ?? list.filter((u) => u.is_super_admin).length;
    } catch (e) {
        users.value = [];
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    editingUser.value = null;
    userForm.value = {
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        is_admin: false,
        is_moderator: false,
        is_super_admin: false,
    };
    userError.value = '';
    userErrors.value = {};
    showUserModal.value = true;
}

function openEdit(u) {
    editingUser.value = u;
    userForm.value = {
        name: u.name || '',
        email: u.email || '',
        phone: formatPhoneForInput(u.phone || ''),
        password: '',
        password_confirmation: '',
        is_admin: !!u.is_admin,
        is_moderator: !!u.is_moderator,
        is_super_admin: !!u.is_super_admin,
    };
    userError.value = '';
    userErrors.value = {};
    showUserModal.value = true;
}

async function saveUser() {
    userSaving.value = true;
    userError.value = '';
    userErrors.value = {};

    const payload = {
        name: userForm.value.name.trim(),
        email: userForm.value.email.trim(),
        phone: userForm.value.phone,
        is_admin: userForm.value.is_admin,
        is_moderator: userForm.value.is_moderator,
        is_super_admin: userForm.value.is_super_admin,
    };

    if (!editingUser.value) {
        payload.password = userForm.value.password;
        payload.password_confirmation = userForm.value.password_confirmation;
    }

    try {
        if (editingUser.value) {
            await api.put(`/admin/users/${editingUser.value.id}`, payload);
        } else {
            await api.post('/admin/users', payload);
        }
        showUserModal.value = false;
        loadUsers();
    } catch (e) {
        userError.value = e.response?.data?.message || 'Ошибка сохранения';
        userErrors.value = e.response?.data?.errors || {};
    } finally {
        userSaving.value = false;
    }
}

function openChangePassword(u) {
    passwordUser.value = u;
    passwordForm.value = { password: '', password_confirmation: '' };
    passwordError.value = '';
    passwordErrors.value = {};
}

async function savePassword() {
    if (!passwordUser.value) return;
    passwordSaving.value = true;
    passwordError.value = '';
    passwordErrors.value = {};

    try {
        await api.put(`/admin/users/${passwordUser.value.id}/password`, {
            password: passwordForm.value.password,
            password_confirmation: passwordForm.value.password_confirmation,
        });
        passwordUser.value = null;
    } catch (e) {
        passwordError.value = e.response?.data?.message || 'Ошибка смены пароля';
        passwordErrors.value = e.response?.data?.errors || {};
    } finally {
        passwordSaving.value = false;
    }
}

function openDelete(u) {
    deletingUser.value = u;
}

async function confirmDelete() {
    if (!deletingUser.value) return;
    deleteSaving.value = true;
    try {
        await api.delete(`/admin/users/${deletingUser.value.id}`);
        deletingUser.value = null;
        loadUsers();
    } catch (e) {
        alert(e.response?.data?.message || 'Ошибка удаления');
    } finally {
        deleteSaving.value = false;
    }
}

onMounted(loadUsers);
</script>

<style scoped>
.users-management {
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
    margin: 0 0 20px;
    font-size: 15px;
    color: var(--color-text-muted);
}

.header-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.search-input {
    max-width: 320px;
}

.table-container {
    overflow-x: auto;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.table th {
    font-weight: 600;
    font-size: 13px;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:hover {
    background: rgba(0, 0, 0, 0.02);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.user-avatar-placeholder {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--color-accent);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
}

.badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 11px;
    border-radius: 6px;
    margin-right: 4px;
}

.badge-super {
    background: #fef3c7;
    color: #92400e;
}

.badge-admin {
    background: #dbeafe;
    color: #1e40af;
}

.badge-moderator {
    background: #d1fae5;
    color: #065f46;
}

.badge-user {
    background: #f3f4f6;
    color: #6b7280;
}

.btn-icon {
    padding: 6px 10px;
    font-size: 14px;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    cursor: pointer;
    margin-right: 6px;
}

.btn-icon--danger {
    color: #dc2626;
    border-color: #dc2626;
}

.btn-icon:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: var(--color-surface);
    border-radius: 12px;
    padding: 24px;
    max-width: 480px;
    width: 90%;
}

.modal--wide {
    max-width: 560px;
}

.modal-title {
    margin: 0 0 20px;
    font-size: 18px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 16px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    font-size: 15px;
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.roles-checkboxes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn {
    padding: 10px 20px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    border: none;
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

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
}

.invalid-feedback {
    font-size: 13px;
    color: #dc2626;
    margin-top: 4px;
}
</style>
