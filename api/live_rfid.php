<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/../app/bootstrap.php';
require_role(['guard','admin']);
$after=(int)($_GET['after_id']??0);
$events=GateLogModel::rfidEventsAfter($after,20);
json_response(['ok'=>true,'events'=>$events,'log'=>$events[0]??null]);
