<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { VueMonacoEditor } from '@guolao/vue-monaco-editor';
import axios from 'axios';

const code = ref('#include <stdio.h>\n\nint main() {\n    printf("¡Hola, PUCV!\\n");\n    return 0;\n}');
const output = ref('');
const isCompiling = ref(false);
const isDarkMode = ref(true);

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

const runCode = async () => {
    isCompiling.value = true;
    output.value = 'Compilando y ejecutando...';
    
    try {
        const response = await axios.post(route('student.sandbox.execute'), {
            code: code.value
        });
        output.value = response.data.output;
    } catch (error) {
        output.value = 'Ocurrió un error de comunicación con el servidor.';
        console.error(error);
    } finally {
        isCompiling.value = false;
    }
};
</script>

<template>
    <Head title="Sandbox ANSI C" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sandbox y Tutor IA
            </h2>
        </template>

        <div class="flex flex-col md:flex-row h-[calc(100vh-130px)] bg-gray-50 dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            
            <!-- Left Panel: Code Editor & Console -->
            <div class="flex-1 flex flex-col border-r border-gray-200 dark:border-gray-700 h-full relative">
                <!-- Toolbar -->
                <div class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 transition-colors duration-300">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="ml-4 text-sm text-gray-600 dark:text-gray-400 font-mono">main.c</span>
                    </div>
                    <button @click="runCode" :disabled="isCompiling" class="flex items-center px-4 py-1.5 bg-green-600 hover:bg-green-500 text-white text-sm font-semibold rounded shadow-md dark:shadow-[0_0_10px_rgba(22,163,74,0.4)] transition-colors disabled:opacity-50">
                        <svg v-if="!isCompiling" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <svg v-else class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ isCompiling ? 'Ejecutando...' : 'Ejecutar Código' }}
                    </button>
                </div>
                
                <!-- Monaco Editor -->
                <div class="flex-1 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
                    <vue-monaco-editor
                        v-model:value="code"
                        :theme="isDarkMode ? 'vs-dark' : 'vs'"
                        language="c"
                        :options="{ minimap: { enabled: false }, fontSize: 16, fontFamily: 'JetBrains Mono, monospace', padding: { top: 16 } }"
                        class="h-full w-full"
                    />
                </div>

                <!-- Console Output -->
                <div class="h-1/3 bg-gray-50 dark:bg-black flex flex-col transition-colors duration-300">
                    <div class="bg-gray-200 dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-400 uppercase tracking-widest py-1 px-4 border-b border-gray-300 dark:border-gray-700 flex justify-between transition-colors duration-300">
                        <span>Terminal Output</span>
                        <button @click="output=''" class="hover:text-gray-900 dark:hover:text-white">Clear</button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 font-mono text-sm text-green-700 dark:text-green-400 whitespace-pre-wrap">
                        {{ output }}
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
