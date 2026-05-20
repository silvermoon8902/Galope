# Galope

Juego de predicciones de carreras de caballos. Los jugadores arman el podio de
cada carrera (1.er, 2.do y 3.er puesto), suman puntos segun aciertan y compiten
en una tabla de clasificacion. Incluye un panel de administracion para gestionar
carreras, resultados, reglas de puntuacion y usuarios.

## El nucleo del proyecto: la maquinaria de confianza

Un juego de predicciones vive de la confianza. Tres piezas la sostienen y son
el centro del codigo:

1. **Bloqueo de predicciones.** Cuando llega la hora de cierre de una carrera,
   ninguna prediccion puede crearse ni modificarse. Esto se decide en el
   servidor (`RaceService::predictionsOpen()` y la verificacion dentro de
   `PlayerController::predict()`), nunca en el navegador. La cuenta regresiva en
   pantalla es solo informativa.
2. **Motor de puntuacion configurable.** Cuanto vale cada acierto se define en
   la tabla `scoring_rules` y se edita desde el panel, sin tocar codigo
   (`app/Scoring.php`).
3. **Carga de resultados confiable.** Al cargar el resultado oficial de una
   carrera, el motor evalua todas las predicciones en una sola transaccion
   atomica y actualiza la clasificacion (`RaceService::loadResult()`).

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
