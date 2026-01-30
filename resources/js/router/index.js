import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import Login from '../components/Login.vue';
import Register from '../components/Register.vue';
import Contacts from '../components/Contacts.vue';
import LicenseManagement from '../components/LicenseManagement.vue';
import ProjectsManagement from '../components/ProjectsManagement.vue';
import ArticlesManagement from '../components/ArticlesManagement.vue';
import ArticleForm from '../components/ArticleForm.vue';
import ArticleView from '../components/ArticleView.vue';
import CategoryPage from '../components/CategoryPage.vue';
import ShejirePage from '../components/ShejirePage.vue';
import ShejireTreeForm from '../components/ShejireTreeForm.vue';
import ShejireTreeView from '../components/ShejireTreeView.vue';
import ShejireModeration from '../components/ShejireModeration.vue';
import ProfileEdit from '../components/ProfileEdit.vue';
import UsersManagement from '../components/UsersManagement.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
    },
    {
        path: '/contacts',
        name: 'contacts',
        component: Contacts,
    },
    {
        path: '/article/:idOrSlug',
        name: 'article',
        component: ArticleView,
    },
    {
        path: '/category/:slug',
        name: 'category',
        component: CategoryPage,
    },
    {
        path: '/shejire',
        name: 'shejire',
        component: ShejirePage,
    },
    {
        path: '/shejire/new',
        name: 'shejire-new',
        component: ShejireTreeForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/shejire/:id',
        name: 'shejire-tree',
        component: ShejireTreeView,
    },
    {
        path: '/shejire/:id/edit',
        name: 'shejire-edit',
        component: ShejireTreeForm,
        meta: { requiresAuth: true },
    },
    {
        path: '/shejire-moderation',
        name: 'shejire-moderation',
        component: ShejireModeration,
        meta: { requiresAuth: true, requiresModerator: true },
    },
    {
        path: '/articles',
        name: 'articles',
        component: ArticlesManagement,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/articles/new',
        name: 'articles-new',
        component: ArticleForm,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/articles/:id/edit',
        name: 'articles-edit',
        component: ArticleForm,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfileEdit,
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { requiresGuest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { requiresGuest: true },
    },
    {
        path: '/licenses',
        name: 'licenses',
        component: LicenseManagement,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/projects',
        name: 'projects',
        component: ProjectsManagement,
        meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
    {
        path: '/users',
        name: 'users',
        component: UsersManagement,
        meta: { requiresAuth: true, requiresSuperAdmin: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard для защиты маршрутов
router.beforeEach((to, from, next) => {
    // Проверяем токен авторизации из localStorage
    const token = localStorage.getItem('access_token');
    const user = localStorage.getItem('user');
    const isAuthenticated = !!(token && user);
    
    // Парсим пользователя для проверки прав
    let userData = null;
    if (user) {
        try {
            userData = JSON.parse(user);
        } catch (e) {
            console.error('Error parsing user data:', e);
        }
    }
    const isAdmin = userData?.is_admin === true;
    const isSuperAdmin = userData?.is_super_admin === true;
    const isModerator = userData?.is_moderator === true;

    // Главная страница доступна всем
    if (to.name === 'home') {
        next();
        return;
    }

    // Проверка для защищенных маршрутов
    if (to.meta.requiresAuth && !isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next({ name: 'home' });
    } else if (to.meta.requiresAdmin && (!isAuthenticated || !isAdmin)) {
        next({ name: 'home' }); // Редирект на главную, если нет прав админа
    } else if (to.meta.requiresSuperAdmin && (!isAuthenticated || !isSuperAdmin)) {
        next({ name: 'home' }); // Редирект на главную, если нет прав супер-админа
    } else if (to.meta.requiresModerator && (!isAuthenticated || !isModerator)) {
        next({ name: 'home' }); // Редирект на главную, если нет прав модератора
    } else {
        next();
    }
});

export default router;
