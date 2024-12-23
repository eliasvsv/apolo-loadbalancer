
# Load-Balancer

Aplicacion PHP/Laravel 11 que simula un balanceador de carga.




## Requerimientos
* PHP 8.4.2 (ZTS)
* Extension PHP Parallel
* MariaDB 11.6.2
* Composer

## Authors

- [@eliasvsv](https://github.com/eliasvsv/apolo-loadbalancer)


## Instalacion
* Clonar el repositorio git
* cd load_balancer
* composer install
* npm install 

## Documentacion# Documentación: Feature Job Load Balancer

## Historia de Usuario

### Título: **Balanceador de Carga para Trabajos Distribuidos**

#### Como:
Administrador del sistema.

#### Quiero:
Un sistema que distribuya de forma eficiente trabajos (jobs) entre múltiples workers simulados, aprovechando la paralelización para procesarlos rápidamente.

#### Para:
Asegurar que los trabajos se completen de forma dinámica y eficiente, asignando nuevos trabajos a los workers tan pronto como terminen los anteriores.

---

## Descripción General

El sistema implementa un Job Load Balancer que:

1. Inicializa trabajos pendientes en una base de datos.
2. Crea y gestiona múltiples workers que procesan trabajos en paralelo.
3. Monitorea el estado de los jobs y workers en tiempo real.
4. Escala dinámicamente los workers según la cantidad de trabajos pendientes.

Los workers:
- Procesan un trabajo a la vez.
- Cambian su estado a `idle` tan pronto terminan un trabajo, permitiéndoles tomar otro disponible.

El sistema registra eventos clave en logs, como la asignación y finalización de trabajos.

---

## Casos de Uso para Testing

### Caso 1: Inicialización de Trabajos

#### Escenario:
Se ejecuta el comando para inicializar 10 trabajos.

#### Acción:
Ejecutar el comando:
```bash
php artisan job:load-balancer 10 5 2
```

#### Resultado Esperado:
- Se crean 10 registros en la base de datos con estado `pending`.
- Se registra un mensaje indicando: “Se han creado 10 trabajos.”

### Caso 2: Creación Dinámica de Workers

#### Escenario:
El sistema comienza con un bloque de 2 workers y escala hasta 5 workers según los trabajos pendientes.

#### Acción:
Ejecutar el comando con los parámetros indicados.

#### Resultado Esperado:
- Se crean workers en bloques de 2 hasta alcanzar el límite de 5.
- Los logs registran la creación de cada worker.

### Caso 3: Asignación de Trabajos a Workers

#### Escenario:
Un worker disponible toma un trabajo pendiente.

#### Acción:
Observar los logs mientras los workers procesan trabajos.

#### Resultado Esperado:
- Un trabajo cambia su estado de `pending` a `in-progress` al ser asignado.
- El estado del worker cambia a `busy`.
- Los logs registran el ID del worker y del trabajo asignado.

### Caso 4: Finalización de Trabajos

#### Escenario:
Un worker finaliza un trabajo y toma otro disponible.

#### Acción:
Esperar a que el trabajo termine y observar el comportamiento del worker.

#### Resultado Esperado:
- El trabajo cambia su estado a `completed`.
- El estado del worker regresa a `idle`.
- Los logs registran la finalización del trabajo y el tiempo tomado.

### Caso 5: Finalización Total de Trabajos

#### Escenario:
Todos los trabajos han sido completados.

#### Acción:
Dejar que el comando se ejecute hasta finalizar.

#### Resultado Esperado:
- Todos los trabajos en la base de datos tienen el estado `completed`.
- Todos los workers están en estado `idle`.
- El comando finaliza mostrando: “Todos los trabajos han sido completados.”

---

## Consideraciones para Testing

1. **Volumen de Trabajos:**
   - Probar con diferentes cantidades de trabajos: 10, 50, 100.
   
2. **Escalado de Workers:**
   - Verificar el escalado correcto con bloques de tamaño 2, 3, y 5.

3. **Paralelismo:**
   - Confirmar que los workers procesan trabajos en paralelo y no de manera secuencial.

4. **Logs:**
   - Validar que todos los eventos clave se registran correctamente.

5. **Errores Controlados:**
   - Simular errores en la base de datos o fallos de los workers y verificar que el sistema se recupera correctamente.

---

## Referencias

1. **Framework Utilizado:** Laravel 9.
2. **Biblioteca para Paralelismo:** PHP Parallel.
3. **Base de Datos:** MySQL.

