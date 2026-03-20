# Aplicación de consulta de tiempo mediante consulta a la API de OpenWeatherMap

Esta aplicación está desarrollada con el patrón MVC y utiliza Docker para su despliegue. En lenguaje de programación PHP.


## Estructura del proyecto

El código está organizado en las siguientes carpetas y ficheros:

- `docker-compose.yml`: Configuración de Docker Compose.
- `db/`: Script de creación de la base de datos.
- `apache/`: Configuración de Apache.
- `src/`: Código fuente de la aplicación.
- `src/Controllers/`: Controladores de la aplicación.
- `src/Models/`: Modelos de la aplicación.
- `src/Views/`: Vistas de la aplicación.
- `src/Views/view.php`: Vista principal de la aplicación. Es llamada por el controlador, pasándole el nombre de la vista a mostrar.
- `src/Views/header.php`: Cabecera de la aplicación web, presente en todas las vistas.
- `src/Views/footer.php`: Pie de página de la aplicación web, presente en todas las vistas.
- `.env`: Variables de entorno. No están publicadas en este repositorio.
- `README.md`: Documentación del proyecto.

## Despliegue

El despliegue se realizará mediante clonado de este mismo repositorio en una instancia EC2 de AWS.
Dicha instancia se asociará a un dominio público mediante el servicio gratuito de noip. 