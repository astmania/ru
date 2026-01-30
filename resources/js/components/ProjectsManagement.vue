<template>
    <div class="projects-management">
        <div class="header">
            <h1>Управление развернутыми проектами</h1>
            <div class="header-actions">
                <button @click="checkAllHealth" class="btn btn-secondary" :disabled="checkingHealth">
                    {{ checkingHealth ? 'Проверка...' : 'Проверить все' }}
                </button>
                <button @click="showCreateModal = true" class="btn btn-primary">
                    Добавить проект
                </button>
            </div>
        </div>

        <!-- Статистика -->
        <div class="stats" v-if="statistics">
            <div class="stat-card">
                <div class="stat-value">{{ statistics.total }}</div>
                <div class="stat-label">Всего проектов</div>
            </div>
            <div class="stat-card stat-healthy">
                <div class="stat-value">{{ statistics.healthy }}</div>
                <div class="stat-label">Здоровых</div>
            </div>
            <div class="stat-card stat-error">
                <div class="stat-value">{{ statistics.error }}</div>
                <div class="stat-label">С ошибками</div>
            </div>
            <div class="stat-card stat-maintenance">
                <div class="stat-value">{{ statistics.maintenance }}</div>
                <div class="stat-label">На обслуживании</div>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="filters">
            <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Поиск по названию, URL, email..."
                class="form-control"
                @input="loadProjects"
            />
            <select v-model="filters.status" @change="loadProjects" class="form-control">
                <option value="">Все статусы</option>
                <option value="active">Активные</option>
                <option value="error">Ошибки</option>
                <option value="maintenance">Обслуживание</option>
                <option value="inactive">Неактивные</option>
            </select>
        </div>

        <!-- Таблица проектов -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>URL</th>
                        <th>Лицензия</th>
                        <th>Статус</th>
                        <th>Последняя проверка</th>
                        <th>Контакты</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="project in projects.data" :key="project.id">
                        <td>
                            <strong>{{ project.name }}</strong>
                            <div v-if="project.notes" class="text-muted small">
                                {{ project.notes }}
                            </div>
                        </td>
                        <td>
                            <a :href="project.url" target="_blank" class="link">
                                {{ project.url }}
                            </a>
                        </td>
                        <td>
                            <span v-if="project.license" class="badge badge-{{ project.license.type }}">
                                {{ project.license.type }}
                            </span>
                            <span v-else class="text-muted">-</span>
                        </td>
                        <td>
                            <span 
                                class="badge"
                                :class="{
                                    'badge-success': project.status === 'active',
                                    'badge-danger': project.status === 'error',
                                    'badge-warning': project.status === 'maintenance',
                                    'badge-secondary': project.status === 'inactive'
                                }"
                            >
                                {{ getStatusText(project.status) }}
                            </span>
                            <div v-if="project.health_status" class="health-indicator">
                                <span 
                                    class="dot"
                                    :class="{
                                        'dot-green': project.health_status.overall_status === 'healthy',
                                        'dot-yellow': project.health_status.overall_status === 'warning',
                                        'dot-red': project.health_status.overall_status === 'error'
                                    }"
                                ></span>
                                {{ project.health_status.overall_status }}
                            </div>
                        </td>
                        <td>
                            <span v-if="project.last_health_check">
                                {{ formatDate(project.last_health_check) }}
                            </span>
                            <span v-else class="text-muted">Никогда</span>
                        </td>
                        <td>
                            <div v-if="project.contact_name">{{ project.contact_name }}</div>
                            <div v-if="project.contact_email" class="text-muted small">
                                {{ project.contact_email }}
                            </div>
                        </td>
                        <td>
                            <div class="actions">
                                <button 
                                    @click="checkProjectHealth(project)"
                                    class="btn btn-sm btn-info"
                                    title="Проверить здоровье"
                                    :disabled="checkingHealth"
                                >
                                    🔍
                                </button>
                                <button 
                                    @click="editProject(project)"
                                    class="btn btn-sm btn-primary"
                                    title="Редактировать"
                                >
                                    ✏️
                                </button>
                                <button 
                                    @click="deleteProject(project)"
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
        <div class="pagination" v-if="projects.last_page > 1">
            <button 
                @click="changePage(projects.current_page - 1)"
                :disabled="projects.current_page === 1"
                class="btn"
            >
                Назад
            </button>
            <span>Страница {{ projects.current_page }} из {{ projects.last_page }}</span>
            <button 
                @click="changePage(projects.current_page + 1)"
                :disabled="projects.current_page === projects.last_page"
                class="btn"
            >
                Вперед
            </button>
        </div>

        <!-- Модальное окно создания/редактирования -->
        <div v-if="showCreateModal || editingProject" class="modal-overlay" @click="closeModal">
            <div class="modal" @click.stop>
                <div class="modal-header">
                    <h2>{{ editingProject ? 'Редактировать проект' : 'Добавить проект' }}</h2>
                    <button @click="closeModal" class="btn-close">×</button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="saveProject">
                        <div class="form-group">
                            <label>Название проекта *</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                class="form-control"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label>URL проекта *</label>
                            <input 
                                v-model="form.url" 
                                type="url" 
                                class="form-control"
                                required
                                placeholder="http://example.com"
                            />
                        </div>

                        <div class="form-group">
                            <label>Ключ лицензии</label>
                            <input 
                                v-model="form.license_key" 
                                type="text" 
                                class="form-control"
                            />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>IP сервера</label>
                                <input 
                                    v-model="form.server_ip" 
                                    type="text" 
                                    class="form-control"
                                    placeholder="192.168.1.1"
                                />
                            </div>
                            <div class="form-group">
                                <label>Пользователь сервера</label>
                                <input 
                                    v-model="form.server_user" 
                                    type="text" 
                                    class="form-control"
                                    placeholder="root"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label>SSH ключ</label>
                            <textarea 
                                v-model="form.ssh_key" 
                                class="form-control"
                                rows="4"
                                placeholder="-----BEGIN RSA PRIVATE KEY-----..."
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <label>Имя контакта</label>
                            <input 
                                v-model="form.contact_name" 
                                type="text" 
                                class="form-control"
                            />
                        </div>

                        <div class="form-group">
                            <label>Email контакта</label>
                            <input 
                                v-model="form.contact_email" 
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

                        <div class="form-group" v-if="editingProject">
                            <label>Статус</label>
                            <select v-model="form.status" class="form-control">
                                <option value="active">Активный</option>
                                <option value="error">Ошибка</option>
                                <option value="maintenance">Обслуживание</option>
                                <option value="inactive">Неактивный</option>
                            </select>
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

const projects = ref({ data: [], current_page: 1, last_page: 1 });
const statistics = ref(null);
const loading = ref(false);
const checkingHealth = ref(false);
const showCreateModal = ref(false);
const editingProject = ref(null);

const filters = reactive({
    search: '',
    status: '',
});

const form = reactive({
    name: '',
    url: '',
    license_key: '',
    server_ip: '',
    server_user: '',
    ssh_key: '',
    contact_name: '',
    contact_email: '',
    notes: '',
    status: 'active',
});

const loadStatistics = async () => {
    try {
        const response = await api.get('/projects/statistics');
        statistics.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки статистики:', error);
    }
};

const loadProjects = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: 20,
            ...filters,
        };
        const response = await api.get('/projects', { params });
        projects.value = response.data;
    } catch (error) {
        console.error('Ошибка загрузки проектов:', error);
        alert('Ошибка загрузки проектов');
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    loadProjects(page);
};

const checkProjectHealth = async (project) => {
    checkingHealth.value = true;
    try {
        await api.post(`/projects/${project.id}/check-health`);
        await loadProjects();
        await loadStatistics();
        alert('Проверка здоровья выполнена');
    } catch (error) {
        console.error('Ошибка проверки:', error);
        alert('Ошибка проверки здоровья');
    } finally {
        checkingHealth.value = false;
    }
};

const checkAllHealth = async () => {
    checkingHealth.value = true;
    try {
        await api.post('/projects/check-all-health');
        await loadProjects();
        await loadStatistics();
        alert('Проверка всех проектов выполнена');
    } catch (error) {
        console.error('Ошибка проверки:', error);
        alert('Ошибка проверки здоровья');
    } finally {
        checkingHealth.value = false;
    }
};

const saveProject = async () => {
    loading.value = true;
    try {
        if (editingProject.value) {
            await api.put(`/projects/${editingProject.value.id}`, form);
        } else {
            await api.post('/projects', form);
        }
        closeModal();
        loadProjects();
        loadStatistics();
    } catch (error) {
        console.error('Ошибка сохранения:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения проекта');
    } finally {
        loading.value = false;
    }
};

const editProject = (project) => {
    editingProject.value = project;
    Object.assign(form, {
        name: project.name,
        url: project.url,
        license_key: project.license_key || '',
        server_ip: project.server_ip || '',
        server_user: project.server_user || '',
        ssh_key: project.ssh_key || '',
        contact_name: project.contact_name || '',
        contact_email: project.contact_email || '',
        notes: project.notes || '',
        status: project.status,
    });
};

const deleteProject = async (project) => {
    if (!confirm(`Вы уверены, что хотите удалить проект "${project.name}"?`)) {
        return;
    }
    
    try {
        await api.delete(`/projects/${project.id}`);
        loadProjects();
        loadStatistics();
    } catch (error) {
        console.error('Ошибка:', error);
        alert(error.response?.data?.message || 'Ошибка удаления проекта');
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    editingProject.value = null;
    Object.assign(form, {
        name: '',
        url: '',
        license_key: '',
        server_ip: '',
        server_user: '',
        ssh_key: '',
        contact_name: '',
        contact_email: '',
        notes: '',
        status: 'active',
    });
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU');
};

const getStatusText = (status) => {
    const statuses = {
        'active': 'Активный',
        'error': 'Ошибка',
        'maintenance': 'Обслуживание',
        'inactive': 'Неактивный',
    };
    return statuses[status] || status;
};

onMounted(() => {
    loadProjects();
    loadStatistics();
});
</script>

<style scoped>
.projects-management {
    padding: 24px 32px;
    max-width: 1200px;
    margin: 0 auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.header-actions {
    display: flex;
    gap: 12px;
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
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
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

.badge-success { background: #4CAF50; color: white; }
.badge-danger { background: #f44336; color: white; }
.badge-warning { background: #FF9800; color: white; }
.badge-secondary { background: #9e9e9e; color: white; }
.badge-trial { background: #e0e0e0; color: #333; }
.badge-basic { background: #2196F3; color: white; }
.badge-premium { background: #FF9800; color: white; }
.badge-enterprise { background: #4CAF50; color: white; }

.health-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
    font-size: 12px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.dot-green { background: #4CAF50; }
.dot-yellow { background: #FF9800; }
.dot-red { background: #f44336; }

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
    max-width: 700px;
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

.link {
    color: var(--color-text);
    text-decoration: none;
}

.link:hover {
    text-decoration: underline;
}

.text-muted {
    color: var(--color-text-muted);
}

.small {
    font-size: 12px;
}
</style>
