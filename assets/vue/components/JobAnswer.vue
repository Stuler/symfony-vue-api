<template>
  <div class="container mt-4">
    <h1>Odpovědět na nabídku #{{ jobId }}</h1>

    <p v-if="jobId" class="text-success">Vue Router is working! jobId = {{ jobId }}</p>
    <p v-else class="text-danger">Vue Router is NOT passing jobId!</p>

    <form @submit.prevent="submitResponse">
      <div class="mb-3">
        <label class="form-label">Vaše jméno</label>
        <input type="text" class="form-control" v-model="response.name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Váš email</label>
        <input type="email" class="form-control" v-model="response.email" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Vaše zpráva</label>
        <textarea class="form-control" v-model="response.message" required></textarea>
      </div>
      <button type="submit" class="btn btn-success">Odeslat odpověď</button>
      <router-link to="/" class="btn btn-secondary">Zpět</router-link>
    </form>
  </div>
</template>

<script>
export default {
  props: ["jobId"], // Ensure jobId is passed from Vue Router
  data() {
    return {
      response: {
        name: "",
        email: "",
        message: ""
      },
      error: null
    };
  },
  mounted() {
    console.log("JobAnswer.vue Loaded with jobId:", this.jobId);
  }
};
</script>
