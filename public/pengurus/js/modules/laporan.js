import { getTunggakan, getLpj } from "../api.js";
import { renderTunggakan } from "../ui/tunggakan.js";
import { renderPendapatan } from "../ui/shu.js"

function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}

function renderShu(data){
  document.getElementById("shuPendapatan").innerText = rupiah(data.total_pendapatan);
  document.getElementById("shuBiaya").innerText = rupiah(data.biaya_detail.total);
  document.getElementById("shuTotal").innerText = rupiah(data.shu);

  const box = document.getElementById("shuPembagian");
  box.innerHTML = "";

  Object.entries(data.pembagian).forEach(([k,v])=>{
    box.innerHTML += `
      <div class="flex justify-between items-center p-4 border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors">
        <span class="capitalize text-slate-600 font-medium">${k.replace(/_/g,' ')}</span>
        <span class="font-bold text-slate-800 font-mono-numbers">${rupiah(v)}</span>
      </div>
    `;
  });
}

export async function laporanPage(app) {
  app.innerHTML = `
    <div class="space-y-10 animate-in fade-in slide-in-from-bottom-4 duration-700">

      <section class="space-y-6">
        <div class="flex items-center gap-4">
          <div class="p-3.5 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-200">
            <i data-lucide="bar-chart-3" class="w-6 h-6"></i>
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Laporan SHU</h2>
            <p class="text-sm text-slate-500 font-medium">Ringkasan alokasi pendapatan dan pembagian sisa hasil</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 group hover:shadow-md transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total Pendapatan</p>
            <h3 id="shuPendapatan" class="text-2xl font-black text-indigo-600 font-mono-numbers leading-none">Rp 0</h3>
          </div>

          <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 group hover:shadow-md transition-all">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total Biaya</p>
            <h3 id="shuBiaya" class="text-2xl font-black text-rose-500 font-mono-numbers leading-none">Rp 0</h3>
          </div>

          <div class="bg-slate-900 p-6 rounded-[2.5rem] shadow-xl shadow-slate-200 text-white relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/20 rounded-full blur-2xl transition-transform group-hover:scale-150"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 relative z-10">SHU Bersih</p>
            <h3 id="shuTotal" class="text-2xl font-black text-white font-mono-numbers leading-none relative z-10">Rp 0</h3>
          </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
          <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
               <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Alokasi Pembagian SHU</span>
               <i data-lucide="pie-chart" class="w-4 h-4 text-slate-300"></i>
            </div>
            <div id="shuPembagian" class="min-h-[100px]"></div>
          </div>

          <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50 bg-slate-50/50 flex items-center justify-between">
               <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Rincian Sumber Pendapatan</span>
               <i data-lucide="list" class="w-4 h-4 text-slate-300"></i>
            </div>
            <div id="listPendapatan" class="min-h-[100px]"></div>
          </div>
        </div>
      </section>

      <section class="pt-6 border-t border-slate-100">
        <div class="flex items-center gap-4 mb-6">
          <div class="p-3.5 bg-rose-500 rounded-2xl text-white shadow-lg shadow-rose-200">
            <i data-lucide="alert-circle" class="w-6 h-6"></i>
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Monitoring Tunggakan</h2>
            <p class="text-sm text-slate-500 font-medium">Status keterlambatan kewajiban anggota</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-rose-600 p-6 rounded-[2.5rem] shadow-lg shadow-rose-100 text-white relative overflow-hidden group">
            <div class="absolute -right-2 -top-2 w-16 h-16 bg-white/10 rounded-full blur-xl"></div>
            <p class="text-[10px] font-black text-rose-100 uppercase tracking-[0.2em] mb-2">Total Tunggakan</p>
            <h3 id="sumTotal" class="text-2xl font-black font-mono-numbers">Rp 0</h3>
          </div>

          <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400">
               <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Simpanan</p>
              <h3 id="sumSimpanan" class="text-xl font-bold text-slate-800 font-mono-numbers">-</h3>
            </div>
          </div>

          <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400">
               <i data-lucide="hand-coins" class="w-6 h-6"></i>
            </div>
            <div>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cicilan</p>
              <h3 id="sumCicilan" class="text-xl font-bold text-slate-800 font-mono-numbers">-</h3>
            </div>
          </div>
        </div>
      </section>
    </div>
  `;

  // Initialize icons
  if (window.lucide) window.lucide.createIcons();

  /* ================= LOAD DATA ================= */
  const [lpj, tunggakan] = await Promise.all([
    getLpj(),
    getTunggakan()
  ]);

  renderShu(lpj);
  renderPendapatan(lpj.pendapatan_list);

  // Periksa keberadaan kontainer target sebelum render
  if(document.getElementById("sumTotal")){
    renderTunggakan(tunggakan);
  }
}