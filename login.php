<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/app/bootstrap.php';
$ctrl = new AuthController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $ctrl->login(); } else { $ctrl->loginForm(); }
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
