import { createRouter, createWebHistory } from 'vue-router';
import JobList from './components/JobList.vue';
import JobAnswer from './components/JobAnswer.vue';

const routes = [
    { path: '/', component: JobList },
    {
        path: '/job-answer/:jobId',
        name: 'jobAnswer',
        component: JobAnswer,
        props: route => ({ jobId: route.params.jobId, jobTitle: history.state.jobTitle })
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
