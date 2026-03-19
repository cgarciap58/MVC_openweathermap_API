<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
</head>

<body>

<div class="container">
    <div class="jumbotron">
        <h1 class="display-4">Trabajando con Formularios en PHP</h1>
        <p class="lead">Procesando datos de formularios desde scripts PHP.</p>
        <hr class="my-4">
      </div>

<form class="row g-3 needs-validation" id="formusuario" name="formusuario" action="../Controllers/user_controller.php" method="POST"> 
    <div class="col-md-4">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombre" name="nombre"  value="<?php if (isset($data['correctos']['nombre'])) { echo $data['correctos']['nombre']; } ?>">
        <?php if (isset($data['errores']['nombre'])) {
    echo "<div class='alert alert-danger'>".$data['errores']['nombre']."</div>";
        } ?>
    </div>
    <div class="col-md-2">
        <label for="edad" class="form-label">Edad</label>
        <input type="text" class="form-control" id="edad" name="edad" value="<?php if (isset($data['correctos']['edad'])) { echo $data['correctos']['edad']; } ?>">
    <?php if (isset($data['errores']['edad'])) {
    echo "<div class='alert alert-danger'>".$data['errores']['edad']."</div>";
        } ?>    </div>
    <div class="col-md-2">
        <label for="email" class="form-label">Email</label>
        <input type="text" class="form-control" id="email" name="email" value="<?php if (isset($data['correctos']['email'])) { echo $data['correctos']['email']; } ?>">
    <?php if (isset($data['errores']['email'])) {
    echo "<div class='alert alert-danger'>".$data['errores']['email']."</div>";
        } ?>    </div>

    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="" id="terminos" >
            <label class="form-check-label" for="terminos">
                Acepto los términos.
            </label>
            <div class="invalid-feedback">
                You must agree before submitting.
            </div>
        </div>
    </div>
    <div class="col-12">
        <input class="btn btn-primary" type="submit" name="enviar" value="Enviar">
    </div>
</form>
</div>
</body></html>