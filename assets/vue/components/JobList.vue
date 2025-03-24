<template>
  <div class="container mt-4">
    <h1 class="text-center">Seznam nabídek</h1>

    <div v-if="loading" class="alert alert-info text-center">Nahrávám nabídky...</div>

    <div v-if="jobs.length" class="list-group">
      <router-link
          v-for="job in jobs"
          :key="job.job_id"
          :to="{ name: 'jobDetail', params: { jobId: job.job_id } }"
          class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
      >
        <span>{{ job.title }}</span>
        <span class="badge bg-primary">
          {{ formatSalary(job.salary) }}
        </span>
      </router-link>
    </div>

    <div v-else-if="!loading" class="alert alert-warning text-center">
      Žádná nabídka nebyla nalezena
    </div>

    <!-- pagination + Jobs Per Page Controls -->
    <div class="d-flex justify-content-between align-items-center mt-4">
      <!-- Pagination Controls -->
      <nav aria-label="Job pagination">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="prevPage">Předchozí</button>
          </li>
          <li class="page-item disabled">
            <span class="page-link">Strana {{ currentPage }} z {{ totalPages }}</span>
          </li>
          <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
            <button class="page-link" @click="nextPage">Další</button>
          </li>
        </ul>
      </nav>

      <!-- dropdown for selecting jobs per page -->
      <div class="d-flex align-items-center">
        <label for="jobsPerPage" class="me-2">Počet nabídek:</label>
        <select id="jobsPerPage" class="form-select w-auto" v-model="limit" @change="changeLimit">
          <option v-for="option in [5, 10, 25, 50]" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      jobs: [],
      currentPage: 1,
      totalPages: 1,
      limit: 10,
      loading: false
    };
  },
  async created() {
    await this.fetchJobs();
  },
  methods: {
    async fetchJobs() {
      this.loading = true;
      try {
        const response = await fetch(`/api/jobs?page=${this.currentPage}&limit=${this.limit}`);
        if (!response.ok) throw new Error("Failed to fetch jobs");

        const data = await response.json();
        this.jobs = data.jobs || [];
        this.totalPages = data.pagination?.total_pages || 1;
      } catch (error) {
        console.error("Error fetching jobs:", error);
      } finally {
        this.loading = false;
      }
    },
    async prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        await this.fetchJobs();
      }
    },
    async nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        await this.fetchJobs();
      }
    },
    async changeLimit() {
      this.currentPage = 1;
      await this.fetchJobs();
    },
    formatSalary(salary) {
      if (!salary || !salary.visible) {
        return "Mzda neuvedena";
      }
      let min = salary.is_min_visible ? salary.min + " " : "";
      let max = salary.is_max_visible ? salary.max + " " : "";
      return `${min}${max}${salary.currency} / ${salary.unit}`;
    }
  }
};
</script>

<style>
.list-group-item {
  font-size: 1.1em;
}
.badge {
  font-size: 0.9em;
}
.pagination {
  margin-bottom: 0;
}
.form-select {
  min-width: 80px;
}
</style>
