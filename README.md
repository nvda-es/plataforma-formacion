# Plataforma de formación

Plataforma de formación en línea escrita en PHP con base de datos MySQL. Los alumnos se registran,
verifican su correo y se matriculan en los cursos disponibles. Los cursos se escriben en Markdown y
se organizan en módulos con unidades de texto y ejercicios de respuesta abierta que corrige el profesor.

## Funcionalidades

**Alumnos**
- Registro con verificación obligatoria del correo electrónico (la cuenta no se activa hasta abrir el enlace).
- Inicio de sesión, recuperación de contraseña y reenvío del enlace de activación.
- reCAPTCHA v3 en los formularios de registro, inicio de sesión, recuperación y reenvío.
- Catálogo de cursos publicados y matrícula libre en los que elija.
- Lectura de unidades (Markdown convertido a HTML) y respuesta a ejercicios en texto libre.
- Progreso por curso: unidades leídas y ejercicios aprobados.
- Si un ejercicio se suspende, el alumno ve el comentario y puede enviar una nueva respuesta.
- El curso se marca como completado al leer todas las unidades y aprobar todos los ejercicios.

**Profesores**
- Listado de entregas de sus cursos (pendientes, aprobadas, suspendidas).
- Calificación de cada respuesta como aprobada o suspendida, con comentario de feedback.

**Administración**
- Alta manual de alumnos, profesores y administradores (con contraseña o con enlace de invitación).
- Edición, activación/desactivación y reenvío de enlace de acceso.
- Matrícula y baja manual de alumnos en cursos.
- Gestión de cursos, módulos, unidades y ejercicios (crear, editar, reordenar, eliminar).
- Contenidos en Markdown escritos en el formulario o cargados desde archivos `.md`.
- Importación de un curso completo desde un único archivo Markdown.

**Correos automáticos**

| Evento | Destinatario |
|---|---|
| Registro | Alumno: enlace de verificación del correo |
| Ejercicio enviado | Profesor del curso (o los administradores si no hay profesor asignado) |
| Ejercicio calificado | Alumno: resultado y feedback |
| Curso completado | Alumno: felicitación |
| Alta por la administración | Usuario: enlace para crear su contraseña o aviso de bienvenida |
| Recuperación de contraseña | Usuario: enlace de un solo uso (1 hora) |

## Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql`, `mbstring` y, recomendadas, `curl` e `intl`.
- MySQL 5.7+ o MariaDB 10.3+.
- [Composer](https://getcomposer.org/).
- Un servidor web (Apache, Nginx) o el servidor integrado de PHP para desarrollo.
- Claves de [reCAPTCHA v3](https://www.google.com/recaptcha/admin) para el dominio.
- Un servidor SMTP para el envío de correos en producción.

## Instalación

1. Instala las dependencias:

   ```bash
   composer install --no-dev
   ```

2. Crea la base de datos y un usuario con permisos sobre ella:

   ```sql
   CREATE DATABASE formacion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'formacion'@'localhost' IDENTIFIED BY 'una-contraseña-segura';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON formacion.* TO 'formacion'@'localhost';
   ```

3. Copia la configuración y edítala (base de datos, URL, correo y reCAPTCHA):

   ```bash
   cp config/config.example.php config/config.php
   ```

4. Crea las tablas y el primer administrador:

   ```bash
   php bin/install.php
   php bin/create-admin.php "Nombre Apellido" admin@ejemplo.es
   ```

5. (Opcional) Importa el curso de ejemplo:

   ```bash
   php bin/import-course.php content/ejemplo-accesibilidad-web.md --publish
   ```

6. Arranca el servidor de desarrollo:

   ```bash
   php -S localhost:8000 -t public public/index.php
   ```

   y abre <http://localhost:8000>.

### Producción

- La raíz pública del servidor web debe ser **`public/`**. El resto de carpetas no debe ser accesible
  (se incluyen `.htaccess` con `Require all denied` como protección adicional en Apache).
- En Apache hace falta `mod_rewrite` (el `.htaccess` de `public/` redirige todo a `index.php`).
  En Nginx: `try_files $uri /index.php?$query_string;`.
- En `config/config.php`: `app.debug => false`, `app.env => 'production'`, `app.url` con `https://`,
  `security.hsts => true` y `mail.driver => 'smtp'`.
- La carpeta `storage/logs/` debe tener permiso de escritura para PHP.
- La IP del cliente (límites de intentos y verificación de reCAPTCHA) se toma de la primera IP válida
  de la cabecera `X-Forwarded-For` (`security.trust_forwarded_for => true`). Esto requiere que un proxy
  de confianza fije esa cabecera y que no se pueda llegar a la aplicación sin pasar por él. Si la
  aplicación se expone directamente, pon la opción a `false` para usar `REMOTE_ADDR`.

### Desarrollo sin reCAPTCHA ni SMTP

- Con `mail.driver => 'log'` los correos se escriben en `storage/logs/mail.log` (incluidos los enlaces
  de verificación). **No lo uses en producción**: el archivo contiene enlaces de un solo uso.
- Con `recaptcha.enabled => false` se omite la verificación de reCAPTCHA. **Actívalo siempre en producción.**
  Para probar reCAPTCHA en local, añade `localhost` a los dominios de la clave en la consola de Google.

## Formato de los cursos en Markdown

Cada unidad o ejercicio es Markdown (con extensiones de GitHub: tablas, tachado, listas de tareas y
enlaces automáticos). Para importar un curso completo desde un archivo, usa estos encabezados:

```markdown
# Curso: Título del curso
Resumen del curso.

## Módulo: Título del módulo
Descripción del módulo (opcional).

### Unidad: Título de la unidad
Contenido de la unidad.

### Ejercicio: Título del ejercicio
Enunciado del ejercicio.
```

Solo los encabezados con esos prefijos delimitan la estructura; cualquier otro encabezado
(`## Resumen`, `#### Ejemplo`…) forma parte del contenido. Los bloques de código se respetan.
Tienes un ejemplo completo en [`content/ejemplo-accesibilidad-web.md`](content/ejemplo-accesibilidad-web.md).

## Estructura del proyecto

```
bin/            Scripts de consola (instalación, crear administrador, importar cursos)
config/         Configuración (config.php no se versiona)
content/        Cursos de ejemplo en Markdown
database/       Esquema SQL
public/         Raíz web: index.php, CSS y JS
src/            Código de la aplicación
  Controllers/  Controladores (Admin/ para la administración)
  Models/       Acceso a datos
templates/      Vistas PHP, parciales y plantillas de correo
storage/logs/   Registros de errores y de correo
```

## Seguridad

- **Inyección SQL**: todas las consultas usan sentencias preparadas de PDO con preparación real en el servidor.
- **XSS**: toda la salida se escapa con `htmlspecialchars`; el Markdown se convierte con `html_input => escape`
  y sin enlaces peligrosos (`javascript:`, `data:`…). Content-Security-Policy estricta con *nonce*.
- **CSRF**: token por sesión validado en todas las peticiones POST; cookies `SameSite=Lax`.
- **Sesiones**: cookies `HttpOnly` y `Secure` (con HTTPS), modo estricto, regeneración del identificador al
  iniciar y cerrar sesión, y cierre por inactividad (30 minutos por defecto).
- **Contraseñas**: Argon2id (o bcrypt si no está disponible), longitud mínima de 10 caracteres y rehash automático.
- **Tokens** de verificación y recuperación aleatorios (256 bits), de un solo uso, con caducidad y guardados como hash.
- **Fuerza bruta**: límite de intentos de inicio de sesión por correo y por IP, límites en registro y envíos
  de correo, reCAPTCHA v3 con comprobación de acción, puntuación y dominio.
- **Enumeración de usuarios**: registro, recuperación y reenvío responden siempre igual; el tiempo de
  respuesta del inicio de sesión se iguala aunque el correo no exista.
- **Autorización**: comprobación de rol en cada acción; el profesor solo ve las entregas de sus cursos; el
  alumno solo accede a contenidos de cursos en los que está matriculado.
- **Cabeceras**: CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`,
  `Cross-Origin-Opener-Policy` y HSTS opcional.
- **Enlaces de correo** construidos con `app.url`, nunca con la cabecera `Host` (evita envenenamiento de enlaces).
- **Subida de archivos**: los `.md` se leen como texto UTF-8 con límite de tamaño y no se guardan en disco.
- Los errores se registran en `storage/logs/app.log` y no se muestran al usuario en producción.

## Accesibilidad

Diseñada siguiendo las WCAG 2.2 nivel AA:

- HTML semántico con `lang="es"`, puntos de referencia (`header`, `nav`, `main`, `footer`) y enlace para
  saltar al contenido.
- Títulos de página únicos y jerarquía de encabezados coherente: los encabezados del Markdown se ajustan
  automáticamente para encajar bajo el `<h1>` de la página.
- Todos los campos tienen etiqueta visible, textos de ayuda y errores asociados con `aria-describedby`,
  `aria-invalid` y atributos `autocomplete`.
- Resumen de errores al principio del formulario que recibe el foco y enlaza con cada campo.
- Mensajes de estado con `role="status"` / `role="alert"`.
- Foco visible de alto contraste, contraste de color AA y estados que no dependen solo del color.
- Tablas con `caption` y encabezados con `scope`; migas de pan y navegación anterior/siguiente.
- Barra de progreso con texto equivalente.
- Botones con el nombre accesible completo (por ejemplo, «Editar el módulo Fundamentos»).
- Diseño adaptable hasta 320 px de ancho, compatible con zoom del 200 % y modo de alto contraste de Windows,
  y respeto a `prefers-reduced-motion`.
- reCAPTCHA v3 es invisible: no exige resolver ningún desafío.
- Correos en HTML sencillo con alternativa en texto plano.

Recomendación para autores: describe todas las imágenes con texto alternativo (`![descripción](url)`)
y usa `##` para los apartados principales de cada unidad.
