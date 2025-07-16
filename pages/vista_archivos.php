<?php

include '../api/includes/conexion.php';

$rol = $_SESSION['rol'] ?? '';
$correo = $_SESSION['correo'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Archivos Subidos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="p-4">


<div style="max-height: 90vh; overflow-y: auto;">
  <table class="table table-bordered border-secondary w-100">
    <thead>
      <tr>
        <th>Nombre del archivo</th>
        <?php if (strtolower($rol)==='administrador'): ?>
          <th>UUID</th><th>Proveedor</th>
        <?php endif; ?>
        <th>Fecha</th><th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if (strtolower($rol) === 'administrador') {
        $stmt = $conexion->prepare("
          SELECT a.id, a.nombre_archivo, a.fecha_subida, a.revision_estado, a.archivo_url, a.uuid_plantilla, u.correo
          FROM archivos_subidos a
          LEFT JOIN proveedores p ON a.proveedor_id = p.id
          LEFT JOIN usuarios u ON p.usuario_id = u.id_usuarios
          ORDER BY a.fecha_subida DESC
        ");
      } else {
        $stmt = $conexion->prepare("
          SELECT p.id
          FROM usuarios u
          JOIN proveedores p ON u.id_usuarios = p.usuario_id
          WHERE u.correo = ?
        ");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->bind_result($prov_id);
        $stmt->fetch(); $stmt->close();
        $stmt = $conexion->prepare("
          SELECT id, nombre_archivo, fecha_subida, revision_estado, archivo_url 
          FROM archivos_subidos 
          WHERE proveedor_id = ?
          ORDER BY fecha_subida DESC
        ");
        $stmt->bind_param("i", $prov_id);
      }

      $stmt->execute();
      if (strtolower($rol) === 'administrador') {
        $stmt->bind_result($id, $nombre, $fecha, $estado, $url, $uuid, $correo_prov);
      } else {
        $stmt->bind_result($id, $nombre, $fecha, $estado, $url);
      }

      while ($stmt->fetch()):
        $path = realpath(__DIR__.'/../'.$url);
        if (!file_exists($path)) continue;
      ?>
        <tr>
          <td><a href="download.php?archivo=<?=urlencode($url)?>"><?=htmlspecialchars($nombre)?></a></td>
          <?php if (strtolower($rol)==='administrador'): ?>
            <td><?=htmlspecialchars($uuid)?></td>
            <td><?=htmlspecialchars($correo_prov ?: 'Desconocido')?></td>
          <?php endif; ?>
          <td><?=htmlspecialchars($fecha)?></td>
          <td class="text-center"><?=htmlspecialchars($estado)?></td>
        </tr>
      <?php endwhile; $stmt->close(); ?>
    </tbody>
  </table>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Paginación -->
<script>
document.addEventListener('DOMContentLoaded',function(){
  const table=document.querySelector('table'),rows=Array.from(table.querySelectorAll('tbody tr'));
  const perPage=10;let cur=1;
  const div=document.createElement('div');div.id='paginacion';div.className='mt-3 d-flex justify-content-center gap-2';
  table.parentElement.appendChild(div);
  function render(p){
    rows.forEach((r,i)=>r.style.display=(i>=(p-1)*perPage&&i<p*perPage?'':'none'));
  }
  function nav(){
    div.innerHTML='';
    const total=Math.ceil(rows.length/perPage);
    const b=(t,p,d)=>{const btn=document.createElement('button');btn.textContent=t;btn.className='btn '+(p===cur?'btn-primary':'btn-outline-primary');btn.disabled=d;btn.onclick=()=>{cur=p;render(cur);nav();};div.appendChild(btn);};
    b('⏮️',1,cur===1);
    for(let i=1;i<=total;i++)b(i,i,false);
    b('⏭️',total,cur===total);
  }
  render(cur);nav();
});
</script>

</body>
</html>
