function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}

export function renderShu(data){

  document.getElementById("shuPendapatan").innerText =
    rupiah(data.total_pendapatan);

  document.getElementById("shuBiaya").innerText =
    rupiah(data.biaya_detail.total);

  document.getElementById("shuTotal").innerText =
    rupiah(data.shu);

  const box = document.getElementById("shuPembagian");
  box.innerHTML = "";

  Object.entries(data.pembagian).forEach(([k,v])=>{
    box.innerHTML += `
      <div class="flex justify-between p-3 border-b text-sm">
        <span class="capitalize">${k.replace('_',' ')}</span>
        <span class="font-bold">${rupiah(v)}</span>
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