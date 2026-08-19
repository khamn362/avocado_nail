<?php
require 'config/database.php';
$stf = $pdo->query('SELECT id, name, status, working_hours_start, working_hours_end FROM staff')->fetchAll();
echo json_encode(['staff' => $stf]);
echo "\n";
$appts = $pdo->query('SELECT id, staff_id, appointment_date, appointment_time, end_time, status FROM appointments')->fetchAll();
echo json_encode(['appts' => $appts]);
?>
