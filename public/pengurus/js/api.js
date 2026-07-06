import { API } from "./config.js";
import { getToken } from "./auth.js";

export async function getDashboardBendahara(){
  const res = await fetch(`${API}/bendahara/dashboard`, {
    headers: {
      Authorization: `Bearer ${getToken()}`
    }
  });

  return res.json();
}

export async function getSimpanan(){
  const res = await fetch(`${API}/bendahara/saldo-simpanan`,{
    headers:{
      Authorization:`Bearer ${getToken()}`
    }
  });

  if(!res.ok) throw new Error("Gagal ambil simpanan");

  return res.json();
}

export async function getPinjaman(){
  const res = await fetch(`${API}/loan/list`,{
    headers:{
      Authorization:`Bearer ${getToken()}`
    }
  });

  if(!res.ok) throw new Error("Gagal ambil pinjaman");

  return res.json();
}

export async function approveLoan(id, status, note = ""){

  const res = await fetch(`${API}/loans/${id}/approve`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${getToken()}`
    },
    body: JSON.stringify({
      status,
      note
    })
  });

  const data = await res.json();

  if(!res.ok){
    throw new Error(data.message || "Gagal approve");
  }

  return data;
}

export async function getTunggakan(){
  const res = await fetch(`${API}/bendahara/tunggakan`,{
    headers:{
      Authorization:`Bearer ${getToken()}`
    }
  });

  if(!res.ok) throw new Error("Gagal ambil tunggakan");

  return res.json();
}

export async function getLpj(){
  const res = await fetch(`${API}/lpj`,{
    headers:{
      Authorization:`Bearer ${getToken()}`
    }
  });

  if(!res.ok) throw new Error("Gagal ambil LPJ");

  return res.json();
}