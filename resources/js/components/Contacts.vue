<template>
    <div class="contacts-page">
        <div class="container">
            <h1 class="page-title">Контакты</h1>
            <div class="contacts-content">
                <div class="contact-info">
                    <h2>Свяжитесь с нами</h2>
                    <p>Мы всегда рады ответить на ваши вопросы и предложения.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <strong>Email:</strong>
                            <a href="mailto:info@example.com">info@example.com</a>
                        </div>
                        <div class="contact-item">
                            <strong>Телефон:</strong>
                            <a href="tel:+77001234567">+7 (700) 123-45-67</a>
                        </div>
                        <div class="contact-item">
                            <strong>Адрес:</strong>
                            <span>г. Алматы, ул. Примерная, д. 1</span>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form-wrapper">
                    <h2>Отправить сообщение</h2>
                    <form @submit.prevent="handleSubmit" class="contact-form">
                        <div class="form-group">
                            <label for="name">Ваше имя</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="form-control"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="form-control"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="message">Сообщение</label>
                            <textarea
                                id="message"
                                v-model="form.message"
                                class="form-control"
                                rows="5"
                                required
                            ></textarea>
                        </div>
                        <button type="submit" class="btn" :disabled="loading">
                            {{ loading ? 'Отправка...' : 'Отправить' }}
                        </button>
                        <div v-if="success" class="alert alert-success mt-3">
                            Сообщение успешно отправлено!
                        </div>
                        <div v-if="error" class="alert alert-danger mt-3">
                            {{ error }}
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const form = ref({
    name: '',
    email: '',
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
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        success.value = true;
        form.value = { name: '', email: '', message: '' };
    } catch (e) {
        error.value = 'Ошибка при отправке сообщения. Попробуйте позже.';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.contacts-page {
    min-height: 60vh;
    padding: 48px 0;
}

.contacts-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    max-width: 900px;
    margin: 0 auto;
}

.contact-info h2,
.contact-form-wrapper h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 16px;
}

.contact-info p {
    color: var(--color-text-muted);
    margin-bottom: 24px;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.contact-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.contact-item strong {
    font-size: 14px;
    color: var(--color-text);
}

.contact-item a,
.contact-item span {
    font-size: 14px;
    color: var(--color-text-muted);
}

.contact-item a:hover {
    color: var(--color-text);
}

.contact-form-wrapper {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: 24px;
}
</style>
