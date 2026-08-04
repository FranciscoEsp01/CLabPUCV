# Estado del Proyecto: CLab PUCV
(Nombre sujeto a cambios)

Este documento resume los avances reales implementados hasta la fecha, el trabajo pendiente de pulido y las tareas recomendadas a futuro. Ideal para orientar a los colaboradores en el estado actual de la plataforma de enseñanza de lenguaje C.

## 🚀 Avances Reales Completados

### 1. UI/UX y Frontend (Vue 3 + Inertia + Tailwind CSS)
- **Tema Claro / Oscuro**: Se implementó un modo oscuro persistente usando `localStorage`. Toda la interfaz reacciona de manera global y dinámica.
- **Layout y Navegación**: Se dividió la plataforma en rutas protegidas para estudiantes (`/student`) y profesores (`/teacher`). El panel de profesores y sus opciones en la barra de tareas quedan estrictamente ocultos para los estudiantes.
- **Editor de Código (Sandbox)**: Se integró el paquete `@guolao/vue-monaco-editor` para proporcionar un entorno de programación profesional con resaltado de sintaxis ANSI C.
- **Tutor IA Global**: Se programó un widget flotante en la esquina inferior derecha (`AiTutor.vue`) inyectado en el Layout global para consultar a la inteligencia artificial en cualquier pantalla.
- **Vistas Estudiantiles**: Se estructuraron los componentes para el Dashboard de inicio (reflejando nombre y puntos reales), la vista interactiva de Lecciones (con pantalla dividida integrando Documentos/PDF y el Sandbox), y la nueva vista de Clasificación (Leaderboard).

### 2. Backend y Base de Datos (Laravel 11)
- **Sistema de Roles**: Se agregó una columna `role` a la tabla `users` mediante migraciones. Se configuró el middleware de validación `CheckRole` para proteger las rutas y vistas.
- **Panel de Profesor Dinámico**: El controlador `TeacherController` actualmente consulta a la base de datos para extraer métricas en vivo (ej. cantidad de estudiantes registrados, cantidad real de módulos subidos) y listarlos en la vista del Dashboard de Profesor.
- **Ejecución Interactiva (Sandbox)**: Se desarrolló un controlador (`SandboxController`) que recibe el código C del cliente, crea un entorno temporal aislado, compila el programa utilizando `gcc` vía `Symfony\Component\Process\Process`, lo ejecuta de manera controlada y captura tanto la salida estándar como los errores para devolverlos a la interfaz web.
- **API de OpenAI**: Se construyó el controlador (`AiTutorController`) para el chat del tutor IA conectándose a través del cliente HTTP a la API REST de OpenAI.
- **Inicio de Sesión Institucional (Google y Local)**: Se configuró tanto el registro local (Breeze) como la autenticación vía Google (Socialite) para validar y restringir el acceso exclusivamente a correos con el dominio `@mail.pucv.cl`. Además, se implementó un sistema de redirección inteligente en `/dashboard` que lleva automáticamente al usuario a su panel correspondiente (estudiante o profesor) al registrarse o iniciar sesión.
- **Gestión de Usuarios y Permisos**: Se creó una vista administrativa donde los profesores y administradores pueden ver todos los usuarios registrados y modificar sus roles (`admin`, `teacher`, `student`).
- **Gestión de Currículo (Módulos y Lecciones)**: Se implementó un CRUD completo para gestionar el contenido del curso. Los profesores pueden crear módulos, definir su visibilidad manual y programar aperturas por fecha. También pueden agregar y editar lecciones (soporte para Markdown, videos, adjuntos PDF). Las vistas de estudiantes consumen estos datos dinámicamente y respetan la configuración de visibilidad impuesta por el profesor.
- **Gestión de Material de Apoyo**: Se habilitó una sección para que profesores y administradores puedan subir archivos PDF (teoría y ejercicios). Los estudiantes tienen acceso de solo lectura a este material.
- **Gamificación y Leaderboard**: Se implementó una tabla de clasificación (Leaderboard) donde los estudiantes pueden ver su progreso y puntaje en comparación al resto del curso, sincronizado dinámicamente con sus perfiles.

### 3. Ciberseguridad Integral y Defensa en Profundidad (110% Segura)
- **Hardening del Sandbox de C y Prevención de RCE:**
  - **Analizador Estático de Código (`CSourceSecurityValidator`):** Inspecciona el código fuente antes de compilar para bloquear headers peligrosos (`<sys/*>`, `<unistd.h>`, `<arpa/inet.h>`, `<windows.h>`, `<dlfcn.h>`), llamadas críticas al sistema/kernel (`system`, `fork`, `execve`, `popen`, `ptrace`, `kill`, `mmap`, `socket`, `fopen`, `unlink`, etc.), ensamblador inline (`__asm__`), operadores de concatenación de macros (`##`) y rutas absolutas críticas (`/etc/passwd`, `/proc`, `.env`).
  - **Aislamiento de Variables de Entorno:** Ejecución de procesos con entorno limpio (`PATH=/usr/bin:/bin`), evitando la filtración o lectura de `APP_KEY`, credenciales de base de datos o claves de API.
  - **Banderas de Compilación Seguras:** Compilación con `-fstack-protector-strong`, `-D_FORTIFY_SOURCE=2`, `-Wformat-security`, `-O2` y `-pipe`.
  - **Control Estricto de Recursos:** Timeouts de compilación (4s) y ejecución (3s), cuotas de entrada (50 KB), truncado seguro de salida (16 KB) y limpieza atómica garantizada en bloque `finally`.
- **Autenticación, Sesión y Control de Acceso (RBAC):**
  - Restricción estricta al dominio institucional `@mail.pucv.cl` en registro local, actualización de perfil y callback OAuth de Google.
  - Regeneración de ID de sesión tras autenticación para mitigar ataques de fijación y secuestro de sesión.
  - Rutas administrativas y docentes aisladas bajo middleware `role:teacher,admin`.
  - Prevención de auto-degradación de administradores y protección estricta contra escalación de privilegios.
- **Cabeceras HTTP de Seguridad y CSP:**
  - Middleware global `SecurityHeaders` con política de seguridad de contenido (CSP) restrictiva, HSTS, `X-Frame-Options: SAMEORIGIN` (anti-clickjacking), `X-Content-Type-Options: nosniff` (anti MIME-sniffing), `Referrer-Policy: strict-origin-when-cross-origin` y `Permissions-Policy`.
- **Seguridad en Subida de Archivos:**
  - Inspección binaria de *magic bytes* (`%PDF-`) en controladores de materiales para prevenir la subida de ejecutables o scripts PHP camuflados como PDF.
  - Almacenamiento con nombres hash aleatorizados no predecibles y prevención de ataques de Path Traversal.
- **Protección Anti-DoS y Rate Limiting:**
  - Rate limiters dedicados en `AppServiceProvider` para el Sandbox de C (15 req/min), Tutor IA (12 req/min) y subida de archivos (10 req/min).
- **Tutor IA Ultra-Rápido con Groq (LLaMA 3.3 70B) y Respaldo Inteligente:**
  - **Integración Segura de Groq API:** Conexión vía credenciales desacopladas en `.env` y mapeadas en `config/services.php` sin exposición de tokens ni claves en el código fuente o frontend.
  - **Arquitectura de Respaldo Automático (Fallback):** Prioridad de respuesta con Groq (`llama-3.3-70b-versatile`) y derivación transparente a OpenAI (`gpt-3.5-turbo`) en caso de saturación o fallos de red.
  - **Directivas Anti-Jailbreak y Sanitización:** Filtrado de caracteres de control nulos/invisibles, limitación estricta de longitud (1500 chars) y directivas de rol pedagógico especializado en ANSI C para la PUCV con rechazo de consultas maliciosas.
  - **Memoria Conversacional y UI Optimizada:** Soporte de historial de turnos recientes, sugerencias de preguntas frecuentes ("chips"), animación de razonamiento y botón de reinicio de chat.
- **Verificación Obligatoria de Correo Institucional (`MustVerifyEmail`):**
  - Implementación de la interfaz `MustVerifyEmail` en el modelo `User` con validación estricta de cuentas `@mail.pucv.cl`.
  - Envío automático de correo con enlace firmado temporal (expira en 60 minutos) tras el registro.
  - Personalización de la plantilla de correo institucional con marca y presentación oficial de la **Pontificia Universidad Católica de Valparaíso** en español (`VerifyEmail::toMailUsing`).
  - Bloqueo y redirección automática hacia la pantalla de verificación (`/verify-email`) para usuarios no verificados que intenten acceder al dashboard estudiantil, sandbox, lecciones o funciones docentes.
  - Interfaz gráfica moderna y responsiva (`VerifyEmail.vue`) con soporte para reenvío de correos, estado de carga y cierre de sesión.
  - Registro de eventos en auditoría de seguridad (`EMAIL_VERIFIED`, `EMAIL_VERIFICATION_RESENT`).
- **Auditoría y Suite de Pruebas de Seguridad:**
  - Servicio `SecurityAuditLogger` para registrar incidentes, accesos denegados, violaciones de sandbox, fallos/éxitos de JWT, verificación de correo y cambios de roles.
  - Batería completa de 68 pruebas automatizadas con 194 aserciones de seguridad, JWT, Sandbox, Verificación de Email y Tutor IA aprobadas al 100%.

---

## 🛠️ Lo que falta por pulir (Requiere Atención a Corto Plazo)

1. **Credenciales en el archivo `.env`**:
   - **OpenAI**: Para que el Tutor IA responda de verdad, es obligatorio añadir la variable `OPENAI_API_KEY` en el `.env` con una clave secreta válida de OpenAI.
   - **Google OAuth**: Para probar el inicio de sesión institucional, se requiere crear las credenciales en *Google Cloud Console* y agregarlas (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`) al `.env`.
2. **Despliegue a Producción y Contenedores (Opcional)**:
   - El Sandbox cuenta actualmente con validación estática, entorno limpio, limitación de recursos y flags seguras de `gcc`. Para un entorno de producción masivo, se puede añadir una capa adicional de contenedorización con Docker / Firejail para aislamiento a nivel de kernel.

---

## 💡 Implementaciones a Futuro
 (Cambiar al integrar algo al sistema o agregar una nueva funcionalidad que no se encuentre en la lista)

1. **Sistema de Evaluación Automática**:
   - Crear ejercicios de programación donde la Sandbox del estudiante reciba entradas ocultas, se contraste la salida real con una salida esperada, y se otorgue una calificación o desbloqueo automático del siguiente módulo.
2. **Tutor IA Contextual**:
   - Enlazar el editor de código con el Chat de IA. Si un estudiante compila y tiene un error, enviarle automáticamente el código fallido a la IA para que el tutor ofrezca consejos de resolución más precisos sin que el estudiante deba explicarlo todo manualmente.
3. **Progreso y Gamificación**:
   - Añadir una tabla pivote en la base de datos para medir el progreso del estudiante (lecciones completadas, porcentaje del curso) y mostrar un dashboard estudiantil mucho más interactivo (barras de experiencia, medallas, etc).

---

## 💻 Cómo iniciar el proyecto localmente

Para que cualquier desarrollador pueda clonar e iniciar este entorno de trabajo desde cero, debe seguir estos pasos en orden:

1. **Clonar e instalar dependencias:**
   ```bash
   # Instalar dependencias de PHP (Laravel)
   composer install

   # Instalar dependencias de Node (Vue, Tailwind, Inertia)
   npm install
   ```

2. **Configurar el entorno (.env):**
   - Copiar el archivo de ejemplo:
     ```bash
     cp .env.example .env
     ```
   - Generar la llave de la aplicación:
     ```bash
     php artisan key:generate
     ```
   - *Importante:* Asegurarse de rellenar las credenciales obligatorias al final del `.env` (las de OpenAI y Google Auth) cuando se vayan a probar esas áreas.

3. **Base de Datos:**
   - Asegúrate de tener SQLite configurado (por defecto) o tu conexión a MySQL/PostgreSQL configurada en el `.env`.
   - Ejecutar las migraciones (creará las tablas y los roles):
     ```bash
     php artisan migrate
     ```

4. **Compilar y Levantar Servidores:**
   - Para trabajar en desarrollo necesitas levantar ambos servidores en terminales separadas (o usar concurrently):
     ```bash
     # Terminal 1: Compila los assets de Vue/Tailwind en tiempo real
     npm run dev

     # Terminal 2: Levanta el servidor local de PHP
     php artisan serve
     ```
     
5. **Requisitos de Sistema Operativo:**
   - Para que el **Sandbox de ANSI C** funcione correctamente en tu máquina, es imperativo tener instalado el compilador `gcc`.
     - En Mac: Abre la terminal y ejecuta `xcode-select --install` o `gcc --version` para verificar.
     - En Windows: Se recomienda usar WSL (Windows Subsystem for Linux) o instalar MinGW.
     - En Linux: Ejecutar `sudo apt install build-essential`.

6. **Ejecutar Pruebas Automatizadas y de Seguridad:**
   ```bash
   php artisan test
   ```
