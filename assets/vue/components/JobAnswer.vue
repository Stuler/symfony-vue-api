<template>
  <div class="container mt-4">
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
        <input type="number" class="form-control" v-model="response.salary.amount">
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

      <h4>Přílohy (Pouze CV a průvodní dopis)</h4>
      <div class="mb-3">
        <label class="form-label">CV (Pouze .pdf, .docx, .txt)</label>
        <input type="file" class="form-control" @change="handleFileUpload($event, 2)">
      </div>
      <div class="mb-3">
        <label class="form-label">Průvodní dopis (Pouze .pdf, .docx, .txt)</label>
        <input type="file" class="form-control" @change="handleFileUpload($event, 1)">
      </div>

      <button type="submit" class="btn btn-success">Odeslat odpověď</button>
      <router-link to="/" class="btn btn-secondary">Zpět</router-link>
    </form>
  </div>
</template>

<script>
export default {
  props: ["jobId", "jobTitle"], // jobTitle is now a prop, not from query
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
        attachments: []
      },
      error: null
    };
  },
  methods: {
    async submitResponse() {
      try {
        const response = await fetch("/api/respond", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(this.response),
        });

        if (!response.ok) throw new Error("Odpověď se nepodařilo odeslat.");

        alert("Odpověď byla úspěšně odeslána!");
        this.$router.push("/");
      } catch (error) {
        console.error(error);
        this.error = "Chyba při odesílání odpovědi.";
      }
    },
    handleFileUpload(event, fileType) {
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
        this.response.attachments.push({
          base64: e.target.result.split(",")[1],
          filename: file.name,
          type: fileType // 1 = Cover Letter, 2 = CV
        });
      };
      reader.readAsDataURL(file);
    }
  }
};
</script>
