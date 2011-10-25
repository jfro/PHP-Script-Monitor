<?php
require_once 'common.inc.php';
$date = date('Y-m-d H:i:s', strtotime('- 1 day'));
$events = JKScriptEvent::find('all', array('occurred_on > ?', $date), 'occurred_on desc', 5);
$tpl->assign('events', $events);

$tpl->assign('content', $tpl->fetch('summary.tpl.php'));
$tpl->display('layout.tpl.php');