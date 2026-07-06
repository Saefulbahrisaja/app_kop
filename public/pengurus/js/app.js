import { navigate } from "./router.js";
import { getUser, logout, protectPage } from "./auth.js";

document.addEventListener("DOMContentLoaded", () => {

  protectPage();

  // USER
  const user = getUser();
  if(user){
    document.getElementById("userName").innerText = user.full_name;
    document.getElementById("userNameMobile").innerText = user.full_name;
  }

  // LOGOUT
  document.getElementById("btnLogout").addEventListener("click", logout);

  // MENU CLICK (FIX)
  document.querySelectorAll("[data-page]").forEach(menu => {
    menu.addEventListener("click", function(e){
      e.preventDefault();

      const page = this.dataset.page;

      console.log("CLICK:", page); // debug

      // ACTIVE STYLE
      document.querySelectorAll("[data-page]").forEach(m=>{
        m.classList.remove("bg-indigo-600","text-white");
      });

      this.classList.add("bg-indigo-600","text-white");

      navigate(page);
    });
  });

  // DEFAULT LOAD
  navigate("dashboard");

});