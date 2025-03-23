import { createRouter, createWebHistory } from 'vue-router';
import JobList from './components/JobList.vue';
import JobAnswer from './components/JobAnswer.vue';

const routes = [
    { path: '/', component: JobList },
    { path: '/job-answer/:jobId', component: JobAnswer, props: true }
];

const router = createRouter({
    history: createWebHistory(), // Ensure clean URLs
    routes
});

export default router;
