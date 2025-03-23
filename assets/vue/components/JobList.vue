<template>
  <div class="container mt-4">
    <h1 class="text-center">Seznam nabídek</h1>

    <div v-if="loading" class="alert alert-info text-center">Nahrávam nabídky...</div>

    <div v-if="jobs.length" class="row">
      <div class="col-md-6" v-for="job in jobs" :key="job.job_id">
        <div class="card mb-4 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">{{ job.title }}</h5>
            <p class="text-muted"><strong>Created:</strong> {{ formatDate(job.date_created) }}</p>
            <p class="text-muted"><strong>Location:</strong> {{ getLocation(job.addresses) }}</p>

            <div v-html="sanitizeHTML(job.description)" class="job-description"></div>

            <router-link
                :to="{ name: 'jobAnswer', params: { jobId: job.job_id }, state: { jobTitle: job.title } }"
                class="btn btn-primary mt-3">
              Odpovědět na nabídku
            </router-link>


          </div>
        </div>
      </div>
    </div>

    <div v-else-if="!loading" class="alert alert-warning text-center">Žádná nabídka nebyla nalezena</div>

    <!-- Pagination -->
    <nav aria-label="Job pagination" class="mt-4">
      <ul class="pagination justify-content-center">
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
  </div>
</template>

<script>
import DOMPurify from "dompurify";

export default {
  data() {
    return {
      jobs: [],
      currentPage: 1,
      totalPages: 1,
      totalJobs: 0,
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
        this.totalJobs = data.pagination?.total_jobs || 0;

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
        this.scrollToTop();
      }
    },
    async nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        await this.fetchJobs();
        this.scrollToTop();
      }
    },
    scrollToTop() {
      window.scrollTo({ top: 0, behavior: "smooth" });
    },
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString();
    },
    getLocation(addresses) {
      if (!addresses || addresses.length === 0) return "Unknown";
      const primaryAddress = addresses.find(addr => addr.is_primary) || addresses[0];
      return `${primaryAddress.city}, ${primaryAddress.state}`;
    },
    sanitizeHTML(html) {
      let doc = new DOMParser().parseFromString(html, "text/html");
      let links = doc.querySelectorAll("a");

      links.forEach(link => {
        let href = link.getAttribute("href");
        if (href && !href.startsWith("http://") && !href.startsWith("https://")) {
          link.setAttribute("href", "https://" + href);
        }
      });

      return DOMPurify.sanitize(doc.body.innerHTML);
    }
  }
};
</script>

<style>
.job-list {
  max-width: 800px;
  margin: auto;
  padding: 20px;
}
.job-item {
  border-bottom: 1px solid #ddd;
  padding: 10px 0;
}
.pagination {
  margin-top: 20px;
}
button {
  margin: 5px;
  padding: 8px;
  cursor: pointer;
}
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Frame around images */
.job-description img {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 10px auto;
  border: 1px solid #0c0b0b;
  border-radius: 10px; /* Rounded corners */
  padding: 5px;
  background: #ffffff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>
