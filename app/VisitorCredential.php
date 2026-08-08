<?php
/* BISM4RCK/KUN3H0 2026 */
function generate_visitor_id(): string {
    $a='ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $s='';
    for($i=0;$i<6;$i++) $s.=$a[random_int(0,strlen($a)-1)];
    return $s;
}
function visitor_status_url(string $id): string {
    return (defined('BASE_URL')?rtrim(BASE_URL,'/'):'').'/visitor/status.php?id='.rawurlencode($id);
}
/* BISM4RCK/KUN3H0 2026 */
/* BISM4RCK-KUN3H0 2026 */
