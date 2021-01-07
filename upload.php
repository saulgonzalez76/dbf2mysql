<?php
/**
 * Desarrollado por Saul Gonzalez Villafranca (RFC:GOVS7612304W2)
 * Intellibasc es propiedad de Gonvisa SPR, se prohibe la copia o distribucion de este codigo.
 * Ultima actualizacion 7/02/19 06:58 PM.
 * Copyright (c) 2019. Todos los derechos reservados
 */

exec("echo '<?= \"1;0\"; ?>' > ".__DIR__."/processed.php");
if (!is_dir(__DIR__."/files")) {
    $oldmask = umask(0); mkdir(__DIR__."/files", 0775, true); umask($oldmask);
}
if (!is_dir(__DIR__."/sql")) {
    $oldmask = umask(0); mkdir(__DIR__."/sql", 0775, true); umask($oldmask);
}

if (!empty($_FILES)) {
    for($i=0;$i<sizeof($_FILES['dbfiles']['name']);$i++) {
        move_uploaded_file($_FILES['dbfiles']['tmp_name'][$i],__DIR__."/files/" . $_FILES['dbfiles']['name'][$i]);
    }
}

?>


