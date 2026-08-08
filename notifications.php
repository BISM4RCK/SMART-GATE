<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
require_once __DIR__ . '/app/bootstrap.php';
$ctrl = new NotificationController();
if (isset($_GET['read'])) { $ctrl->read(); } else { $ctrl->index(); }
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
/* BISM4RCK-KUN3H0 2026 */
