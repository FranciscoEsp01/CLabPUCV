<?php

namespace App\Services\Security;

class CSourceSecurityValidator
{
    /**
     * Maximum allowed source code length in characters.
     */
    public const MAX_CODE_LENGTH = 50000;

    /**
     * Forbidden header patterns (regex).
     */
    protected static array $forbiddenHeaders = [
        'sys\/' => 'Llamadas al sistema del kernel o arquitectura de bajo nivel (<sys/...>)',
        'unistd\.h' => 'Acceso a llamadas POSIX de bajo nivel (<unistd.h>)',
        'windows\.h' => 'Acceso a la API de Windows (<windows.h>)',
        'winsock2?\.h' => 'Acceso a sockets de Windows',
        'arpa\/inet\.h' => 'Operaciones de red e Internet (<arpa/inet.h>)',
        'netinet\/in\.h' => 'Protocolos de red (<netinet/in.h>)',
        'netdb\.h' => 'Resolución DNS y sockets de red (<netdb.h>)',
        'sys\/socket\.h' => 'Acceso a sockets de red (<sys/socket.h>)',
        'sys\/types\.h' => 'Tipos de datos del kernel (<sys/types.h>)',
        'sys\/stat\.h' => 'Manipulación de permisos y estado de archivos del sistema (<sys/stat.h>)',
        'sys\/mman\.h' => 'Manipulación de memoria virtual (<sys/mman.h>)',
        'sys\/ptrace\.h' => 'Trazado y depuración de procesos (<sys/ptrace.h>)',
        'sys\/syscall\.h' => 'Invocación directa de syscalls (<sys/syscall.h>)',
        'dlfcn\.h' => 'Carga dinámica de librerías (<dlfcn.h>)',
        'fcntl\.h' => 'Control de descriptores de archivos de bajo nivel (<fcntl.h>)',
        'dirent\.h' => 'Lectura y escaneo de directorios (<dirent.h>)',
        'utmp\.h' => 'Acceso a sesiones de usuario del sistema operativo (<utmp.h>)',
        'pwd\.h' => 'Acceso a credenciales de usuarios del sistema (<pwd.h>)',
        'pty\.h' => 'Creación de pseudo-terminales (<pty.h>)',
        'signal\.h' => 'Manipulación de señales del sistema (<signal.h>)',
    ];

    /**
     * Forbidden function calls, system calls and macros.
     */
    protected static array $forbiddenFunctions = [
        // Process & OS execution
        'system' => 'Ejecución de comandos del sistema operativo (system)',
        'popen' => 'Apertura de procesos por tubería (popen)',
        'pclose' => 'Control de procesos por tubería (pclose)',
        'execl' => 'Reemplazo de imagen de proceso (execl)',
        'execlp' => 'Reemplazo de imagen de proceso (execlp)',
        'execle' => 'Reemplazo de imagen de proceso (execle)',
        'execv' => 'Reemplazo de imagen de proceso (execv)',
        'execve' => 'Reemplazo de imagen de proceso (execve)',
        'fexecve' => 'Reemplazo de imagen de proceso (fexecve)',
        'execvp' => 'Reemplazo de imagen de proceso (execvp)',
        'execvpe' => 'Reemplazo de imagen de proceso (execvpe)',
        'posix_spawn' => 'Creación y reemplazo de proceso (posix_spawn)',
        'posix_spawnp' => 'Creación y reemplazo de proceso (posix_spawnp)',
        'fork' => 'Bifurcación de procesos / Fork bomb (fork)',
        'vfork' => 'Bifurcación de procesos (vfork)',
        'clone' => 'Creación de procesos/hilos del kernel (clone)',
        'kill' => 'Envío de señales a procesos (kill)',
        'raise' => 'Emisión de señales al proceso (raise)',
        'ptrace' => 'Inspección y control de procesos (ptrace)',
        'syscall' => 'Llamada directa al kernel (syscall)',
        '__syscall' => 'Llamada directa al kernel (__syscall)',
        
        // Network functions
        'socket' => 'Creación de sockets de red (socket)',
        'connect' => 'Conexión de red saliente (connect)',
        'bind' => 'Enlace de puertos de red (bind)',
        'listen' => 'Apertura de puertos de escucha de red (listen)',
        'accept' => 'Aceptación de conexiones entrantes de red (accept)',
        'gethostbyname' => 'Resolución DNS (gethostbyname)',
        'getaddrinfo' => 'Resolución de direcciones de red (getaddrinfo)',
        
        // Filesystem alterations & permissions
        'fopen' => 'Apertura de archivos del sistema host (fopen)',
        'freopen' => 'Redirección de flujos a archivos del sistema (freopen)',
        'open' => 'Apertura de descriptores de archivos del host (open)',
        'openat' => 'Apertura relativa de descriptores del host (openat)',
        'creat' => 'Creación de archivos en el host (creat)',
        'remove' => 'Eliminación de archivos del host (remove)',
        'unlink' => 'Eliminación de enlaces/archivos (unlink)',
        'rename' => 'Renombrado de archivos en disco (rename)',
        'rmdir' => 'Eliminación de directorios (rmdir)',
        'mkdir' => 'Creación de directorios en el host (mkdir)',
        'chmod' => 'Modificación de permisos del sistema (chmod)',
        'fchmod' => 'Modificación de permisos del sistema (fchmod)',
        'chown' => 'Cambio de propietario de archivos (chown)',
        'fchown' => 'Cambio de propietario de archivos (fchown)',
        'chroot' => 'Modificación del directorio raíz (chroot)',
        'link' => 'Creación de enlaces en el sistema de archivos (link)',
        'symlink' => 'Creación de enlaces simbólicos (symlink)',
        'truncate' => 'Truncado de archivos (truncate)',
        'ftruncate' => 'Truncado de archivos (ftruncate)',

        // Memory injection & tampering
        'mmap' => 'Mapeo directo de memoria del sistema (mmap)',
        'mprotect' => 'Modificación de permisos de memoria ejecutable (mprotect)',
        'munmap' => 'Liberación de mapeo de memoria (munmap)',
        'shmget' => 'Acceso a memoria compartida (shmget)',
        'shmat' => 'Acoplamiento a memoria compartida (shmat)',
        'dlopen' => 'Carga dinámica de binarios (dlopen)',
        'dlsym' => 'Resolución dinámica de símbolos (dlsym)',

        // User & permissions
        'setuid' => 'Escalación / cambio de UID (setuid)',
        'seteuid' => 'Escalación / cambio de UID efectivo (seteuid)',
        'setgid' => 'Escalación / cambio de GID (setgid)',
        'setegid' => 'Escalación / cambio de GID efectivo (setegid)',
    ];

    /**
     * Validate the provided C source code against security policies.
     *
     * @param string $code
     * @return array{isValid: bool, safe: bool, reason: string|null, rule: string|null}
     */
    public static function validate(string $code): array
    {
        // 1. Length check
        if (mb_strlen($code) > self::MAX_CODE_LENGTH) {
            return [
                'isValid' => false,
                'safe' => false,
                'reason' => 'El código fuente excede el tamaño máximo permitido (' . self::MAX_CODE_LENGTH . ' caracteres).',
                'rule' => 'MAX_LENGTH_EXCEEDED',
            ];
        }

        // 2. Check for dangerous local path inclusions (e.g. #include "/etc/passwd" or #include "../.env")
        if (preg_match('/#\s*include\s*["<](\.\.|\/|[a-zA-Z]:\\\\)/i', $code, $matches)) {
            return [
                'isValid' => false,
                'safe' => false,
                'reason' => 'Inclusión de rutas de archivos relativas o absolutas no permitida (#include "' . $matches[1] . '...").',
                'rule' => 'PATH_TRAVERSAL_INCLUDE',
            ];
        }

        // 3. Check for inline assembly
        if (preg_match('/\b(__asm__|__asm|asm)\b/i', $code)) {
            return [
                'isValid' => false,
                'safe' => false,
                'reason' => 'El uso de instrucciones en lenguaje ensamblador (inline assembly) está estrictamente prohibido por políticas de seguridad.',
                'rule' => 'INLINE_ASSEMBLY_FORBIDDEN',
            ];
        }

        // 4. Check for preprocessor token-pasting or dynamic symbol concatenation tricks
        if (preg_match('/##/', $code)) {
            return [
                'isValid' => false,
                'safe' => false,
                'reason' => 'Uso de operadores de preprocesador de concatenación (##) no permitido por seguridad.',
                'rule' => 'TOKEN_PASTING_FORBIDDEN',
            ];
        }

        // 5. Check for sensitive absolute paths in literals
        if (preg_match('/(\/etc\/(passwd|shadow|hosts|sudoers)|\/proc\/|\/sys\/|\.env)/i', $code)) {
            return [
                'isValid' => false,
                'safe' => false,
                'reason' => 'Referencia a rutas críticas del sistema operativo detectada.',
                'rule' => 'SENSITIVE_SYSTEM_PATH_DETECTED',
            ];
        }

        // 6. Check forbidden headers
        foreach (self::$forbiddenHeaders as $headerPattern => $description) {
            if (preg_match('/#\s*include\s*<\s*' . $headerPattern . '\s*>/i', $code)) {
                return [
                    'isValid' => false,
                    'safe' => false,
                    'reason' => "La librería incluida no está permitida: {$description}.",
                    'rule' => 'FORBIDDEN_HEADER_' . strtoupper(str_replace(['.', '/', '\\'], '_', $headerPattern)),
                ];
            }
        }

        // 7. Strip comments and string literals to prevent false positives in printfs or comments
        $sanitizedCode = self::stripCommentsAndLiterals($code);

        // 8. Check forbidden function calls
        foreach (self::$forbiddenFunctions as $func => $description) {
            $pattern = '/\b' . preg_quote($func, '/') . '\s*\(/i';
            if (preg_match($pattern, $sanitizedCode)) {
                return [
                    'isValid' => false,
                    'safe' => false,
                    'reason' => "Operación o llamada a función bloqueada por seguridad: {$description}.",
                    'rule' => 'FORBIDDEN_FUNCTION_' . strtoupper($func),
                ];
            }
        }

        return [
            'isValid' => true,
            'safe' => true,
            'reason' => null,
            'rule' => null,
        ];
    }

    /**
     * Remove comments and string/char literals from C code for reliable token inspection.
     *
     * @param string $code
     * @return string
     */
    protected static function stripCommentsAndLiterals(string $code): string
    {
        $length = strlen($code);
        $result = '';
        $i = 0;

        while ($i < $length) {
            $char = $code[$i];
            $next = ($i + 1 < $length) ? $code[$i + 1] : '';

            // Single line comment //
            if ($char === '/' && $next === '/') {
                $i += 2;
                while ($i < $length && $code[$i] !== "\n") {
                    $i++;
                }
                $result .= "\n";
                continue;
            }

            // Multi line comment /* ... */
            if ($char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length) {
                    if ($code[$i] === '*' && ($i + 1 < $length) && $code[$i + 1] === '/') {
                        $i += 2;
                        break;
                    }
                    if ($code[$i] === "\n") {
                        $result .= "\n";
                    }
                    $i++;
                }
                continue;
            }

            // String literal " ... "
            if ($char === '"') {
                $i++;
                while ($i < $length) {
                    if ($code[$i] === '\\' && $i + 1 < $length) {
                        $i += 2; // skip escaped character
                        continue;
                    }
                    if ($code[$i] === '"') {
                        $i++;
                        break;
                    }
                    $i++;
                }
                $result .= ' "string_literal" ';
                continue;
            }

            // Char literal ' ... '
            if ($char === "'") {
                $i++;
                while ($i < $length) {
                    if ($code[$i] === '\\' && $i + 1 < $length) {
                        $i += 2;
                        continue;
                    }
                    if ($code[$i] === "'") {
                        $i++;
                        break;
                    }
                    $i++;
                }
                $result .= " 'c' ";
                continue;
            }

            $result .= $char;
            $i++;
        }

        return $result;
    }
}
