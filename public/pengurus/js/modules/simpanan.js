import { getSimpanan } from "../api.js";
import { initSimpananFeature } from "../ui/simpanan.js";

export async function simpananPage(app){

  app.innerHTML = `
    <div class="space-y-8 animate-in fade-in duration-500">

  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
    <div class="flex items-center gap-4">
      <div class="p-3 bg-indigo-600 rounded-2xl text-white shadow-lg shadow-indigo-200">
        <i data-lucide="wallet-2" class="w-6 h-6"></i>
      </div>
      <div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Simpanan Anggota</h2>
        <p class="text-sm text-slate-500 font-medium">Manajemen dan monitoring saldo simpanan real-time</p>
      </div>
    </div>

    <div class="relative w-full md:w-80 group">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
        <i data-lucide="search" class="w-4 h-4"></i>
      </div>
      <input id="searchInput" type="text"
        placeholder="Cari nama anggota..."
        class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-100 flex items-center gap-5 hover:shadow-md transition-shadow">
      <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
        <i data-lucide="users" class="w-7 h-7"></i>
      </div>
      <div>
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Anggota</p>
        <h3 id="totalAnggota" class="text-2xl font-bold text-slate-800 font-mono-numbers">-</h3>
      </div>
    </div>

    <div class="bg-indigo-900 p-6 rounded-[2rem] shadow-xl shadow-indigo-100 flex items-center gap-5 relative overflow-hidden group">
      <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/5 rounded-full blur-2xl group-hover:scale-125 transition-transform"></div>
      
      <div class="w-14 h-14 bg-indigo-500 rounded-2xl flex items-center justify-center text-white relative z-10">
        <i data-lucide="banknote" class="w-7 h-7"></i>
      </div>
      <div class="relative z-10">
        <p class="text-xs font-bold text-indigo-300 uppercase tracking-widest mb-1">Akumulasi Simpanan</p>
        <h3 id="totalSimpanan" class="text-2xl font-bold text-white font-mono-numbers">-</h3>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
      <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Rincian Saldo Per Anggota</h3>
      <div class="flex gap-2">
        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="text-[10px] font-bold text-slate-400">UPDATE OTOMATIS</span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="text-slate-400 text-[11px] uppercase tracking-[0.15em] font-bold border-b border-slate-50">
            <th class="px-8 py-5">Nama Anggota</th>
            <th class="px-6 py-5 text-right">Pokok</th>
            <th class="px-6 py-5 text-right">Wajib</th>
            <th class="px-6 py-5 text-right">Manasuka</th>
            <th class="px-8 py-5 text-right bg-slate-50/50 text-indigo-600">Total Saldo</th>
          </tr>
        </thead>

        <tbody id="simpananTable" class="divide-y divide-slate-50 text-sm text-slate-600 font-medium">
          </tbody>
      </table>
    </div>
  </div>

  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 py-2">
    <p class="text-xs text-slate-400 font-medium italic">Menampilkan data berdasarkan filter terbaru</p>
    <div id="pagination" class="flex items-center gap-1.5 p-1.5 bg-white border border-slate-100 rounded-2xl shadow-sm">
      </div>
  </div>

</div>


  `;
 if (window.lucide) {
    window.lucide.createIcons();
  }

  const res = await getSimpanan();

  initSimpananFeature(res);
}