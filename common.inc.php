<?php
include dirname(__FILE__).'/script.start.php';

set_include_path(dirname(__FILE__).'/classes'.PATH_SEPARATOR.'.');
require_once 'Jfro/Jfro.php';
Jfro::loadClass('Savant3');
Jfro::loadClass('Jfro_Request');
Jfro::loadClass('Jfro_Database_Object');
Jfro::loadClass('JKScriptEvent');

$request = new Jfro_Request();

Jfro_Database_Object::setDatabase($sl_logger->getDatabase());
$tpl = new Savant3();
$tpl->addPath('template', dirname(__FILE__).'/templates');
$tpl->addPath('resource', dirname(__FILE__).'/helpers');

function redirect_to($url) {
	header("Location: ".$url);
	exit();
}