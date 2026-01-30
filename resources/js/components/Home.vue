<template>
    <div class="home">
        <!-- На главной всегда показываем главную страницу (и до, и после авторизации) -->
        <MainPage />
    </div>
</template>

<script setup>
import MainPage from './MainPage.vue';
</script>

<style scoped>
.home {
    min-height: 20vh;
}
</style>
