function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}
export function renderTunggakan(data){

  // SUMMARY
  document.getElementById("sumTotal").innerText =
    data.summary.total + " Kasus";

  document.getElementById("sumSimpanan").innerText =
    data.summary.simpanan_wajib + " Orang";

  document.getElementById("sumCicilan").innerText =
    data.summary.cicilan + " Orang";

  /* ================= SIMPANAN ================= */
  const simpananBox = document.getElementById("listSimpanan");
  simpananBox.innerHTML = "";

  data.data.simpanan_wajib.forEach(a=>{
    simpananBox.innerHTML += `
      <div class="flex justify-between items-center p-3 border-b">
        <span>${a.nama}</span>
        <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-600 rounded">
          Belum Bayar
        </span>
      </div>
    `;
  });

  /* ================= CICILAN ================= */
  const cicilanBox = document.getElementById("listCicilan");
  cicilanBox.innerHTML = "";

  data.data.cicilan.forEach(a=>{

    const telat = a.cicilan.hari_telat;

    cicilanBox.innerHTML += `
      <div class="p-3 border-b">

        <div class="flex justify-between">
          <span class="font-medium">${a.nama}</span>
          <span class="text-xs px-2 py-1 rounded
            ${telat > 30 ? "bg-red-100 text-red-600" : "bg-yellow-100 text-yellow-600"}">
            ${telat} hari telat
          </span>
        </div>

        <div class="text-sm text-slate-500 mt-1">
          Rp ${a.cicilan.nominal.toLocaleString("id-ID")}
        </div>

        <div class="text-xs text-slate-400">
          Jatuh tempo: ${a.cicilan.jatuh_tempo}
        </div>

      </div>
    `;
  });

}

export function renderPendapatan(list){

  const box = document.getElementById("listPendapatan");
  box.innerHTML = "";

  if(!list.length){
    box.innerHTML = `<p class="p-4 text-center text-slate-400">Tidak ada data</p>`;
    return;
  }

  list.forEach(p=>{
    box.innerHTML += `
      <div class="flex justify-between items-center p-4 border-b hover:bg-slate-50">

        <div>
          <p class="font-medium">${p.note}</p>
          <p class="text-xs text-slate-400">
            ${p.periode} • ${p.user_name || "-"}
          </p>
        </div>

        <div class="text-right">
          <p class="font-bold text-green-600">
            ${rupiah(p.amount)}
          </p>
          <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-600 rounded">
            ${p.type}
          </span>
        </div>

      </div>
    `;
  });

}