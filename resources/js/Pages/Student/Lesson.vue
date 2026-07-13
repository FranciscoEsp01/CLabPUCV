<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { VueMonacoEditor } from '@guolao/vue-monaco-editor';
import axios from 'axios';

// Estado del contenido
const currentTab = ref('doc'); // 'doc' o 'video'

const props = defineProps({
    lesson: {
        type: Object,
        required: true
    }
});

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
    <Head title="Lección Interactiva - ANSI C" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Módulo {{ lesson.module?.order || '?' }}: {{ lesson.title }}
                </h2>
                <div class="flex space-x-2">
                    <button class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        &larr; Lección Anterior
                    </button>
                    <button class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-md dark:shadow-[0_0_10px_rgba(37,99,235,0.4)]">
                        Siguiente Lección &rarr;
                    </button>
                </div>
            </div>
        </template>

        <!-- Layout Principal: Split View -->
        <div class="flex flex-col md:flex-row h-[calc(100vh-130px)] bg-gray-100 dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            
            <!-- Left Panel: Documentación/Video -->
            <div class="w-full md:w-1/2 flex flex-col border-r border-gray-200 dark:border-gray-700 h-full relative overflow-hidden bg-white dark:bg-gray-800 shadow-xl dark:shadow-none transition-colors duration-300">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
                    <button v-if="lesson.video_url" @click="currentTab = 'video'" :class="currentTab === 'video' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200'" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Video Explicativo
                    </button>
                    <button @click="currentTab = 'doc'" :class="currentTab === 'doc' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200'" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Documentación
                    </button>
                </div>

                <!-- Contenido -->
                <div class="flex-1 overflow-y-auto p-0 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 flex flex-col">
                    
                    <!-- View: Video -->
                    <div v-show="currentTab === 'video' && lesson.video_url" class="p-6 space-y-6 animate-fade-in flex-1">
                        <div class="w-full aspect-video bg-gray-900 dark:bg-black rounded-xl border border-gray-300 dark:border-gray-700 shadow-xl overflow-hidden relative group flex justify-center items-center">
                            <a :href="lesson.video_url" target="_blank" class="text-white hover:text-blue-400 font-medium">Abrir Video Explicativo en una nueva pestaña &rarr;</a>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ lesson.title }}</h3>
                        </div>
                    </div>

                    <!-- View: Documentación -->
                    <div v-show="currentTab === 'doc'" class="h-full animate-fade-in flex flex-col">
                        <div v-if="lesson.pdf_path" class="w-full h-full flex-1">
                            <iframe :src="lesson.pdf_path" class="w-full h-full border-0"></iframe>
                        </div>
                        <div v-else class="p-6 text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none">
                            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4">{{ lesson.title }}</h1>
                            <div class="whitespace-pre-line text-lg">
                                {{ lesson.content || 'Sin contenido aún.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Sandbox Code Editor -->
            <div class="w-full md:w-1/2 flex flex-col h-full relative">
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

<style>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
