import { API } from "./config.js";

export async function loginRequest(username, password) {
  const res = await fetch(`${API}/login`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      username,
      password
    })
  });

  if (!res.ok) {
    throw new Error("Server error");
  }

  return await res.json();
}

export function getUser() {
  return JSON.parse(localStorage.getItem("user"));
}

export function getToken() {
  return localStorage.getItem("token");
}

export function logout() {
  localStorage.clear();
  window.location.href = "/index.html";
}

export function protectPage() {
  if (!getToken()) {
    window.location.href = "/index.html";
  }
}