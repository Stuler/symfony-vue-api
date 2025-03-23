<template>
  <transition name="fade">
    <div v-if="message" :class="['alert', alertType]" class="flash-message">
      {{ message }}
    </div>
  </transition>
</template>

<script>
export default {
  props: ["message", "type"],
  computed: {
    alertType() {
      return this.type === "success" ? "alert-success" : "alert-danger";
    },
  },
};
</script>

<style>
.flash-message {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
  padding: 20px 30px;
  font-size: 18px;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  max-width: 400px;
  text-align: center;
  animation: fadeIn 0.5s ease-in-out;
}

/* Success & Error Colors */
.alert-success {
  background-color: #28a745;
  color: white;
}

.alert-danger {
  background-color: #dc3545;
  color: white;
}

/* Fade-in and fade-out transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translate(-50%, -60%);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%);
  }
}
</style>
