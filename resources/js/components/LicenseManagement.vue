<template>
    <div class="license-management">
        <div class="header">
            <h1>Управление лицензиями</h1>
            <button @click="showCreateModal = true" class="btn btn-primary">
                Создать лицензию
            </button>
        </div>

        <!-- Статистика -->
        <div class="stats" v-if="statistics">
            <div class="stat-card">
                <div class="stat-value">{{ statistics.total }}</div>
                <div class="stat-label">Всего лицензий</div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-value">{{ statistics.active }}</div>
                <div class="stat-label">Активных</div>
            </div>
            <div class="stat-card stat-expired">
                <div class="stat-value">{{ statistics.expired }}</div>
                <div class="stat-label">Истекших</div>
            </div>
            <div class="stat-card stat-inactive">
                <div class="stat-value">{{ statistics.inactive }}</div>
                <div class="stat-label">Неактивных</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters">
            <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Поиск по ключу, email, имени..."
                class="form-control"
                @input="loadLicenses"
            />
            <select v-model="filters.type" @change="loadLicenses" class="form-control">
                <option value="">Все типы</option>
                <option value="trial">Trial</option>
                <option value="basic">Basic</option>
                <option value="premium">Premium</option>
                <option value="enterprise">Enterprise</option>
            </select>
            <select v-model="filters.status" @change="loadLicenses" class="form-control">
                <option value="">Все статусы</option>
                <option value="active">Активные</option>
                <option value="expired">Истекшие</option>
                <option value="inactive">Неактивные</option>
            </select>
        </div>

        <!-- Таблица лицензий -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ключ лицензии</th>
                        <th>Тип</th>
                        <th>Клиент</th>
                        <th>Домен</th>
                        <th>Истекает</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="license in licenses.data" :key="license.id">
                        <td>
                            <code>{{ license.license_key }}</code>
                            <button 
                                @click="copyToClipboard(license.license_key)"
                                class="btn-copy"
                                title="Копировать"
                            >
                                📋
                            </button>
                        </td>
                        <td>
                            <span class="badge badge-{{ license.type }}">
                                {{ license.type }}
                            </span>
                        </td>
                        <td>
                            <div v-if="license.customer_name">{{ license.customer_name }}</div>
                            <div v-if="license.customer_email" class="text-muted small">
                                {{ license.customer_email }}
                            </div>
                        </td>
                        <td>{{ license.domain || '-' }}</td>
                        <td>
                            <span v-if="license.expires_at">
                                {{ formatDate(license.expires_at) }}
                            </span>
                            <span v-else class="text-muted">Без ограничений</span>
                        </td>
                        <td>
                            <span 
                                class="badge"
                                :class="{
                                    'badge-success': license.is_active && isLicenseValid(license),
                                    'badge-danger': !license.is_active || isLicenseExpired(license)
                                }"
                            >
                                {{ getStatusText(license) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button 
                                    @click="editLicense(license)"
                                    class="btn btn-sm btn-primary"
                                    title="Редактировать"
                                >
                                    ✏️
                                </button>
                                <button 
                                    @click="toggleLicenseStatus(license)"
                                    class="btn btn-sm"
                                    :class="license.is_active ? 'btn-warning' : 'btn-success'"
                                    :title="license.is_active ? 'Деактивировать' : 'Активировать'"
                                >
                                    {{ license.is_active ? '⏸️' : '▶️' }}
                                </button>
                                <button 
                                    @click="deleteLicense(license)"
                                    class="btn btn-sm btn-danger"
                                    title="Удалить"
                                >
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <div class="pagination" v-if="licenses.last_page > 1">
            <button 
                @click="changePage(licenses.current_page - 1)"
                :disabled="licenses.current_page === 1"
                class="btn"
            >
                Назад
            </button>
            <span>Страница {{ licenses.current_page }} из {{ licenses.last_page }}</span>
            <button 
                @click="changePage(licenses.current_page + 1)"
                :disabled="licenses.current_page === licenses.last_page"
                class="btn"
            >
                Вперед
            </button>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <div v-if="showCreateModal || editingLicense" class="modal-overlay" @click="closeModal">
            <div class="modal" @click.stop>
                <div class="modal-header">
                    <h2>{{ editingLicense ? 'Редактировать лицензию' : 'Создать лицензию' }}</h2>
                    <button @click="closeModal" class="btn-close">×</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveLicense">
                        <div class="form-group">
                            <label>Тип лицензии *</label>
                            <select v-model="form.type" required class="form-control">
                                <option value="trial">Trial</option>
                                <option value="basic">Basic</option>
                                <option value="premium">Premium</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ключ лицензии</label>
                            <input 
                                v-model="form.license_key" 
                                type="text" 
                                class="form-control"
                                placeholder="Оставьте пустым для автогенерации"
                            />
                        </div>

                        <div class="form-group">
                            <label>Домен</label>
                            <input 
                                v-model="form.domain" 
                                type="text" 
                                class="form-control"
                                placeholder="example.com"
                            />
                        </div>

                        <div class="form-group">
                            <label>Дата истечения</label>
                            <input 
                                v-model="form.expires_at" 
                                type="date" 
                                class="form-control"
                            />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Макс. пользователей</label>
                                <input 
                                    v-model.number="form.max_users" 
                                    type="number" 
                                    class="form-control"
                                    min="1"
                                />
                            </div>
                            <div class="form-group">
                                <label>Макс. запросов/месяц</label>
                                <input 
                                    v-model.number="form.max_requests_per_month" 
                                    type="number" 
                                    class="form-control"
                                    min="1"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Имя клиента</label>
                            <input 
                                v-model="form.customer_name" 
                                type="text" 
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label>Email клиента</label>
                            <input 
                                v-model="form.customer_email" 
                                type="email" 
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label>Примечания</label>
                            <textarea 
                                v-model="form.notes" 
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input 
                                    v-model="form.is_active" 
                                    type="checkbox"
                                />
                                Активна
                            </label>
                        </div>

                        <div class="modal-actions">
                            <button type="button" @click="closeModal" class="btn btn-secondary">
                                Отмена
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="loading">
                                {{ loading ? 'Сохранение...' : 'Сохранить' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import api from '../services/api';

const licenses = ref({ data: [], current_page: 1, last_page: 1 });
const statistics = ref(null);
const loading = ref(false);
const showCreateModal = ref(false);
const editingLicense = ref(null);

const filters = reactive({
    search: '',
    type: '',
    status: '',
});

const form = reactive({
    type: 'trial',
    license_key: '',
    domain: '',
    expires_at: '',
    max_users: null,
    max_requests_per_month: null,
    customer_name: '',
    customer_email: '',
    notes: '',
    is_active: true,
});

const loadStatistics = async () => {
    try {
        const response = await api.get('/license/statistics');
        statistics.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки статистики:', error);
    }
};

const loadLicenses = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: 20,
            ...filters,
        };
        const response = await api.get('/license/list', { params });
        licenses.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки лицензий:', error);
        alert('Ошибка загрузки лицензий');
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    loadLicenses(page);
};

const saveLicense = async () => {
    loading.value = true;
    try {
        if (editingLicense.value) {
            await api.put(`/license/${editingLicense.value.id}`, form);
        } else {
            await api.post('/license/create', form);
        }
        closeModal();
        loadLicenses();
        loadStatistics();
    } catch (error) {
        console.error('Ошибка сохранения:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения лицензии');
    } finally {
        loading.value = false;
    }
};

const editLicense = (license) => {
    editingLicense.value = license;
    Object.assign(form, {
        type: license.type,
        license_key: license.license_key,
        domain: license.domain || '',
        expires_at: license.expires_at ? license.expires_at.split('T')[0] : '',
        max_users: license.max_users,
        max_requests_per_month: license.max_requests_per_month,
        customer_name: license.customer_name || '',
        customer_email: license.customer_email || '',
        notes: license.notes || '',
        is_active: license.is_active,
    });
};

const toggleLicenseStatus = async (license) => {
    if (!confirm(`Вы уверены, что хотите ${license.is_active ? 'деактивировать' : 'активировать'} эту лицензию?`)) {
        return;
    }
    
    try {
        await api.post(`/license/${license.id}/toggle-status`);
        loadLicenses();
        loadStatistics();
    } catch (error) {
        console.error('Ошибка:', error);
        alert('Ошибка изменения статуса');
    }
};

const deleteLicense = async (license) => {
    if (!confirm(`Вы уверены, что хотите удалить лицензию ${license.license_key}?`)) {
        return;
    }
    
    try {
        await api.delete(`/license/${license.id}`);
        loadLicenses();
        loadStatistics();
    } catch (error) {
        console.error('Ошибка:', error);
        alert(error.response?.data?.message || 'Ошибка удаления лицензии');
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    editingLicense.value = null;
    Object.assign(form, {
        type: 'trial',
        license_key: '',
        domain: '',
        expires_at: '',
        max_users: null,
        max_requests_per_month: null,
        customer_name: '',
        customer_email: '',
        notes: '',
        is_active: true,
    });
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        alert('Скопировано в буфер обмена');
    } catch (error) {
        console.error('Ошибка копирования:', error);
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU');
};

const isLicenseValid = (license) => {
    if (!license.is_active) return false;
    if (!license.expires_at) return true;
    return new Date(license.expires_at) > new Date();
};

const isLicenseExpired = (license) => {
    if (!license.expires_at) return false;
    return new Date(license.expires_at) <= new Date();
};

const getStatusText = (license) => {
    if (!license.is_active) return 'Неактивна';
    if (isLicenseExpired(license)) return 'Истекла';
    return 'Активна';
};

onMounted(() => {
    loadLicenses();
    loadStatistics();
});
</script>

<style scoped>
.license-management {
    padding: 24px 32px;
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 600;
    color: var(--color-text);
}

.stat-label {
    font-size: 14px;
    color: var(--color-text-muted);
    margin-top: 8px;
}

.filters {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.filters .form-control {
    flex: 1;
    min-width: 200px;
}

.table-container {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    overflow: hidden;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead {
    background: var(--color-bg);
}

.table th,
.table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.table th {
    font-weight: 600;
    font-size: 13px;
    color: var(--color-text-muted);
    text-transform: uppercase;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-trial { background: #e0e0e0; color: #333; }
.badge-basic { background: #2196F3; color: white; }
.badge-premium { background: #FF9800; color: white; }
.badge-enterprise { background: #4CAF50; color: white; }
.badge-success { background: #4CAF50; color: white; }
.badge-danger { background: #f44336; color: white; }

.actions {
    display: flex;
    gap: 4px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 14px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 24px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: var(--color-surface);
    border-radius: var(--radius);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--color-border);
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--color-text-muted);
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}

.btn-copy {
    background: none;
    border: none;
    cursor: pointer;
    margin-left: 8px;
    font-size: 14px;
}

.text-muted {
    color: var(--color-text-muted);
}

.small {
    font-size: 12px;
}
</style>
