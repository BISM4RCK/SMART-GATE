<?php
/* BISM4RCK/KUN3H0 2026 */
function build_resident_house_number(string $block,string $lot,string $letter=''):string{
 $block=trim($block);$lot=trim($lot);$letter=strtoupper(trim($letter));
 if(!preg_match('/^[0-9]+$/',$block)||!preg_match('/^[0-9]+$/',$lot)) throw new InvalidArgumentException('Block and lot must be numbers.');
 if($letter!==''&&!preg_match('/^[A-Z]$/',$letter)) throw new InvalidArgumentException('Household letter must be A-Z.');
 return $block.' - '.$lot.($letter!==''?' - '.$letter:'');
}
/* BISM4RCK/KUN3H0 2026 */
/* BISM4RCK-KUN3H0 2026 */
