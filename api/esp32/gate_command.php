<?php
/* BISM4RCK/KUN3H0 2026 */
/* BISM4RCK/KUN3H0 2026 */
require_once __DIR__ . '/../../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
$device = trim($_GET['device'] ?? 'esp32');
$cmd = GateCommandModel::pending($device);
if (!$cmd) json_response(['ok'=>true,'command'=>null]);
GateCommandModel::markSent((int)$cmd['id'],$device);
json_response(['ok'=>true,'command'=>$cmd]);
/* BISM4RCK/KUN3H0 2026 */
