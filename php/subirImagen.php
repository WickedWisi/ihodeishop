<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Verifica que se ha enviado una imagen
if (!isset($_FILES["imagen"])) {
  echo json_encode(["ok" => false, "message" => "No se envió ninguna imagen."]);
  exit;
}

$directorio = "../imagen/";
$nombreTemporal = $_FILES["imagen"]["tmp_name"];
$nombreFinal = basename($_FILES["imagen"]["name"]);

// Generar un nombre único para evitar sobrescribir archivos
$nombreUnico = uniqid("img_") . "_" . $nombreFinal;
$rutaDestino = $directorio . $nombreUnico;

// Guardar imagen en la carpeta
if (move_uploaded_file($nombreTemporal, $rutaDestino)) {
  echo json_encode([
    "ok" => true,
    "nombre" => $nombreUnico // ✅ solo devolvemos el nombre del archivo
  ]);
} else {
  echo json_encode([
    "ok" => false,
    "message" => "No se pudo guardar la imagen."
  ]);
}
?>
