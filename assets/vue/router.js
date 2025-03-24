import { createRouter, createWebHistory } from "vue-router";
import JobList from "./components/JobList.vue";
import JobDetail from "./components/JobDetail.vue";
import JobAnswer from "./components/JobAnswer.vue";

const routes = [
    { path: "/", name: "jobList", component: JobList },
    { path: "/job/:jobId", name: "jobDetail", component: JobDetail },
    { path: "/job/:jobId/apply", name: "jobAnswer", component: JobAnswer }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
