
# Load-Balancer

Aplicacion PHP/Laravel 11 que simula un balanceador de carga.




## Requerimientos
PHP 8.4.2 (ZTS)
Extension PHP Parallel
MariaDB 11.6.2
Composer

## Authors

- [@eliasvsv](https://github.com/eliasvsv/apolo-loadbalancer)


## Instalacion
. Clonar el repositorio git
. cd apolo-loadbalancer
. composer install
. npm install 
. configurar una conexion a base de datos (de preferencia MariaDB)
. php artisan migrate
. php artisan job:load-balancer 10 5 2 
