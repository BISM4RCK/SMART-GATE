<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/../app/bootstrap.php';
$ctrl = new VisitorController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { $ctrl->submit(); } else { $ctrl->form(); }
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK-KUN3H0 2026 */
