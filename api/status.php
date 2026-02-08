<?php
// Simple API endpoint to verify backend health
header('Content-Type: application/json');
echo json_encode([
    "status" => "online",
    "backend" => "PHP " . phpversion(),
    "server_time" => date("Y-m-d H:i:s")
]);
?>