function validarContrasenas(prefijo) {
  const pass1 = document.getElementById(`contrasena${capitalize(prefijo)}`).value;
  const pass2 = document.getElementById(`contrasena${capitalize(prefijo)}2`).value;
  const alerta = document.getElementById('alerta-password');

  if (pass1 !== pass2) {
    alerta.style.display = 'block';
    return false; // evita el envío del formulario
  } else {
    alerta.style.display = 'none';
    return true; // permite el envío
  }
}

function capitalize(string) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}