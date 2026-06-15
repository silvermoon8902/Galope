# Galope

Juego de apuesta por puntos sobre carreras de caballos. Por cada carrera, el
jugador elige una modalidad de juego, distribuye sus 50 puntos entre uno, dos o
tres caballos, y apuesta unicamente al ganador. Si su caballo gana, los puntos
apostados se multiplican por el dividendo oficial del hipodromo.

## Reglas del juego

Por cada carrera el jugador elige UNA modalidad. Todas se juegan unicamente al
caballo ganador; el segundo y el tercer puesto no entran en el calculo.

- **Full Point** : 50 puntos a 1 caballo.
- **Dual Point** : 25 puntos a cada uno de 2 caballos.
- **Smart Point** : 30 + 15 + 5 puntos a 3 caballos distintos.

Cuando se publica el resultado oficial, el motor calcula:

```
puntos del jugador = (puntos apostados al caballo ganador) x (dividendo oficial)
```

Si el caballo ganador no esta entre los caballos elegidos, la jugada suma cero.

Ejemplo: el jugador apuesta Full Point con 50 puntos al caballo 4. El 4 gana con
dividendo oficial 5. El jugador suma 50 x 5 = 250 puntos en esa carrera.

## La maquinaria de confianza

Tres piezas sostienen el juego y son el centro del codigo:

1. **Bloqueo de predicciones.** Cuando llega la hora de cierre de una carrera,
   ninguna jugada puede crearse ni modificarse. Esto se decide en el servidor
   (`RaceService::predictionsOpen()` y la verificacion dentro de
   `PlayerController::predict()`), nunca en el navegador.
2. **Motor de puntuacion auditable.** Toda la formula vive en `app/Scoring.php`:
   modalidades, stakes y el calculo `stake_en_ganador * dividendo`. Una sola
   funcion, sin sorpresas.
3. **Carga de resultados atomica.** Al cargar ganador + dividendo, el motor
   evalua todas las jugadas de esa carrera en una sola transaccion y actualiza
   la clasificacion (`RaceService::loadResult()`).

## Stack

- PHP 8.0 o superior (probado en 8.3)
- MySQL 5.7 / 8.x en produccion (PDO). SQLite disponible para pruebas locales.
- HTML, CSS y JavaScript sin dependencias externas
- Servidor web Apache (incluye `.htaccess`) o Nginx

## Instalacion

1. Copiar la configuracion de ejemplo y completarla:

   ```
   cp config/config.example.php config/config.php
   ```

   En `config/config.php` poner `driver => 'mysql'` y los datos de la base.

2. Crear la base de datos vacia en MySQL (por ejemplo `galope`).

3. Cargar el esquema y los datos de ejemplo:

   ```
   php bin/migrate.php --fresh
   ```

4. Apuntar el servidor web al directorio `public/` como raiz del sitio.

### Prueba rapida local (sin MySQL)

Con `driver => 'sqlite'` en `config/config.php`:

```
php bin/migrate.php --fresh
php -S localhost:8000 -t public public/index.php
```

Abrir `http://localhost:8000`.

## Despliegue en Railway

El repositorio incluye un `Dockerfile` (PHP 8.3 + Apache) y `railway.json`.
La configuracion de la base se toma de variables de entorno cuando
`config/config.php` no existe (ver `config/env.php`).

1. En railway.app: **New Project → Deploy from GitHub repo** y elegir el
   repositorio `Galope`. Railway detecta el `Dockerfile` y construye la imagen.
2. En el proyecto: **New → Database → Add MySQL**.
3. En el servicio de la app, pestana **Variables**: agregar
   `DATABASE_URL` con el valor de referencia `${{MySQL.MYSQL_URL}}`.
4. Railway vuelve a desplegar. El entrypoint espera a la base, crea el esquema
   y carga los datos de ejemplo en el primer arranque.
5. En **Settings → Networking → Generate Domain** para obtener la URL publica.

Cada `git push` a la rama `main` dispara un nuevo despliegue automatico.

## Cuentas de ejemplo

Las crea `bin/migrate.php`:

- Administrador: `admin@galope.test` / `galope-admin`
- Jugador: `jugador1@galope.test` / `galope-demo` (tambien jugador2 a jugador5)

## Estructura

```
config/      configuracion (config.php no se versiona)
db/          esquemas MySQL y SQLite
bin/         migrate.php: instala el esquema y carga datos de ejemplo
app/         Database, helpers, Scoring, RaceService, Auth, controladores
app/sources/ ResultProvider: punto de extension para una API de resultados
views/       plantillas PHP (layout, jugador, admin, auth)
public/      raiz web: index.php (front controller) y assets
```

## Ciclo de vida de una carrera

`Programada` -> `Predicciones abiertas` -> `Bloqueada` -> `Finalizada`

El estado se calcula combinando el estado guardado con el reloj
(`RaceService::effectiveStatus()`): aunque una carrera figure como abierta,
si ya paso su hora de cierre cuenta como bloqueada.

## Integracion con una API de carreras

Los resultados hoy se cargan a mano desde el panel. El codigo esta preparado
para una fuente automatica: `app/sources/ResultProvider.php` define la interfaz
a implementar cuando se decida la API. Esa implementacion solo debe obtener el
podio y llamar a `RaceService::loadResult(...)` con `source = 'api'`; el motor
de puntuacion no cambia.

## Notas de seguridad

- Contrasenas con `password_hash` (bcrypt).
- Token CSRF en todos los formularios.
- Reglas del juego (bloqueo, puntuacion, resultados) validadas en el servidor.
- Antes de produccion: quitar el bloque "Cuentas de demostracion" de la
  pantalla de ingreso (`views/auth/login.php`) y cambiar las claves sembradas.
