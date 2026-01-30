<template>
    <div class="contact-form-section">
        <div class="contact-form-container">
            <h2 class="contact-form-title">Обратная связь</h2>
            <p class="contact-form-subtitle">Свяжитесь с нами, мы всегда рады вашим вопросам и предложениям</p>
            
            <form @submit.prevent="handleSubmit" class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact-name">Ваше имя</label>
                        <input
                            id="contact-name"
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            placeholder="Введите ваше имя"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email</label>
                        <input
                            id="contact-email"
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            placeholder="your@email.com"
                            required
                        />
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contact-subject">Тема</label>
                    <input
                        id="contact-subject"
                        v-model="form.subject"
                        type="text"
                        class="form-control"
                        placeholder="Тема сообщения"
                        required
                    />
                </div>
                
                <div class="form-group">
                    <label for="contact-message">Сообщение</label>
                    <textarea
                        id="contact-message"
                        v-model="form.message"
                        class="form-control"
                        rows="6"
                        placeholder="Введите ваше сообщение"
                        required
                    ></textarea>
                </div>
                
                <button type="submit" class="btn btn-submit" :disabled="loading">
                    {{ loading ? 'Отправка...' : 'Отправить сообщение' }}
                </button>
                
                <div v-if="success" class="alert alert-success mt-3">
                    Спасибо! Ваше сообщение успешно отправлено. Мы свяжемся с вами в ближайшее время.
                </div>
                
                <div v-if="error" class="alert alert-danger mt-3">
                    {{ error }}
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
    name: '',
    email: '',
    subject: '',
    message: ''
});

const loading = ref(false);
const success = ref(false);
const error = ref('');

const handleSubmit = async () => {
    loading.value = true;
    error.value = '';
    success.value = false;
    
    try {
        // Здесь будет API запрос
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        success.value = true;
        form.value = { name: '', email: '', subject: '', message: '' };
        
        // Скрыть сообщение об успехе через 5 секунд
        setTimeout(() => {
            success.value = false;
        }, 5000);
    } catch (e) {
        error.value = 'Ошибка при отправке сообщения. Пожалуйста, попробуйте позже.';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* ContactForm использует глобальные стили из app.css */
</style>
