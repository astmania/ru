<template>
    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="footer-title">О нас</h3>
                    <p class="footer-text">Информационный портал о культуре, истории и традициях казахского народа.</p>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Разделы</h3>
                    <ul class="footer-links">
                        <li><router-link to="/#tarih">Тарих</router-link></li>
                        <li><router-link to="/#madeniet">Мәдениет</router-link></li>
                        <li><router-link to="/#saiasat">Саясат</router-link></li>
                        <li><router-link to="/#batyrlar">Батырлар</router-link></li>
                        <li><router-link to="/#sport">Спорт</router-link></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="footer-title">Контакты</h3>
                    <ul class="footer-contact">
                        <li><router-link to="/contacts">Связаться с нами</router-link></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ currentYear }} Все права защищены</p>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';

const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
/* Footer использует глобальные стили из app.css */
</style>
