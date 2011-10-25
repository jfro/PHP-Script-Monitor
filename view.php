<?php
require_once 'common.inc.php';

if($request->has('id')) {
	$event = JKScriptEvent::find($request['id']);
	$tpl->assign('event', $event);
}

if(!isset($event) || !$event) {
	redirect_to('index.php');
}

$tpl->assign('content', $tpl->fetch('view.tpl.php'));
$tpl->display('layout.tpl.php');