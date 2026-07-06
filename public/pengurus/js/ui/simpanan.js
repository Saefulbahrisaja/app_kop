function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}

let currentPage = 1;
const perPage = 10;
let originalData = [];

export function initSimpananFeature(data){

  originalData = data.data;

  document.getElementById("totalAnggota").innerText =
    data.total_anggota + " Anggota";

  const total = originalData.reduce((sum,a)=>sum+a.simpanan.total,0);
  document.getElementById("totalSimpanan").innerText = rupiah(total);

  render();

  // SEARCH REALTIME
  document.getElementById("searchInput").addEventListener("input", e=>{
    const keyword = e.target.value.toLowerCase();

    currentPage = 1;

    const filtered = originalData.filter(a =>
      a.nama.toLowerCase().includes(keyword)
    );

    render(filtered);
  });
}

export function render(filteredData = originalData){

  const data = filteredData;

  const start = (currentPage - 1) * perPage;
  const end = start + perPage;

  const paginated = data.slice(start, end);

  renderSimpananTable(paginated);
  renderPagination(data.length);
}

export function renderSimpananTable(list){

  const tbody = document.getElementById("simpananTable");
  tbody.innerHTML = "";

  list.forEach(a=>{
    tbody.innerHTML += `
      <tr class="border-b hover:bg-slate-50">
        <td class="p-3">${a.nama}</td>
        <td class="p-3 text-right">${rupiah(a.simpanan.pokok)}</td>
        <td class="p-3 text-right">${rupiah(a.simpanan.wajib)}</td>
        <td class="p-3 text-right">${rupiah(a.simpanan.manasuka)}</td>
        <td class="p-3 text-right font-bold text-indigo-600">
          ${rupiah(a.simpanan.total)}
        </td>
      </tr>
    `;
  });

}

function renderPagination(total){

  const totalPage = Math.ceil(total / perPage);
  const container = document.getElementById("pagination");

  container.innerHTML = "";

  for(let i=1;i<=totalPage;i++){
    container.innerHTML += `
      <button data-page="${i}"
        class="px-3 py-1 rounded ${
          i === currentPage
          ? "bg-indigo-600 text-white"
          : "bg-white border"
        }">
        ${i}
      </button>
    `;
  }

  // CLICK PAGE
  container.querySelectorAll("button").forEach(btn=>{
    btn.addEventListener("click", ()=>{
      currentPage = parseInt(btn.dataset.page);
      render();
    });
  });
}