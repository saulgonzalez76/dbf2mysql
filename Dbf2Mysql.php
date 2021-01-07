<?php
$base_datos = filter_input(\INPUT_POST, 'db');
$server = filter_input(\INPUT_POST, 'server');
$usuario = filter_input(\INPUT_POST, 'user');
$password = filter_input(\INPUT_POST, 'pass');
$processed = 0;
if (!file_exists(__DIR__."/status")){
	exec("echo '1' > ".__DIR__."/status");
    exec("echo '<?= \"1;$processed\"; ?>' > ".__DIR__."/processed.php");

	include "clsDbf2Mysql.php";

    foreach (glob(__DIR__."/files/*.[zZ][iI][pP]") as $zipfile) {
        exec("unzip ". $zipfile . " -d ".__DIR__."/files");
        unlink($zipfile);
    }

	foreach (glob(__DIR__ . "/files/*.[dD][bB][fF]") as $filename) {
        if (!file_exists(__DIR__."/sql/" . pathinfo($filename)['filename'] . ".sql")) {
            dbf2mysql::guardarSQL($filename,__DIR__."/sql/");
        }
		unlink($filename);
	}

	foreach (glob(__DIR__."/sql/*.sql") as $filename) {
		$conexion = new PDO ("mysql:host=$server;dbname=".$base_datos, $usuario, $password);
        	$sql = "DROP TABLE " . $base_datos . "." . pathinfo($filename)['filename'];
        	$sth = $conexion->prepare($sql);
        	$sth->execute();
		exec("mysql -h$server -u" . $usuario . " -p" . $password . " --default-character-set='latin1' " . $base_datos . " < " . $filename);
		$sql = "show tables like '" . pathinfo($filename)['filename'] . "'";
        $sth = $conexion->prepare($sql);
        $sth->execute();
		if ($row = $sth->fetch()){
			unlink($filename);
		}
		$processed ++;
        exec("echo '<?= \"2;$processed\"; ?>' > ".__DIR__."/processed.php");
	}
    unlink(__DIR__."/status");
    unlink(__DIR__."/processed.php");
}
exit;

