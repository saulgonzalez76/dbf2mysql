<?php
error_reporting(E_ERROR | E_PARSE);

	class dbf2mysql {
		protected $archivoDBF	= "";
		protected $nombre		= "";
		protected $ruta		= "";
		protected $tamano		= 0;
		protected $cantidadCampos		= 0;
		protected $cantidadRegistros	= 0;
		protected $titulosCampos	= Array();
		protected $salidaSQL		= Array();
		protected $tiempoInicio		= 0;
		protected $fileName = "";
		protected $lineaInsert = "";
		protected $registro = "";

		private function __construct($ficheroDBF,$ruta) {
			$this->ruta = $ruta;
			$this->archivoDBF = $ficheroDBF;
			$this->nombre = pathinfo($ficheroDBF)['filename'];
			$this->tamano = filesize($this->archivoDBF);
			$this->tiempoInicio = $this->getFullTime();
		}
		
		public static function guardarSQL($archivo,$ruta) {
			$dbf2sql = new dbf2mysql($archivo,$ruta);
			if ($dbf2sql->convert())
				$dbf2sql->saveToFile();
		}

		public function getName() {
			return $this->fileName;
		}

		protected function getFullTime() {
			$timeInicio = explode(" ",microtime());
			return $timeInicio[1] + $timeInicio[0];
		}

		protected function saveToFile() {
			$this->fileName = $this->ruta . $this->nombre . ".sql";
			/* si el archivo ya existe se elimina */
			if (is_file($this->fileName)) {
				echo "Ya existe otro fichero con el nombre '" . $this->getName() . "' en el directorio actual\n";
				exit;
			}
			/* abrimos el archivo */
			if ($file = @fopen($this->fileName,"w")) {
				foreach($this->salidaSQL as $linea)
					fputs($file, "$linea\n");
				fclose($file);
				//echo "El archivo se almaceno en e	l directorio actual con el nombre '". $this->getName() ."'\n";
			} else
				echo "No se puede escribir en el directorio\n";
		}
		
		protected function convert() {
			/* verificamos que se posea la libreria para trabajar con ficheros dBase */
			if ($this->dBaseOk()) {
				/* verificamos que el archivo exista */
				if (is_file($this->archivoDBF) AND is_readable($this->archivoDBF)) {
					/* Si todo esta Ok abrimos el fichero para trabajar sobre el */
					$this->archivoDBF		= dbase_open($this->archivoDBF,0);
					/* obtenemos la cantidad de campos */
					$this->cantidadCampos	= dbase_numfields($this->archivoDBF);
					/* obtenemos la cantidad de registros */
					$this->cantidadRegistros= dbase_numrecords($this->archivoDBF);
					/* obtenemos los titulos de los campos */
					$this->titulosCampos	= dbase_get_header_info($this->archivoDBF);
					/* convertimos el fichero DBF a SQL */
					return $this->convertir2sql();
				} else {
					echo "El fichero '" . $this->archivoDBF . "' no existe o no tiene permisos de lectura\n";
					return False;
				}
			} else {
				echo "Se necesita la libreria 'dbase' para poder convertir ficheros DBF\n";
				return False;
			}
		}
		
		protected function dBaseOk() {
			/* con esta funcion verifico si existe la libreria necesaria para trabajar con ficheros DBF */
			/* obtengo las librerias compiladas en PHP */
			$utilidades = get_loaded_extensions();
			/* recorro las librerias para verificar si existe dbase */
			foreach (get_loaded_extensions() AS $libreria)
				if (strtolower($libreria) == "dbase")
					/* si existe retorno true */
					return True;
			/* si llegue aqui es porque no existe. retorno false */
			return False;
		}
		
		protected function convertir2sql() {
			/* creamos la cabecera del archivo SQL */
			$this->crearCabecera();

			/* creamos la tabla */
			if (!$this->crearTabla())
				/* si se produzco un error retornamos false */
				return False;

			/* volvamos la tabla */
			if (!$this->crearRegistros())
				/* si se produzco un error retornamos false */
				return False;

			/* cerramos el fichero DBF */
			if (!$this->cerrarDBF())
				/* si se produzco un error retornamos false */
				return False;

			/* creamos el footer del archivo */
			$this->crearFooter();

			/* si llegamos aqui todo fue bien */
			return True;
		}
		
		protected function crearCabecera() {
			$this->agregar("--");
			$this->agregar("-- HDS Converter 0.2 (2009-08-20)");
			$this->agregar("-- Contact to: gschimpf.com");
			$this->agregar("--");
			$this->agregar("");
			$this->agregar("/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;");
			$this->agregar("/*!40103 SET TIME_ZONE='+00:00' */;");
			$this->agregar("/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;");
			$this->agregar("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;");
			$this->agregar("/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;");
			$this->agregar("/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;");
			$this->agregar("");
		}
		
		protected function crearTabla() {
			/*
				creamos la cabecera con el nombre y el primer campo que sera la palabla 'cod' + el nombre de la tabla
				ej: 'codNombretabla'
			*/
			$nombreTabla = $this->nombre;
			$codPrimario = "cod" . ucfirst($this->nombre);
			/* creamos la linea inicial del CREATE TABLE */
			$this->cabeceraTabla = "CREATE TABLE IF NOT EXISTS $nombreTabla (";
			/* verificamos que alla tomado valores */
			if ($nombreTabla == "" OR $codPrimario == "cod") {
				echo "La cabecera no se creo correctamente\n";
				return False;
			}
			/* recorremos los campos tomando el nombre de cada uno y su tipo */
			$campos = "";
			for ($i = 0; $i < $this->cantidadCampos; $i++) {
				/* obtenemos los datos campo */
				$campo = $this->titulosCampos[$i];
				/* obtenemos el nombre desde los datos campo */
				$tituloCampo = strtolower($campo['name']);
				/* obtenemos el tipo desde los datos campo */
				$tipoCampo = $this->getTipoCampo($campo);
				/* verificamos que alla tomado valores */
				if ($tituloCampo == "" or $tipoCampo == "") {
					echo "Uno de los campos no se creo correctamente\n";
					return False;
				}
				/* armamos el tipo de campo */
				if ($campos !== "") {
					$this->cabeceraTabla .= ",";
				}
				$this->cabeceraTabla .= "`$tituloCampo` $tipoCampo NULL";
				$campos = $this->cabeceraTabla;
			}
			/* finalizamos la cabecera */
			$this->cabeceraTabla .= ");";
			/* cargamos la cabecera a la salida SQL y si se produzco un error retornamos false */
			$this->agregar($this->cabeceraTabla);
			/* si llegamos hasta aqui todo va Ok */
			return True;
		}
		
		protected function getTipoCampo($campo) {
			switch($campo['type']) {
				case 'number':
					return 'double('.$campo['length'].','.$campo['precision'].')';
				case 'date':
					return 'date';
				case 'character':
					return 'varchar('.$campo['length'].')';
					break;
				case 'boolean':
					return 'tinyint(1)';
				case 'memo':
					return 'varchar(100)';
			}
			return '';
		}
		
		protected function crearRegistros() {
			/* agregamos una cabecera */
			$this->crearCabeceraVolcado();
			/* agregamos la linea para realizar el bloqueo de la tabla */
			$this->bloquearTabla();

			/* volvamos los registros */
			if (!$this->volcarRegistros())
				/* si se produzco un error retornamos false */
				return False;

			/* desbloqueamos de la tabla */
			$this->desbloquearTabla();

			/* si llegamos hasta aqui todo va Ok */
			return True;
		}
		
		protected function crearCabeceraVolcado() {
			/* ponemos una cabecera de la tabla */
			$this->agregar("");
			$this->agregar("--");
			$this->agregar("-- Volcando datos para la tabla " . $this->nombre);
			$this->agregar("--");
			$this->agregar("");
		}
		
		protected function volcarRegistros() {
			/* recorremos los registros */
			$count = 0;
			for ($i = 1; $i <= $this->cantidadRegistros; $i++) {
				/* create an insert with 500 records or less */
				if (($count % 500) == 0) {
					$this->crearLineaInsert(); $query = "";}
				if ($this->obtenerRegistro($i)) {
					$count ++;
					$record = "(";
					$ajuste = 0;
					for ($j = 0; $j < $this->cantidadCampos; $j++) {
						if ($this->titulosCampos[$j]['type'] !== "memo") {
							$dato = str_replace("'", "\'", $this->registro[$j - $ajuste]);
							if ($record !== "(") {
								$record .= ",";
							}
							$record .= "'" . trim($dato) . "'";
						} else {
							$ajuste++;
							if ($record !== "(") {
								$record .= ",";
							}
							$record .= "''";
						}
					}
					$query .= $record . ")";

					if ((($count % 500) == 0) || ($i == $this->cantidadRegistros)) {
						$this->lineaInsert .= $query . ";";
						$this->agregar($this->lineaInsert);
					} else {
						$query .= ",";
					}
				}
			}
			return True;
		}
		
		protected function crearLineaInsert() {
			$this->lineaInsert = "INSERT INTO `" . $this->nombre . "` VALUES ";
		}
		
		protected function obtenerRegistro($lugar) {
			$this->registro = dbase_get_record($this->archivoDBF,$lugar);
			if (!$this->registro) {
				/* si se produzco un error retornamos false */
				echo "No se pudo obtener el registro";
				return False;
			}
			return True;
		}
		
		protected function cerrarDBF() {
			/* cerramos la tabla */
			if (!dbase_close($this->archivoDBF))
				return False;
			return True;
		}
		
		protected function bloquearTabla() {
			/* agregamos una linea para que se realize el bloqueo de la tabla */
			$this->agregar("LOCK TABLES `" . $this->nombre . "` WRITE;");
		}
		
		protected function desbloquearTabla() {
			/* agregamos una linea para desbloquear la tabla */
			$this->agregar("UNLOCK TABLES;");
		}
		
		protected function crearFooter() {
			$this->agregar("/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;");
			$this->agregar("");
			$this->agregar("/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;");
			$this->agregar("/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;");
			$this->agregar("/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;");
			$this->agregar("/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;");
			$this->agregar("");
			$this->agregar("-- Conversion finalizada. " . date("Y-m-d H:i:s"));
			$this->agregar("-- Duracion de la conversion: " . $this->tiempoTotal());
		}

		protected function tiempoTotal() {
			$this->tiempoInicio = $this->getFullTime() - $this->tiempoInicio;
			return number_format($this->tiempoInicio,4,",",".") . " segundos";
		}

		protected function agregar($linea) {
			/* agregamos una linea a la salida SQL */
			$this->salidaSQL[] = $linea;
		}
	}
?>
