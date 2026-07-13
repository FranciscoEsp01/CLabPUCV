# Estado del Proyecto: CLab PUCV
(Nombre sujeto a cambios)

Este documento resume los avances reales implementados hasta la fecha, el trabajo pendiente de pulido y las tareas recomendadas a futuro. Ideal para orientar a los colaboradores en el estado actual de la plataforma de enseñanza de lenguaje C.

##  Avances Reales Completados

### 1. UI/UX y Frontend (Vue 3 + Inertia + Tailwind CSS)
- **Tema Claro / Oscuro**: Se implementó un modo oscuro persistente usando `localStorage`. Toda la interfaz reacciona de manera global y dinámica.
- **Layout y Navegación**: Se dividió la plataforma en rutas protegidas para estudiantes (`/student`) y profesores (`/teacher`).
- **Editor de Código (Sandbox)**: Se integró el paquete `@guolao/vue-monaco-editor` para proporcionar un entorno de programación profesional con resaltado de sintaxis ANSI C.
- **Tutor IA Global**: Se programó un widget flotante en la esquina inferior derecha (`AiTutor.vue`) inyectado en el Layout global para consultar a la inteligencia artificial en cualquier pantalla.
- **Vistas Estudiantiles**: Se estructuraron los componentes para el Dashboard de inicio (reflejando nombre y puntos reales), la vista interactiva de Lecciones (con pantalla dividida integrando Documentos/PDF y el Sandbox), y la nueva vista de Clasificación (Leaderboard).
### 2. Backend y Base de Datos (Laravel 11)
- **Sistema de Roles**: Se agregó una columna `role` a la tabla `users` mediante migraciones. Se configuró el middleware de validación `CheckRole` para proteger las rutas y vistas.
- **Panel de Profesor Dinámico**: El controlador `TeacherController` actualmente consulta a la base de datos para extraer métricas en vivo (ej. cantidad de estudiantes registrados) y listarlos en la vista del Dashboard de Profesor.
- **Ejecución Interactiva (Sandbox)**: Se desarrolló un controlador (`SandboxController`) que recibe el código C del cliente, crea un entorno temporal (`/storage/app/sandbox`), compila el programa utilizando `gcc` vía `Symfony\Component\Process\Process`, lo ejecuta de manera segura y captura tanto la salida estándar como los errores para devolverlos a la interfaz web.
- **API de OpenAI**: Se construyó el controlador (`AiTutorController`) para el chat del tutor IA conectándose a través del cliente HTTP a la API REST de OpenAI.
- **Inicio de Sesión Institucional (Google y Local)**: Se configuró tanto el registro local (Breeze) como la autenticación vía Google (Socialite) para validar y restringir el acceso exclusivamente a correos con el dominio `@mail.pucv.cl` (con excepciones para administradores).
- **Gestión de Usuarios y Permisos**: Se creó una vista administrativa donde los profesores y administradores pueden ver todos los usuarios registrados y modificar sus roles (`admin`, `teacher`, `student`).
- **Gestión de Currículo (Módulos y Lecciones)**: Se implementó un CRUD completo para gestionar el contenido del curso. Los profesores pueden crear módulos, ordenarlos y agregarles lecciones (con soporte para contenido en Markdown, enlaces a videos y adjuntar archivos PDF). Las vistas de estudiantes consumen estos datos dinámicamente desde la base de datos.
- **Gestión de Material de Apoyo**: Se habilitó una sección para que profesores y administradores puedan subir archivos PDF (teoría y ejercicios). Los estudiantes tienen acceso de solo lectura a este material.
- **Gamificación y Leaderboard**: Se implementó una tabla de clasificación (Leaderboard) donde los estudiantes pueden ver su progreso y puntaje en comparación al resto del curso, sincronizado dinámicamente con sus perfiles.

---

##  Lo que falta por pulir (Requiere Atención a Corto Plazo)

1. **Credenciales en el archivo `.env`**:
   - **OpenAI**: Para que el Tutor IA responda de verdad, es obligatorio añadir la variable `OPENAI_API_KEY` en el `.env` con una clave secreta válida de OpenAI.
   - **Google OAuth**: Para probar el inicio de sesión institucional, se requiere crear las credenciales en *Google Cloud Console* y agregarlas (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`) al `.env`.
2. **Seguridad Avanzada en la Sandbox**:
   - Aunque la ejecución en servidor tiene un *timeout* límite por proceso (evita bucles infinitos), el código se corre en el *host* nativo. Se recomienda encapsular la ejecución en un contenedor (ej. Docker, chroot o Firejail) para prevenir inyecciones maliciosas a nivel del sistema operativo.

---

##  Implementaciones a Futuro
 (Cambiar al integrar algo al sistema o agregar una nueva funcionalidad que no se encuentre en la lista)

1. **Sistema de Evaluación Automática**:
   - Crear ejercicios de programación donde la Sandbox del estudiante reciba entradas ocultas, se contraste la salida real con una salida esperada, y se otorgue una calificación o desbloqueo automático del siguiente módulo.
2. **Tutor IA Contextual**:
   - Enlazar el editor de código con el Chat de IA. Si un estudiante compila y tiene un error, enviarle automáticamente el código fallido a la IA para que el tutor ofrezca consejos de resolución más precisos sin que el estudiante deba explicarlo todo manualmente.
3. **Progreso y Gamificación**:
   - Añadir una tabla pivote en la base de datos para medir el progreso del estudiante (lecciones completadas, porcentaje del curso) y mostrar un dashboard estudiantil mucho más interactivo (barras de experiencia, medallas, etc).

---

##  Cómo iniciar el proyecto localmente

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
