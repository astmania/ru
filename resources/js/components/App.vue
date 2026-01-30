<template>
    <div id="app">
        <Navbar />
        <main class="main">
            <router-view />
        </main>
        <Footer />
    </div>
</template>

<script setup>
import Navbar from './Navbar.vue';
import Footer from './Footer.vue';
</script>

<style scoped>
/* App использует глобальные стили из app.css */
</style>
