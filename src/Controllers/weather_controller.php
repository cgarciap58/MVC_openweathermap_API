<?php

include_once("../Views/view.php");

// Cuando se pulsa el botón de enviar
if (isset($_POST["enviar"])) {
    $array_correctos = array(); // Array para almacenar los valores correctos
    $array_errores = array(); // Array para almacenar los errores

    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $edad = (int)$_POST['edad'];
    $email = htmlspecialchars(trim($_POST['email']));


    if (!isset($nombre) || strlen($nombre) <= 4) {
        $array_errores['nombre'] = "El nombre debe tener al menos 4 
    caracteres";
    } else {
        $array_correctos['nombre'] = $nombre;
    }

    if (!isset($edad) || $edad < 18) {
        $array_errores['edad'] = "La edad debe ser mayor o igual a 18";
    } else {
        $array_correctos['edad'] = $edad;
    }

    if (!isset($email) || strlen($email) <= 4) {
        $array_errores['email'] = "El email debe tener al menos 4 
    caracteres";
    } else {
        $array_correctos['email'] = $email;
    }

    $array_correctos_y_errores = array(
        'correctos' => $array_correctos,
        'errores' => $array_errores
    );



    if (count($array_errores) == 0) {
        include_once("../models/dao_users.php");
        $dao = new DAOUser();
        if ($dao->addUser($array_correctos)) {
            echo "Inserción exitosa";
        } else {
            echo "Error al insertar";
        }
    } else {
        View::show("user_view", $array_correctos_y_errores);
    }
            
}
echo "<hr>";

?>
