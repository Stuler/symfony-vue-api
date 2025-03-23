<template>
  <div class="container mt-4">
    <h1>Odpovědět na nabídku: {{ jobTitle }}</h1>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <form @submit.prevent="validateAndSubmit">
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
        <label class="form-label">Jednotka</label>
        <select class="form-control" v-model="response.salary.unit">
          <option value="month">Měsíc</option>
          <option value="hour">Hodina</option>
        </select>
      </div>

      <h4>CV</h4>
      <div class="mb-3">
        <label class="form-label">CV (Pouze .pdf, .docx, .txt)</label>
        <input type="file" class="form-control" @change="handleFileUpload">
      </div>

      <div class="form-check">
        <input class="form-check-input" type="checkbox" v-model="response.gdpr_34" id="gdpr">
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
export default {
  props: ["jobId", "jobTitle"],
  data() {
    return {
      response: {
        job_id: this.jobId,
        name: "",
        email: "",
        phone: "",
        linkedin: "",
        salary: {
          amount: null,
          currency: "CZK",
          unit: "month"
        },
        gdpr_34: true,
        cv: [],
        skip_validation: false, // by default, backend should validate if needed
      },
      error: null
    };
  },
  methods: {
    async validateAndSubmit() {
      try {
        // Call the validation endpoint
        const validationResponse = await fetch(`/api/validate/${this.jobId}`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(this.response),
        });

        // Ensure we properly parse the response
        const validationData = await validationResponse.json();
        console.log("Validation response:", validationData);

        // Check if validation failed
        if (!validationResponse.ok || !validationData.meta || validationData.meta.code !== "api.ok") {
          throw new Error(validationData?.meta?.message || "Validace selhala.");
        }

        // Proceed with submission (skip validation since it's already done)
        this.response.skip_validation = true;
        const response = await fetch("/api/respond", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(this.response),
        });

        if (!response.ok) throw new Error("Odpověď se nepodařilo odeslat.");
        alert("Odpověď byla úspěšně odeslána!");
        this.$router.push("/");
      } catch (error) {
        console.error("API Error:", error);
        this.error = error.message || "Chyba při odesílání odpovědi.";
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
