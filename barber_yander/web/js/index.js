// Archivo: index.js

// Espera a que el DOM esté cargado
document.addEventListener("DOMContentLoaded", () => {
  console.log("Página Barber Yander cargada correctamente ✅");

  // Botones principales
  const btnLogin = document.querySelector(".btn.login");
  const btngaleria = document.querySelector(".btn.galeria");

  // Redirección a login.html (para iniciar sesión o reservar)
  btnLogin.addEventListener("click", () => {
    window.location.href = "login.html";
  });

});
