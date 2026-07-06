import { loginRequest } from "./auth.js";

const form = document.getElementById("loginForm");
const message = document.getElementById("message");
const btn = document.getElementById("btnLogin");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const username = document.getElementById("username").value;
  const password = document.getElementById("password").value;

  // UI state loading
  btn.disabled = true;
  btn.innerText = "Loading...";
  message.innerText = "";

  try {
    const data = await loginRequest(username, password);

    if (data.success) {
      // simpan
      localStorage.setItem("token", data.token);
      localStorage.setItem("user", JSON.stringify(data.user));

      message.innerText = "Login berhasil";
      message.className = "text-green-500 text-center text-sm";

      setTimeout(() => {
        window.location.href = "/pengurus/dashboard.html";
      }, 800);

    } else {
      throw new Error("Login gagal");
    }

  } catch (err) {
    message.innerText = err.message;
    message.className = "text-red-500 text-center text-sm";
  } finally {
    btn.disabled = false;
    btn.innerText = "Login";
  }
});