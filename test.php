<?php
include 'script.start.php';
class MyTest {
	protected $var;
	function __construct() {
		$this->var = 'test';
	}
}

if(array_key_exists('test', $_GET)) {
	switch($_GET['test']) {
		case 'notice':
			$test++;
			break;
		case 'slow':
			sleep(2);
			break;
		default:
			trigger_error('No such test: '.$_GET['test'], E_USER_WARNING);
	}
}
if(array_key_exists('member', $_POST)) {
	trigger_error('Unsupported form data! or so i say', E_USER_ERROR);
	print 'Form submitted!';
}
if(array_key_exists('session', $_GET)) {
	session_start();
	$_SESSION['test'] = array('bling' => array('test', 'one', 'two'));
	$_SESSION['test2'] = new MyTest();
	trigger_error('Session test', E_USER_ERROR);
	header("Location: test.php");
}
?>
<form action="" method="POST" enctype="multipart/form-data">
<input type="text" name="member[firstname]" value="" />
<input type="text" name="member[lastname]" value="" />
<input type="submit" name="submit-button" value="Submit" />
</form>
<a href='?test=slow'>Slow Test</a> | <a href='?test=notice'>Notice Test</a> | <a href="?session=1">Session Test</a>