<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { VueMonacoEditor } from '@guolao/vue-monaco-editor';
import axios from 'axios';

const page = usePage();

// Plantillas de código ANSI C
const templates = {
    hello: {
        name: 'Hola Mundo',
        code: `#include <stdio.h>

int main() {
    printf("¡Hola, PUCV! Bienvenido al Sandbox de ANSI C.\\n");
    return 0;
}`,
        stdin: ''
    },
    pointers: {
        name: 'Punteros y Memoria',
        code: `#include <stdio.h>
#include <stdlib.h>

int main() {
    int n = 5;
    int *arr = (int*) malloc(n * sizeof(int));
    
    if (arr == NULL) {
        printf("Error asignando memoria.\\n");
        return 1;
    }
    
    for (int i = 0; i < n; i++) {
        arr[i] = (i + 1) * 10;
        printf("Elemento arr[%d] = %d (Direccion: %p)\\n", i, arr[i], (void*)&arr[i]);
    }
    
    free(arr);
    printf("Memoria liberada correctamente.\\n");
    return 0;
}`,
        stdin: ''
    },
    scanf: {
        name: 'Entrada Estándar (scanf)',
        code: `#include <stdio.h>

int main() {
    int a, b;
    printf("Ingrese dos numeros enteros separados por espacio:\\n");
    if (scanf("%d %d", &a, &b) == 2) {
        printf("Suma: %d + %d = %d\\n", a, b, a + b);
        printf("Producto: %d * %d = %d\\n", a, b, a * b);
    } else {
        printf("Error al leer los datos de entrada.\\n");
    }
    return 0;
}`,
        stdin: '14 28'
    },
    structs: {
        name: 'Estructuras (struct)',
        code: `#include <stdio.h>
#include <string.h>

struct Estudiante {
    char nombre[50];
    int edad;
    float promedio;
};

int main() {
    struct Estudiante est1;
    strcpy(est1.nombre, "Estudiante PUCV");
    est1.edad = 21;
    est1.promedio = 6.8;

    printf("=== Ficha de Estudiante ===\\n");
    printf("Nombre:   %s\\n", est1.nombre);
    printf("Edad:     %d anos\\n", est1.edad);
    printf("Promedio: %.1f\\n", est1.promedio);

    return 0;
}`,
        stdin: ''
    },
    recursion: {
        name: 'Recursión (Factorial)',
        code: `#include <stdio.h>

long long factorial(int n) {
    if (n <= 1) return 1;
    return n * factorial(n - 1);
}

int main() {
    int num = 10;
    printf("El factorial de %d es: %lld\\n", num, factorial(num));
    return 0;
}`,
        stdin: ''
    }
};

const selectedTemplate = ref('hello');
const code = ref(templates.hello.code);
const stdin = ref('');
const showStdin = ref(false);
const output = ref('');
const isCompiling = ref(false);
const isDarkMode = ref(true);
const editorLoaded = ref(false);
const copied = ref(false);
const executionStatus = ref(null); // 'success', 'error', 'security'

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                isDarkMode.value = document.documentElement.classList.contains('dark');
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
});

const onEditorMount = () => {
    editorLoaded.value = true;
};

const handleTemplateChange = (key) => {
    selectedTemplate.value = key;
    code.value = templates[key].code;
    if (templates[key].stdin) {
        stdin.value = templates[key].stdin;
        showStdin.value = true;
    }
    output.value = '';
    executionStatus.value = null;
};

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(code.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (e) {
        console.error('Error al copiar código:', e);
    }
};

const resetCode = () => {
    code.value = templates[selectedTemplate.value].code;
    output.value = '';
    executionStatus.value = null;
};

const runCode = async () => {
    isCompiling.value = true;
    output.value = 'Compilando y ejecutando código C en entorno seguro...';
    executionStatus.value = null;

    try {
        const headers = {};
        const jwtToken = page.props?.auth?.jwt_token;
        if (jwtToken) {
            headers['Authorization'] = `Bearer ${jwtToken}`;
        }

        const response = await axios.post(route('student.sandbox.execute'), {
            code: code.value,
            stdin: stdin.value
        }, { headers });

        output.value = response.data.output;
        
        if (response.data.output.includes('Error de compilación') || response.data.output.includes('Error de ejecución')) {
            executionStatus.value = 'error';
        } else if (response.data.output.includes('[BLOQUEO DE SEGURIDAD]')) {
            executionStatus.value = 'security';
        } else {
            executionStatus.value = 'success';
        }
    } catch (error) {
        if (error.response && error.response.data && error.response.data.output) {
            output.value = error.response.data.output;
            executionStatus.value = error.response.status === 422 ? 'security' : 'error';
        } else {
            output.value = '❌ Ocurrió un error de comunicación con el servidor. Por favor, verifica tu conexión o intenta nuevamente.';
            executionStatus.value = 'error';
        }
        console.error(error);
    } finally {
        isCompiling.value = false;
    }
};
</script>

<template>
    <Head title="Sandbox ANSI C - CLabPUCV" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="p-2 bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </span>
                        Sandbox Interactivo ANSI C
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Compilador GCC seguro con análisis estático y asistencia en tiempo real.
                    </p>
                </div>

                <!-- Template Selector & Controls -->
                <div class="flex items-center flex-wrap gap-2">
                    <div class="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-1 shadow-sm">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2">Plantilla:</span>
                        <select 
                            :value="selectedTemplate" 
                            @change="handleTemplateChange($event.target.value)"
                            class="bg-transparent border-none text-xs font-semibold text-gray-800 dark:text-gray-200 focus:ring-0 cursor-pointer py-1 pl-1 pr-6"
                        >
                            <option v-for="(tpl, key) in templates" :key="key" :value="key" class="dark:bg-gray-800">
                                {{ tpl.name }}
                            </option>
                        </select>
                    </div>

                    <button 
                        @click="showStdin = !showStdin" 
                        :class="showStdin ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border-purple-300 dark:border-purple-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700'"
                        class="px-3 py-1.5 border text-xs font-medium rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Stdin ({{ showStdin ? 'Ocultar' : 'Entrada' }})
                    </button>

                    <button 
                        @click="copyCode" 
                        class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        {{ copied ? '¡Copiado!' : 'Copiar' }}
                    </button>

                    <button 
                        @click="resetCode" 
                        class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-xl shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5"
                        title="Restaurar código a la plantilla original"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Resetear
                    </button>
                </div>
            </div>
        </template>

        <!-- Main Workspace Area -->
        <div class="h-[calc(100vh-140px)] flex flex-col bg-gray-100 dark:bg-gray-950 transition-colors duration-300 overflow-hidden">
            
            <!-- Stdin Box (Optional Collapsible) -->
            <div v-show="showStdin" class="bg-purple-50/70 dark:bg-purple-950/30 border-b border-purple-200 dark:border-purple-900/50 p-3 px-4 flex flex-col sm:flex-row sm:items-center gap-2 text-xs">
                <span class="font-semibold text-purple-900 dark:text-purple-300 flex items-center gap-1.5 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    Entrada Estándar (stdin para scanf):
                </span>
                <input 
                    type="text" 
                    v-model="stdin" 
                    placeholder="Ej: 10 25 o texto que el programa leerá con scanf/getchar" 
                    class="flex-1 bg-white dark:bg-gray-900 border border-purple-200 dark:border-purple-800 rounded-lg px-3 py-1 text-xs text-gray-900 dark:text-white font-mono focus:ring-1 focus:ring-purple-500 focus:outline-none"
                />
            </div>

            <!-- Editor & Console Split View -->
            <div class="flex-1 flex flex-col lg:flex-row min-h-0 relative">
                
                <!-- Left Section: Monaco Code Editor -->
                <div class="flex-[3] flex flex-col border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-800 min-h-0 bg-white dark:bg-gray-900 relative">
                    
                    <!-- Editor Top Bar -->
                    <div class="h-11 bg-gray-50 dark:bg-gray-900/90 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-4 shrink-0">
                        <div class="flex items-center space-x-2">
                            <div class="flex space-x-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/80"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500/80"></div>
                            </div>
                            <span class="ml-3 text-xs font-mono font-medium text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                <span class="text-blue-500 font-bold">C</span> main.c
                            </span>
                        </div>

                        <!-- Run Code Button -->
                        <button 
                            @click="runCode" 
                            :disabled="isCompiling" 
                            class="inline-flex items-center px-4 py-1.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 active:scale-95 text-white text-xs font-bold rounded-lg shadow-md hover:shadow-green-500/20 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <svg v-if="!isCompiling" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-else class="animate-spin w-3.5 h-3.5 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isCompiling ? 'Compilando...' : 'Ejecutar (GCC)' }}
                        </button>
                    </div>

                    <!-- Monaco Editor Workspace Container -->
                    <div class="flex-1 w-full h-full min-h-[300px] relative bg-white dark:bg-gray-900 overflow-hidden">
                        <vue-monaco-editor
                            v-model:value="code"
                            :theme="isDarkMode ? 'vs-dark' : 'vs'"
                            language="c"
                            :options="{
                                minimap: { enabled: false },
                                fontSize: 15,
                                fontFamily: 'JetBrains Mono, Menlo, Monaco, Consolas, Courier New, monospace',
                                padding: { top: 12, bottom: 12 },
                                automaticLayout: true,
                                scrollBeyondLastLine: false,
                                lineNumbersMinChars: 3,
                                renderLineHighlight: 'all',
                                tabSize: 4,
                            }"
                            class="h-full w-full"
                            @mount="onEditorMount"
                        >
                            <template #loading>
                                <div class="h-full w-full flex flex-col items-center justify-center bg-gray-900 text-gray-400 space-y-3">
                                    <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                                    <p class="text-xs font-mono tracking-wide">Cargando Editor Monaco C...</p>
                                </div>
                            </template>
                        </vue-monaco-editor>
                    </div>
                </div>

                <!-- Right / Bottom Section: Console Output -->
                <div class="flex-[2] flex flex-col min-h-[220px] lg:min-h-0 bg-gray-950 text-gray-200 border-t lg:border-t-0 border-gray-800 relative">
                    
                    <!-- Terminal Header -->
                    <div class="h-11 bg-gray-900/90 border-b border-gray-800 flex items-center justify-between px-4 shrink-0">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-xs font-mono font-semibold tracking-wider text-gray-300 uppercase">
                                Salida de Terminal (STDOUT / STDERR)
                            </span>
                            
                            <!-- Status Badge -->
                            <span v-if="executionStatus === 'success'" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                Éxito
                            </span>
                            <span v-else-if="executionStatus === 'error'" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500/20 text-red-400 border border-red-500/30">
                                Error
                            </span>
                            <span v-else-if="executionStatus === 'security'" class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                Bloqueo de Seguridad
                            </span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button 
                                @click="output=''" 
                                class="text-xs text-gray-400 hover:text-white transition-colors px-2 py-1 rounded hover:bg-gray-800"
                                title="Limpiar salida de la terminal"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- Terminal Output Body -->
                    <div class="flex-1 overflow-y-auto p-4 font-mono text-xs sm:text-sm whitespace-pre-wrap selection:bg-emerald-500/30 selection:text-white scrollbar-thin scrollbar-thumb-gray-800">
                        <div v-if="!output && !isCompiling" class="text-gray-600 flex flex-col items-center justify-center h-full space-y-2 select-none">
                            <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p>Presiona <span class="text-emerald-400 font-semibold">"Ejecutar (GCC)"</span> para compilar tu código C.</p>
                            <p class="text-xs text-gray-700">El resultado de la ejecución aparecerá aquí.</p>
                        </div>

                        <div v-else-if="isCompiling" class="text-blue-400 flex items-center space-x-2">
                            <div class="w-3.5 h-3.5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                            <span>{{ output }}</span>
                        </div>

                        <div v-else :class="{
                            'text-emerald-400': executionStatus === 'success',
                            'text-red-400': executionStatus === 'error',
                            'text-amber-400': executionStatus === 'security',
                            'text-gray-300': !executionStatus
                        }">
                            {{ output }}
                        </div>
                    </div>

                    <!-- Sandbox Footer Info -->
                    <div class="h-8 bg-gray-950 border-t border-gray-900 px-4 flex items-center justify-between text-[11px] text-gray-500 font-mono">
                        <div class="flex items-center space-x-3">
                            <span>Compilador: GCC 11+ (-O2 -Wall)</span>
                            <span>•</span>
                            <span>Sandbox: Aislado</span>
                        </div>
                        <div class="flex items-center space-x-1.5 text-purple-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="hidden sm:inline">Tutor IA disponible abajo a la derecha</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #374151;
    border-radius: 9999px;
}
</style>
