import { approveLoan } from "../api.js";

function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}

function statusBadge(status){

  const map = {
    PENDING: "bg-yellow-100 text-yellow-600",
    APPROVED: "bg-green-100 text-green-600",
    REJECTED: "bg-red-100 text-red-600"
  };

  return map[status] || "bg-slate-100 text-slate-500";
}

export function renderPinjaman(data){

  document.getElementById("totalPinjaman").innerText =
    data.total + " Pengajuan";

  const tbody = document.getElementById("pinjamanTable");

  if(!data.data || data.data.length === 0){
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-10 text-slate-400">
          Belum ada pengajuan pinjaman
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = "";

  data.data.forEach(p=>{

    tbody.innerHTML += `
      <tr class="border-b hover:bg-slate-50">

        <!-- NAMA -->
        <td class="p-3 font-medium">
          ${p.anggota?.nama || "-"}
        </td>

        <!-- JUMLAH -->
        <td class="p-3 text-right font-bold text-indigo-600">
          ${rupiah(p.amount)}
        </td>

        <!-- TENOR -->
        <td class="p-3 text-center">
          ${p.term_months} Bulan
        </td>

        <!-- TANGGAL -->
        <td class="p-3 text-sm text-slate-500">
          ${p.tanggal_pengajuan}
        </td>

        <!-- STATUS -->
        <td class="p-3 text-center">
          <span class="px-2 py-1 text-xs rounded-full ${statusBadge(p.status)}">
            ${p.status}
          </span>
        </td>

        <!-- ACTION -->
        <td class="p-3 text-center space-x-2">

          ${
            p.can_approve
            ? `<button class="approveBtn px-2 py-1 text-xs bg-green-500 text-white rounded"
                data-id="${p.pinjaman_id}">
                Approve
              </button>`
            : ""
          }

          ${
            p.can_reject
            ? `<button class="rejectBtn px-2 py-1 text-xs bg-red-500 text-white rounded"
                data-id="${p.pinjaman_id}">
                Reject
              </button>`
            : ""
          }

        </td>

      </tr>
    `;
  });

  // EVENT BUTTON
  bindAction();
}

/* ================= ACTION ================= */

function bindAction(){

  // APPROVE
  document.querySelectorAll(".approveBtn").forEach(btn=>{
    btn.onclick = async () => {

      const id = btn.dataset.id;

      if(!confirm("Setujui pinjaman ini?")) return;

      btn.innerText = "Loading...";
      btn.disabled = true;

      try{
        await approveLoan(id, "APPROVED");

        alert("✅ Berhasil approve");

        // reload halaman
        location.reload();

      }catch(err){
        alert("❌ " + err.message);
      }
    };
  });

  // REJECT
  document.querySelectorAll(".rejectBtn").forEach(btn=>{
    btn.onclick = async () => {

      const id = btn.dataset.id;

      const note = prompt("Alasan penolakan (opsional):");

      if(note === null) return;

      btn.innerText = "Loading...";
      btn.disabled = true;

      try{
        await approveLoan(id, "REJECTED", note);

        alert("❌ Pinjaman ditolak");

        location.reload();

      }catch(err){
        alert("❌ " + err.message);
      }
    };
  });

}