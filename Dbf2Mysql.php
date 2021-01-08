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

    foreach (glob(__DIR__."/sql/*.txt") as $filename) {
        exec("mysql -h$server -u" . $usuario . " -p" . $password . " --default-character-set='latin1' " . $base_datos . " < " . txt_csv_mysql($filename,pathinfo($filename)['filename']));
        $conexion = new PDO ("mysql:host=$server;dbname=".$base_datos, $usuario, $password);
        $sql = "show tables like '" . pathinfo($filename)['filename'] . "'";
        $sth = $conexion->prepare($sql);
        $sth->execute();
        if ($row = $sth->fetch()){
            unlink($filename);
        }
        $processed ++;
        exec("echo '<?= \"2;$processed\"; ?>' > ".__DIR__."/processed.php");
    }

    foreach (glob(__DIR__."/sql/*.csv") as $filename) {
        exec("mysql -h$server -u" . $usuario . " -p" . $password . " --default-character-set='latin1' " . $base_datos . " < " . txt_csv_mysql($filename,pathinfo($filename)['filename']));
        $conexion = new PDO ("mysql:host=$server;dbname=".$base_datos, $usuario, $password);
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

function txt_csv_mysql($filename,$table){
// file into multidimensional array
    $array = file($filename,FILE_IGNORE_NEW_LINES);
    for ($i=0;$i<sizeof($array);$i++){
        $array[$i] = explode(",",$array[$i]);
    }

// sets number of fields and its max length
    $varchar = [];
    if (sizeof($array)<1) exit;
    for($i=0;$i<sizeof($array[0]);$i++){
        array_push($varchar,0);
    }
    foreach ($array as $element){
        foreach ($element as $key=>$field){
            if (strlen($field) > $varchar[$key]) $varchar[$key] = strlen($field);
        }
    }

// sets field names without quotes
    foreach ($array[0] as $key=>$header){
        $header = str_replace('"','',$header);
        $header = str_replace("'","\'",$header);
        $array[0][$key] = $header;
    }
    $field_names = $array[0];

// removes header line
    array_splice($array,0,1);

// creates the insert values for query
    $array_q = [];
    foreach ($array as $insert){
        array_push($array_q,"(".(sizeof($array_q)+1).",'" . implode("','",$insert) . "')");
    }
    $insert_into = "insert into `".$table."` values ";

// create table query
    $table_q = "create table `".$table."` ( id int auto_increment primary key,";
    foreach ($field_names as $key=>$field){
        $table_q .= $field . " varchar(" . $varchar[$key] . "), ";
    }
    $table_q = substr($table_q,0,-2);
    $table_q .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;";

//insert fields by 500
    $sql_file_name = "/tmp/" . time() . ".sql";

    $sql_file = fopen($sql_file_name,'w');
    fwrite($sql_file,"/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
    fwrite($sql_file,"/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
    fwrite($sql_file,"/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
    fwrite($sql_file,"/*!50503 SET NAMES utf8mb4 */;\n");
    fwrite($sql_file,"/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n");
    fwrite($sql_file,"/*!40103 SET TIME_ZONE='+00:00' */;\n");
    fwrite($sql_file,"/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n");
    fwrite($sql_file,"/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n");
    fwrite($sql_file,"/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n");
    fwrite($sql_file,"/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n");

    fwrite($sql_file,"drop table if exists `".$table."`;\n");
    fwrite($sql_file,"/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
    fwrite($sql_file,"/*!50503 SET character_set_client = utf8mb4 */;\n");
    fwrite($sql_file,$table_q);
    fwrite($sql_file,"\n\n");
    fwrite($sql_file,"/*!40101 SET character_set_client = @saved_cs_client */;\n");


    fwrite($sql_file,"LOCK TABLES `".$table."` WRITE;\n");
    fwrite($sql_file,"/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n");
    fwrite($sql_file,"\n\n");

    for($i=0;$i<sizeof($array_q);$i++){
        if (($i % 500) == 0) {
            fwrite($sql_file,$insert_into);
        }
        if (((($i+1) % 500) == 0) || ($i == (sizeof($array_q)-1))) {
            fwrite($sql_file, $array_q[$i] . ";\n\n");
        } else {
            fwrite($sql_file,$array_q[$i] . ",");
        }
    }

    fwrite($sql_file,"/*!40000 ALTER TABLE `".$table."` ENABLE KEYS */;\n");
    fwrite($sql_file,"UNLOCK TABLES;");
    fwrite($sql_file,"/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;\n");

    fwrite($sql_file,"/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;\n");
    fwrite($sql_file,"/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
    fwrite($sql_file,"/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;\n");
    fwrite($sql_file,"/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
    fwrite($sql_file,"/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
    fwrite($sql_file,"/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");
    fwrite($sql_file,"/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;\n");

    fclose($sql_file);

    return $sql_file_name;
}