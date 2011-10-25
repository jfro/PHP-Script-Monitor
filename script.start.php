<?php
include dirname(__FILE__).'/config.php';
include dirname(__FILE__).'/classes/ScriptLogger.php';
$sl_logger = ScriptLogger::getInstance(dirname(__FILE__).'/db', SL_SHOW_ERRORS, SL_LOG_ERRORS, SL_SLOW_LIMIT);