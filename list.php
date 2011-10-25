<?php
require_once 'common.inc.php';

// page stuff
if($request->hasPost('delete')) {
	foreach($request['delete'] as $id) {
		$evt = JKScriptEvent::find($id);
		if($evt) {
			$evt->delete();
		}
	}
	//Flash::notice('Deleted selected events');
	//redirect_to('list.php');
}
if($request->has('type')) {
	$selected_type = $request['type'];
}
else {
	$selected_type = 'all';
}

if($request->has('order')) {
	$order = $request['order'];
}
else {
	$order = 'desc';
}

if($request['col']) {
	$field = $request['col'];
}
else {
	$field = 'last_occurred_on';
}

if($request->has('page')) {
	$page = intval($request['page']);
}
else {
	$page = 0;
}

if($selected_type != 'all') {
	list($events, $page_count) = JKScriptEvent::paginate('all', array('type = ?',$selected_type), $field.' '.$order, 30, $page);
}
else {
	list($events, $page_count) = JKScriptEvent::paginate('all', null, $field.' '.$order, 30, $page);
}
$tpl->assign('page_count', $page_count);
$tpl->assign('page', $page);
$tpl->assign('events', $events);
$tpl->assign('field', $field);

if($request['order'] == 'asc') {
	$order = 'desc';
}
else {
	$order = 'asc';
}

$tpl->assign('selected_type', $selected_type);
$tpl->assign('order', $order);
$tpl->assign('current_order', $order == 'desc' ? 'asc' : 'desc');

$tpl->assign('content', $tpl->fetch('list.tpl.php'));
$tpl->display('layout.tpl.php');
