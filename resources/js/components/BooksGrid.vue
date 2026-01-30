<template>
    <div class="books-grid">
        <div class="books-container">
            <div
                v-for="book in books"
                :key="book.id"
                class="book-card"
            >
                <div class="book-image">
                    <img :src="book.image" :alt="book.title" />
                </div>
                <div class="book-info">
                    <h3 class="book-title">{{ book.title }}</h3>
                    <p class="book-author">{{ book.author }}</p>
                    <p class="book-description">{{ book.description }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const books = ref([
    {
        id: 1,
        title: 'Қазақ халқының тарихы',
        author: 'Автор 1',
        description: 'Қазақ халқының тарихы туралы кітап',
        image: '/images/book1.jpg'
    },
    {
        id: 2,
        title: 'Мәдениет және дәстүр',
        author: 'Автор 2',
        description: 'Қазақ мәдениеті мен дәстүрлері туралы',
        image: '/images/book2.jpg'
    },
    {
        id: 3,
        title: 'Батырлар',
        author: 'Автор 3',
        description: 'Қазақ батырлары туралы кітап',
        image: '/images/book3.jpg'
    },
    {
        id: 4,
        title: 'Шежіре',
        author: 'Автор 4',
        description: 'Қазақ шежіресі туралы',
        image: '/images/book4.jpg'
    },
    {
        id: 5,
        title: 'Саясат',
        author: 'Автор 5',
        description: 'Қазақстан саясаты туралы',
        image: '/images/book5.jpg'
    },
    {
        id: 6,
        title: 'Спорт',
        author: 'Автор 6',
        description: 'Қазақстан спортшылары туралы',
        image: '/images/book6.jpg'
    }
]);
</script>

<style scoped>
/* BooksGrid использует глобальные стили из app.css */
</style>
