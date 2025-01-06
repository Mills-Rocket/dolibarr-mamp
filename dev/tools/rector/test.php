<?php

// File to test development of rules for rector
require '../../../htdocs/master.inc.php';

//var_dump($conf);

!empty($conf->global->bbb);

empty($conf->global->cccc);

$aaa = 'ccc';
empty($conf->global->$aaa);

if ($conf->global->$aaa == 1) {
	// Nothing
}

empty($conf->global->cccc >= 0);

if ($conf->global->eee >= 2 && $conf->global->fff == 1 && !$conf->global->ggg) {
	// Nothing
}

$result = $db->idate($conf->global->hhh);

$result = min($conf->global->iii1, $conf->global->iii2);
