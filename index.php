<?php 

function __autoload($class_name) {
	include $class_name . '.php';
}

function pre(){
	echo "<pre>".PHP_EOL;
	foreach (func_get_args() as $arg) {
		echo "\t";print_r($arg);echo " ";}
	echo PHP_EOL."</pre>".PHP_EOL;
}

new Language();

$S = new SS("htmls.html");
$data = [
		"buildName"	=> "towncenter",
		"level"		=> 11,
		"time" 		=> time(),
		"id" 		=> 222222,
		"user" 		=> [
					"name" 	=> "John",
					"age"	=> 20
				],
		"items" 	=> [
					[
						"id"	=> 5,
						"name"	=> "Lorem ipsum. 1"
					],
					[
						"id"	=> 6,
						"name"	=> "Lorem ipsum. 2"
					],
					[
						"id"	=> 7,
						"name"	=> "Lorem ipsum. 3"
					],
				],
		"listItem" 	=> [
					[
						"id"	=> 1,
						"name"	=> "some name 1"
					],
					[
						"id"	=> 2,
						"name"	=> "some name 2"
					],
					[
						"id"	=> 3,
						"name"	=> "some name 3"
					],
				]
	];

// $S->elem($data);
$S->page([
	"title"		=> "SS",
	"script"	=> ["script/jquery.js","script/ss.js","script/script.js"],
	"header" 	=> "menu here",
	"footer" 	=> "copyright here",
	"data" 		=> $data
	]);
		
$time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];


// echo "Process time: $time seconds\n";
// echo "<br>\nend of doc";
?>
