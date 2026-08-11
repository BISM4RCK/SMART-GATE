<?php
/* BISM4RCK-KUN3H0 2026 */
require_once __DIR__ . '/../app/bootstrap.php';
require_role('admin');
$me=current_user();
$id=trim((string)($_GET['session_id']??''));
if($id==='') json_response(['ok'=>false,'message'=>'Missing RFID burn session.'],422);
$session=RfidScanSessionModel::get($id,(int)$me['id']);
if(!$session) json_response(['ok'=>false,'message'=>'RFID burn session not found.'],404);
$result=$session['result_json']?json_decode($session['result_json'],true):null;
json_response(['ok'=>true,'status'=>$session['status'],'result'=>$result,'message'=>$result['message']??null]);
