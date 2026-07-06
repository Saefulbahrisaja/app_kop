import { getDashboardBendahara } from "../api.js";
import { renderBendahara } from "../ui/bendahara.js";

export async function dashboardPage(app){

  app.innerHTML = `
    <div id="dashboardContent" class="space-y-6"></div>
  `;

  // inject UI kamu (FULL)
  document.getElementById("dashboardContent").innerHTML = `
    ${getDashboardTemplate()}
  `;

  const data = await getDashboardBendahara();
  renderBendahara(data);
}

function getDashboardTemplate(){
  return `
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
  <div>
    <h2 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
      Dashboard Utama
      <span class="relative flex h-3 w-3">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
      </span>
    </h2>
    <p class="text-slate-500 text-sm mt-1 font-medium">Pantau kondisi kesehatan keuangan koperasi secara real-time.</p>
  </div>

  <div class="flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/60 w-fit">
    <button class="px-5 py-2 text-xs font-bold text-indigo-600 bg-white rounded-xl shadow-sm border border-slate-200/50 transition-all">
      Hari Ini
    </button>
    <button class="px-5 py-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition-all">
      Minggu Ini
    </button>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
  
  <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden group hover:shadow-xl hover:shadow-indigo-500/5 hover:-translate-y-1 transition-all duration-300">
    <div class="flex justify-between items-center mb-6">
      <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300">
        <i data-lucide="piggy-bank" class="w-5 h-5"></i>
      </div>
      <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Simpanan</span>
    </div>
    <div class="space-y-3.5">
      <div class="flex justify-between items-center group/item">
        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Pokok</span>
        <span id="simpananPokok" class="font-mono-numbers font-bold text-slate-800 group-hover/item:text-indigo-600 transition-colors">-</span>
      </div>
      <div class="flex justify-between items-center group/item">
        <span class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Wajib</span>
        <span id="simpananWajib" class="font-mono-numbers font-bold text-slate-800 group-hover/item:text-indigo-600 transition-colors">-</span>
      </div>
      <div class="pt-3 border-t border-slate-50 flex justify-between items-center">
        <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Manasuka</span>
        <span id="simpananManasuka" class="font-mono-numbers font-black text-indigo-600">-</span>
      </div>
    </div>
  </div>

  <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-emerald-500/5 hover:-translate-y-1 transition-all duration-300">
    <div class="flex justify-between items-center mb-6">
      <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
        <i data-lucide="arrow-up-right" class="w-5 h-5"></i>
      </div>
      <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Arus Kas</span>
    </div>
    <div class="mb-4">
      <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1.5">Total Saldo Kas</p>
      <span id="kasSaldo" class="text-2xl font-black text-emerald-600 font-mono-numbers tracking-tighter leading-none">-</span>
    </div>
    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-50">
      <div class="flex flex-col">
        <span class="text-[9px] text-slate-400 uppercase font-black mb-1">Inflow</span>
        <span id="kasInflow" class="text-xs font-bold text-slate-700 font-mono-numbers">-</span>
      </div>
      <div class="flex flex-col text-right">
        <span class="text-[9px] text-slate-400 uppercase font-black mb-1">Outflow</span>
        <span id="kasOutflow" class="text-xs font-bold text-rose-500 font-mono-numbers">-</span>
      </div>
    </div>
  </div>

  <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 group hover:shadow-xl hover:shadow-amber-500/5 hover:-translate-y-1 transition-all duration-300">
    <div class="flex justify-between items-center mb-6">
      <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl group-hover:bg-amber-500 group-hover:text-white transition-colors">
        <i data-lucide="receipt" class="w-5 h-5"></i>
      </div>
      <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Piutang</span>
    </div>
    <div class="space-y-4">
      <div class="flex justify-between items-end">
        <div>
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Sisa Piutang</p>
          <span id="piutangSisa" class="text-xl font-black text-rose-600 font-mono-numbers leading-none">-</span>
        </div>
      </div>
      <div class="relative w-full bg-slate-100 h-2 rounded-full overflow-hidden">
        <div class="absolute top-0 left-0 bg-amber-400 h-full w-2/3 rounded-full"></div>
      </div>
      <div class="flex justify-between text-[10px] font-bold uppercase tracking-tighter">
        <span id="piutangTotal" class="text-slate-400">T: -</span>
        <span id="piutangTerbayar" class="text-emerald-500 font-black">B: -</span>
      </div>
    </div>
  </div>

  <div class="bg-indigo-600 p-6 rounded-[2.5rem] shadow-xl shadow-indigo-200 text-white relative overflow-hidden group hover:scale-[1.03] transition-all duration-300">
    <div class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-500"></div>
    <div class="relative z-10 flex flex-col h-full justify-between">
      <div>
        <div class="flex items-center gap-2 mb-3">
          <i data-lucide="sparkles" class="w-4 h-4 text-indigo-200"></i>
          <span class="text-[10px] font-black text-indigo-100 uppercase tracking-[0.2em]">Saldo Bersih</span>
        </div>
        <h2 id="akSaldoBersih" class="text-2xl font-black font-mono-numbers tracking-tight mb-4">0</h2>
      </div>
      <div class="space-y-2 pt-4 border-t border-indigo-400/40 text-[10px]">
        <div class="flex justify-between font-bold">
          <span class="opacity-80">KAS</span> 
          <span id="akSaldoKas" class="font-mono-numbers">-</span>
        </div>
        <div class="flex justify-between font-bold">
          <span class="opacity-80 uppercase">Beban</span> 
          <span id="akPengeluaran" class="text-rose-300 font-mono-numbers">-</span>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col justify-between hover:border-indigo-200 transition-colors group">
    <div class="flex justify-between items-center mb-4">
      <div class="p-3 bg-slate-50 text-slate-500 rounded-2xl group-hover:bg-slate-200 transition-colors">
        <i data-lucide="shield-check" class="w-5 h-5"></i>
      </div>
      <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.15em]">Sistem</span>
    </div>
    <div class="text-center py-2">
      <p id="statusSaldo" class="text-sm font-black text-slate-700 mb-2 font-mono-numbers">-</p>
      <span id="statusText" class="inline-block px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] bg-slate-50 text-slate-400 border border-slate-100 group-hover:bg-emerald-50 group-hover:text-emerald-600 group-hover:border-emerald-100 transition-all">
        Sync...
      </span>
    </div>
    <p id="statusWarning" class="text-[10px] text-amber-600 font-bold text-center mt-2 italic opacity-0 group-hover:opacity-100 transition-opacity"></p>
  </div>
</div>
    `
    ;

    
}