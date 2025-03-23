import './styles/app.css';
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap";

import { createApp } from 'vue';
import App from './vue/App.vue';
import router from './vue/router';

// Debug: Log when Vue app starts
console.log("Mounting Vue app...");

const app = createApp(App);
app.use(router);
app.mount('#app');

// Debug: Log when Vue Router starts
router.beforeEach((to, from, next) => {
    console.log(`Navigating from ${from.fullPath} to ${to.fullPath}`);
    next();
});
