<template>
  <div class="container mt-4">
    <flash-message :message="flashMessage" :type="flashType" />

    <h1>Odpovědět na nabídku: {{ jobTitle }}</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <form @submit.prevent="submitResponse">
      <h4>Osobní údaje</h4>
      <div class="mb-3">
        <label class="form-label">Jméno</label>
        <input type="text" class="form-control" v-model="response.name" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" v-model="response.email" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Telefon</label>
        <input type="text" class="form-control" v-model="response.phone">
      </div>
      <div class="mb-3">
        <label class="form-label">LinkedIn Profil</label>
        <input type="url" class="form-control" v-model="response.linkedin">
      </div>

      <h4>Platové očekávání</h4>
      <div class="mb-3">
        <label class="form-label">Požadovaná mzda</label>
        <input type="number" class="form-control" v-model="response.salary.amount" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Měna</label>
        <select class="form-control" v-model="response.salary.currency">
          <option value="CZK">CZK</option>
          <option value="EUR">EUR</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">CV (Pouze .pdf, .docx, .txt)</label>
        <input type="file" class="form-control" @change="handleFileUpload($event, 2)">
      </div>
      <div class="mb-3">
        <label class="form-label">Průvodní dopis (Pouze .pdf, .docx, .txt)</label>
        <input type="file" class="form-control" @change="handleFileUpload($event, 1)">
      </div>

      <div class="form-check">
        <input class="form-check-input" type="checkbox" v-model="response.gdpr_agreement" id="gdpr">
        <label class="form-check-label" for="gdpr">
          Souhlasím se zpracováním osobních údajů (GDPR)
        </label>
      </div>

      <button type="submit" class="btn btn-success">Odeslat odpověď</button>
      <router-link to="/" class="btn btn-secondary">Zpět</router-link>
    </form>
  </div>
</template>

<script>
import FlashMessage from "../components/FlashMessage.vue";

export default {
  components: { FlashMessage },
  props: ["jobId", "jobTitle"],
  data() {
    return {
      response: {
        job_id: null,
        name: "",
        email: "",
        phone: "",
        linkedin: "",
        salary: { amount: null, currency: "CZK", unit: "month" },
        gdpr_agreement: true,
        attachments: []
      },
      flashMessage: null,
      flashType: "success",
      error: null
    };
  },
  mounted() {
    // ensure job_id is set properly
    this.response.job_id = this.jobId || this.$route.params.jobId || null;
    console.log("Job ID Set:", this.response.job_id);
  },
  methods: {
    async submitResponse() {
      try {
        const response = await fetch("/api/respond", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(this.response),
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.error || "Odpověď se nepodařilo odeslat.");

        this.flashMessage = "Odpověď byla úspěšně odeslána!";
        this.flashType = "success";

        // redirect After 3 Seconds
        setTimeout(() => {
          this.flashMessage = null;
          this.$router.push("/");
        }, 3000);
      } catch (error) {
        console.error(error);
        this.flashMessage = error.message || "Chyba při odesílání odpovědi.";
        this.flashType = "error";
      }
    },
    handleFileUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const allowedFormats = [
        "application/pdf",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
        "text/plain"
      ];

      if (!allowedFormats.includes(file.type)) {
        alert("Nepodporovaný formát souboru. Povolené jsou pouze PDF, DOCX, TXT.");
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        this.response.cv = [{
          filename: file.name,
          base64: e.target.result.split(",")[1]
        }];
      };
      reader.readAsDataURL(file);
    }
  }
};
</script>
