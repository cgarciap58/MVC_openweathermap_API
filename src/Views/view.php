
<?php
/*    Clase View para mostrar una vista. Contiene un método estático que deberá ser invocado
    desde cualquier controlador cuando hay que mostrar una vista.


    */
class View {
    
    /*
     Método estático para mostrar una vista. Construye la vista utilizando la cabecera, la vista a mostrar y el pie de página. 
     Se le pueden pasar datos a la vista a través del parámetro $data.
     Parámetros:
        $viewName: nombre de la vista a mostrar.
        $data: parámetro, de tipo array indexado, que contiene los datos que serán utilizados en la vista. Si no 
        necesita datos $data será null.
    */
    public static function show ($viewName, $data=null){
        include_once ("header.php");
        include ("$viewName.php");    
        include_once ("footer.php");
    }
}


?>