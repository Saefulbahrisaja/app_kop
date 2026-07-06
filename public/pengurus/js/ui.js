function rupiah(angka){
  return "Rp " + angka.toLocaleString("id-ID");
}

export function renderBendahara(data){

  // SIMPANAN
  document.getElementById("simpananPokok").innerText = rupiah(data.simpanan.pokok);
  document.getElementById("simpananWajib").innerText = rupiah(data.simpanan.wajib);
  document.getElementById("simpananManasuka").innerText = rupiah(data.simpanan.manasuka);

  // KAS
  document.getElementById("kasSaldo").innerText = rupiah(data.kas.saldo);
  document.getElementById("kasInflow").innerText = rupiah(data.kas.inflow);
  document.getElementById("kasOutflow").innerText = rupiah(data.kas.outflow);

  // PIUTANG
  document.getElementById("piutangTotal").innerText = rupiah(data.piutang.total_pinjaman);
  document.getElementById("piutangTerbayar").innerText = rupiah(data.piutang.terbayar);
  document.getElementById("piutangSisa").innerText = rupiah(data.piutang.sisa);

  // AKUNTANSI
  document.getElementById("akSaldoKas").innerText = rupiah(data.kas_akuntansi.saldo_kas);
  document.getElementById("akPendapatan").innerText = rupiah(data.kas_akuntansi.pendapatan_shu);
  document.getElementById("akPengeluaran").innerText = rupiah(data.kas_akuntansi.pengeluaran);
  document.getElementById("akSaldoBersih").innerText = rupiah(data.kas_akuntansi.saldo_bersih);

  // STATUS
  document.getElementById("statusSaldo").innerText = rupiah(data.status_saldo.saldo);
  
  const statusEl = document.getElementById("statusText");
  statusEl.innerText = data.status_saldo.status;

  // warna dinamis
  if(data.status_saldo.color === "GREEN"){
    statusEl.className = "bg-green-500 px-2 py-1 rounded text-white";
  } else {
    statusEl.className = "bg-red-500 px-2 py-1 rounded text-white";
  }

  document.getElementById("statusWarning").innerText =
    "⚠ " + data.status_saldo.warning;
}