import { getPinjaman } from "../api.js";
import { renderPinjaman } from "../ui/pinjaman.js";

export async function pinjamanPage(app){

  app.innerHTML = `
    <div class="space-y-8 animate-in fade-in duration-500">

  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="flex items-center gap-4">
      <div class="p-3 bg-amber-500 rounded-2xl text-white shadow-lg shadow-amber-200">
        <i data-lucide="hand-coins" class="w-6 h-6"></i>
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Manajemen Pinjaman</h2>
        <p class="text-sm text-slate-500 font-medium">Pantau dan kelola kredit anggota koperasi</p>
      </div>
    </div>
    
    <div class="flex gap-2">
       <button class="p-2 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition-colors shadow-sm">
         <i data-lucide="filter" class="w-5 h-5"></i>
       </button>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-1 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-all group">
      <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
        <i data-lucide="trending-up" class="w-7 h-7"></i>
      </div>
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Pinjaman Aktif</p>
        <h3 id="totalPinjaman" class="text-2xl font-bold text-slate-800 font-mono-numbers tracking-tight">
          Rp 0
        </h3>
      </div>
    </div>
    
    <div class="hidden md:block md:col-span-2"></div>
  </div>

  <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/30 flex justify-between items-center">
      <h3 class="text-xs font-bold text-slate-500 uppercase tracking-[0.2em]">Daftar Pengajuan & Kredit</h3>
      <span class="px-3 py-1 bg-white border border-slate-200 rounded-full text-[10px] font-bold text-slate-400 shadow-sm">
        AUTO-REFRESH
      </span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="text-slate-400 text-[10px] uppercase tracking-widest font-extrabold border-b border-slate-50">
            <th class="px-8 py-5 text-left font-bold">Anggota</th>
            <th class="px-6 py-5 text-right font-bold">Jumlah Pinjaman</th>
            <th class="px-6 py-5 text-center font-bold">Tenor</th>
            <th class="px-6 py-5 text-left font-bold">Tanggal Input</th>
            <th class="px-6 py-5 text-center font-bold">Status</th>
            <th class="px-8 py-5 text-center font-bold">Aksi</th>
          </tr>
        </thead>

        <tbody id="pinjamanTable" class="divide-y divide-slate-50 text-slate-600 font-medium">
          </tbody>
      </table>
    </div>
    
    <div id="emptyHint" class="hidden p-10 text-center">
      <i data-lucide="clipboard-list" class="w-12 h-12 text-slate-200 mx-auto mb-3"></i>
      <p class="text-slate-400 text-sm">Belum ada data pinjaman yang tercatat</p>
    </div>
  </div>

</div>

  `;
 if (window.lucide) {
    window.lucide.createIcons();
  }

  const res = await getPinjaman();

  renderPinjaman(res);
}