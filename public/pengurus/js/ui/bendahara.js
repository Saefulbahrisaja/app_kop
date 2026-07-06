function rupiah(n){
  return "Rp " + (n || 0).toLocaleString("id-ID");
}

export function renderBendahara(data){

  /* ================= SIMPANAN ================= */
  document.getElementById("simpananPokok").innerText = rupiah(data.simpanan.pokok);
  document.getElementById("simpananWajib").innerText = rupiah(data.simpanan.wajib);
  document.getElementById("simpananManasuka").innerText = rupiah(data.simpanan.manasuka);

  /* ================= KAS ================= */
  document.getElementById("kasSaldo").innerText = rupiah(data.kas.saldo);
  document.getElementById("kasInflow").innerText = rupiah(data.kas.inflow);
  document.getElementById("kasOutflow").innerText = rupiah(data.kas.outflow);

  /* ================= PIUTANG ================= */
  document.getElementById("piutangTotal").innerText =
    "Total: " + rupiah(data.piutang.total_pinjaman);

  document.getElementById("piutangTerbayar").innerText =
    "Bayar: " + rupiah(data.piutang.terbayar);

  document.getElementById("piutangSisa").innerText =
    rupiah(data.piutang.sisa);

  // progress bar (dynamic)
  const persen =
    (data.piutang.terbayar / data.piutang.total_pinjaman) * 100;

  const progressBar = document.querySelector(".bg-amber-400");
  if(progressBar){
    progressBar.style.width = persen + "%";
  }

  /* ================= KAS AKUNTANSI ================= */
  document.getElementById("akSaldoKas").innerText =
    rupiah(data.kas_akuntansi.saldo_kas);

  document.getElementById("akPengeluaran").innerText =
    rupiah(data.kas_akuntansi.pengeluaran);

  document.getElementById("akSaldoBersih").innerText =
    rupiah(data.kas_akuntansi.saldo_bersih);

  /* ================= STATUS ================= */
  document.getElementById("statusSaldo").innerText =
    rupiah(data.status_saldo.saldo);

  const statusText = document.getElementById("statusText");
  statusText.innerText = data.status_saldo.status;

  // warna dinamis
  if(data.status_saldo.color === "GREEN"){
    statusText.className =
      "inline-block px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500 text-white";
  } else {
    statusText.className =
      "inline-block px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-red-500 text-white";
  }

  document.getElementById("statusWarning").innerText =
    "⚠ " + data.status_saldo.warning;
}