import { dashboardPage } from "./modules/dashboard.js";
import { anggotaPage } from "./modules/anggota.js";
import { simpananPage } from "./modules/simpanan.js";
import { pinjamanPage } from "./modules/pinjaman.js";
import { laporanPage } from "./modules/laporan.js";

const routes = {
  dashboard: dashboardPage,
  anggota: anggotaPage,
  simpanan: simpananPage,
  pinjaman: pinjamanPage,
  laporan: laporanPage
};

export function navigate(page){
  const app = document.getElementById("app");

  if(!routes[page]){
    app.innerHTML = "<h2>404 Page</h2>";
    return;
  }

  app.innerHTML = `<div class="text-center py-10">Loading...</div>`;

  // PENTING: pastikan dipanggil
  routes[page](app);
}