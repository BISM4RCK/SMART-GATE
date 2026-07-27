<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/_bootstrap.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(['ok' => false, 'message' => 'Method not allowed'], 405); }
$ticketId = (int)($_POST['ticket_id'] ?? 0);
$reply = trim($_POST['reply'] ?? '');
if (!$ticketId || $reply === '') { json_response(['ok' => false, 'message' => 'Reply is required'], 422); }
TicketModel::reply($ticketId, (int)current_user()['id'], $reply);
json_response(['ok' => true, 'message' => 'Reply saved']);
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
