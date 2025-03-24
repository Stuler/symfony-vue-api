<template>
  <div class="container mt-4">
    <h1 class="text-center">{{ job?.title || "Název nenalezen" }}</h1>

    <div v-if="loading" class="alert alert-info text-center">Načítání detailu nabídky...</div>
    <div v-else-if="error" class="alert alert-danger text-center">{{ error }}</div>
    <div v-else-if="job" class="job-details">
      <p><strong>ID pozice:</strong> {{ job.job_id }}</p>
      <p><strong>Popis:</strong></p>
      <div v-html="sanitizeHTML(job.description)"></div>

      <p v-if="job.salary" class="salary">
        <strong>Plat:</strong>
        <span v-if="job.salary.is_range">
          {{ job.salary.is_min_visible ? job.salary.min + " " : "" }}
          -
          {{ job.salary.is_max_visible ? job.salary.max + " " : "" }}
          {{ job.salary.currency }} / {{ job.salary.unit }}
        </span>
        <span v-else>
          {{ job.salary.min }} {{ job.salary.currency }} / {{ job.salary.unit }}
        </span>
        <span v-if="job.salary.note">({{ job.salary.note }})</span>
      </p>

      <router-link :to="{ name: 'jobAnswer', params: { jobId: job.job_id } }"
                   class="btn btn-success mt-3">
        Odpovědět na nabídku
      </router-link>

      <button class="btn btn-secondary mt-3" @click="$router.go(-1)">Zpět</button>
    </div>
  </div>
</template>

<script>
import DOMPurify from "dompurify";

export default {
  data() {
    return {
      job: null,
      loading: false,
      error: null
    };
  },
  async created() {
    await this.fetchJobDetails();
  },
  methods: {
    async fetchJobDetails() {
      this.loading = true;
      const jobId = this.$route.params.jobId;
      try {
        const response = await fetch(`/api/jobs/${jobId}`);
        if (!response.ok) throw new Error("Job not found");

        const data = await response.json();
        this.job = data;
      } catch (error) {
        console.error("Error fetching job details:", error);
        this.error = "Job not found";
      } finally {
        this.loading = false;
      }
    },
    sanitizeHTML(html) {
      return DOMPurify.sanitize(html);
    }
  }
};
</script>

<style>
.container {
  max-width: 800px;
}
.salary {
  font-size: 1.2em;
  font-weight: bold;
  color: #007bff;
}
</style>
