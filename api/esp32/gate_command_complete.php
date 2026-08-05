<?php
/* BISM4RCK/KUN3H0 2026 */
/* BISM4RCK/KUN3H0 2026 */
require_once __DIR__ . '/../../app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
$id=(int)($_POST['command_id']??0);
if(!$id) json_response(['ok'=>false,'message'=>'command_id required'],422);
json_response(['ok'=>GateCommandModel::complete($id)]);
/* BISM4RCK/KUN3H0 2026 */
