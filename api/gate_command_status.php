<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['guard','admin']);
$id=(int)($_GET['command_id']??0);
$cmd=GateCommandModel::find($id);
if(!$cmd) json_response(['ok'=>false,'message'=>'Gate command not found.'],404);
json_response(['ok'=>true,'status'=>$cmd['status'],'command_id'=>(int)$cmd['id']]);
