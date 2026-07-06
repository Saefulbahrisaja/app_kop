export function anggotaPage(app){
  app.innerHTML = `
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
      <div class="p-6 border-b border-slate-50 flex justify-between items-center">
        <h3 class="font-bold text-slate-800">Anggota Terbaru</h3>
        <button class="flex items-center gap-2 px-4 py-2 bg-slate-50 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-100 transition-colors">
          <i data-lucide="download" class="w-4 h-4"></i> Export
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 text-slate-400 text-[10px] uppercase tracking-widest">
            <tr>
              <th class="px-8 py-4 font-bold">Nama</th>
              <th class="px-8 py-4 font-bold">No. Anggota</th>
              <th class="px-8 py-4 font-bold text-center">Status</th>
            </tr>
          </thead>
          <tbody id="anggotaTable" class="divide-y divide-slate-50 text-sm text-slate-600 italic uppercase">
            </tbody>
        </table>
      </div>
    </div>
  `;
}