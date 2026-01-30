<template>
    <header class="navbar">
        <div class="container navbar-inner">
            <router-link to="/" class="navbar-brand">Вход</router-link>
            <ul class="navbar-nav">
                <template v-if="isAuthenticated">
                    <li><router-link to="/" class="nav-link">Главная</router-link></li>
                    <li v-if="user?.is_admin">
                        <router-link to="/licenses" class="nav-link">Лицензии</router-link>
                    </li>
                    <li v-if="user?.is_admin">
                        <router-link to="/articles" class="nav-link">Статьи</router-link>
                    </li>
                    <li v-if="user?.is_super_admin">
                        <router-link to="/projects" class="nav-link">Проекты</router-link>
                    </li>
                    <li v-if="user?.is_super_admin">
                        <router-link to="/users" class="nav-link">Пользователи</router-link>
                    </li>
                    <li><router-link to="/contacts" class="nav-link">Контакты</router-link></li>
                    <li class="dropdown" :class="{ 'dropdown-open': isDropdownOpen }">
                        <button type="button" class="nav-link dropdown-toggle" @click="toggleDropdown"
                            @blur="handleDropdownBlur">
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                :alt="user?.name"
                                class="user-avatar"
                            />
                            <svg v-else class="user-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                            {{ formatUserName(user?.name) }}
                        </button>
                        <div class="dropdown-menu">
                            <div class="dropdown-item dropdown-item--info">
                                <div class="dropdown-item-name">{{ user?.name }}</div>
                                <div class="dropdown-item-email">{{ user?.email }}</div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <router-link to="/profile" class="dropdown-item dropdown-item--action" @click="isDropdownOpen = false">
                                Редактировать профиль
                            </router-link>
                            <button type="button" class="dropdown-item dropdown-item--action" @click="handleLogout">
                                Выйти
                            </button>
                        </div>
                    </li>
                </template>
                <template v-else>
                    <li><router-link to="/contacts" class="nav-link">Контакты</router-link></li>
                    <li><router-link to="/login" class="nav-link">Войти</router-link></li>
                    <li><router-link to="/register" class="nav-link">Регистрация</router-link></li>
                </template>
            </ul>
        </div>
        <!-- Второе меню (как было раньше на главной), но теперь в header -->
        <section ref="menuSectionRef" class="secondary-menu-section"
            :class="{ 'is-fixed': isMenuFixed }">
            <div class="container d-flex justify-content-between align-items-center">
                <router-link to="/shejire" class="nav-style">
                    <img src="/storage/img/tree-diagram-svgrepo-com.svg" alt="Шежіре" class="diagram-icon" />
                    Шежіре
                </router-link>
                <nav class="secondary-menu" aria-label="Разделы статей">
                    <router-link v-for="cat in categories" :key="cat.id" :to="`/category/${cat.slug}`"
                        class="menu-item">
                        {{ cat.name }}
                    </router-link>
                </nav>
            </div>
        </section>

        <!-- Placeholder чтобы контент не прыгал при фиксации -->
        <div v-if="isMenuFixed" class="menu-placeholder" :style="{ height: menuHeight + 'px' }">
        </div>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import api from '../services/api';

const router = useRouter();
const { user, isAuthenticated, logout } = useAuth();
const isDropdownOpen = ref(false);
const categories = ref([]);

// фиксированное меню разделов (как раньше на главной)
const menuSectionRef = ref(null);
const isMenuFixed = ref(false);
const menuHeight = ref(57);

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const handleDropdownBlur = (e) => {
    const el = e?.currentTarget;
    if (!el) return;
    setTimeout(() => {
        if (!el.contains(document.activeElement)) {
            isDropdownOpen.value = false;
        }
    }, 150);
};

const handleClickOutside = (event) => {
    const dropdown = event.target.closest('.dropdown');
    if (!dropdown) {
        isDropdownOpen.value = false;
    }
};

const handleScroll = () => {
    const scrollY = window.scrollY || window.pageYOffset;
    isMenuFixed.value = scrollY > 100;
};

const formatUserName = (fullName) => {
    if (!fullName) return '';

    const parts = fullName.trim().split(' ');
    if (parts.length < 2) return fullName;

    // Берем фамилию (первая часть)
    const lastName = parts[0];

    // Берем первые буквы имени и отчества
    const initials = parts.slice(1)
        .map(part => part.charAt(0).toUpperCase())
        .join('.');

    return `${lastName} ${initials}.`;
};

const handleLogout = async () => {
    isDropdownOpen.value = false;
    await logout();
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    loadCategories();
    window.addEventListener('scroll', handleScroll, { passive: true });
    if (menuSectionRef.value) {
        menuHeight.value = menuSectionRef.value.offsetHeight;
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', handleScroll);
});

async function loadCategories() {
    try {
        const { data } = await api.get('/categories');
        categories.value = Array.isArray(data) ? data : [];
    } catch (e) {
        categories.value = [];
    }
}
</script>

<style scoped>
/* Navbar использует глобальные стили из app.css */
</style>
