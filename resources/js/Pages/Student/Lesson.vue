<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Estado del contenido
const currentTab = ref('doc'); // 'doc' o 'video'

</script>

<template>
    <Head title="Lección Interactiva - ANSI C" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Módulo 1: Introducción a Variables en C
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

        <!-- Layout Principal: Full Width -->
        <div class="flex flex-col h-[calc(100vh-130px)] bg-gray-100 dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            
            <!-- PANEL ÚNICO: Teoría y Video -->
            <div class="w-full max-w-7xl mx-auto flex flex-col border-x border-gray-200 dark:border-gray-700 h-full relative overflow-hidden bg-white dark:bg-gray-800 shadow-xl dark:shadow-2xl transition-colors duration-300">
                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
                    <button @click="currentTab = 'video'" :class="currentTab === 'video' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200'" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Video Explicativo
                    </button>
                    <button @click="currentTab = 'doc'" :class="currentTab === 'doc' ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400 bg-white dark:bg-gray-800' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200'" class="flex-1 py-3 text-sm font-bold uppercase tracking-wider transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Documentación
                    </button>
                </div>

                <!-- Contenido (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    
                    <!-- View: Video -->
                    <div v-show="currentTab === 'video'" class="space-y-6 animate-fade-in">
                        <div class="w-full aspect-video bg-gray-900 dark:bg-black rounded-xl border border-gray-300 dark:border-gray-700 shadow-xl overflow-hidden relative group">
                            <!-- Simulación de Video -->
                            <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 group-hover:bg-white dark:group-hover:bg-gray-800 transition-colors cursor-pointer">
                                <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center shadow-lg dark:shadow-[0_0_30px_rgba(37,99,235,0.6)] transform group-hover:scale-110 transition-all">
                                    <svg class="w-10 h-10 text-white ml-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Variables y Tipos de Datos</h3>
                            <p class="text-gray-600 dark:text-gray-400">En este video aprenderás cómo la memoria de la computadora almacena información y cómo puedes usar `int`, `float`, `char` y otras estructuras en C para reservar esos bloques de memoria.</p>
                        </div>
                    </div>

                    <!-- View: Documentación Markdown -->
                    <div v-show="currentTab === 'doc'" class="text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none animate-fade-in">
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4">Declaración de Variables en C</h1>
                        <p class="text-lg">Una variable es un espacio de memoria reservado para almacenar un valor que puede cambiar durante la ejecución del programa.</p>
                        
                        <div class="bg-gray-50 dark:bg-gray-900 border-l-4 border-blue-500 p-4 rounded-r-lg my-6">
                            <h4 class="text-blue-600 dark:text-blue-400 font-bold m-0 mb-1">Sintaxis Fundamental</h4>
                            <code class="text-sm font-mono text-gray-800 dark:text-gray-200">tipo_de_dato nombre_variable = valor_inicial;</code>
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-3">Tipos de Datos Primitivos</h3>
                        <ul class="space-y-2 list-disc pl-5">
                            <li><strong class="text-blue-600 dark:text-blue-300">int:</strong> Almacena números enteros (ej. 1, -5, 42).</li>
                            <li><strong class="text-blue-600 dark:text-blue-300">float:</strong> Almacena números con decimales de precisión simple.</li>
                            <li><strong class="text-blue-600 dark:text-blue-300">double:</strong> Almacena decimales de doble precisión.</li>
                            <li><strong class="text-blue-600 dark:text-blue-300">char:</strong> Almacena un solo carácter (ej. 'A', 'z').</li>
                        </ul>

                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mt-8 mb-3">Ejemplo Práctico</h3>
                        <p>Para imprimir una variable entera, usamos la función <code>printf</code> junto con el formateador <code>%d</code>.</p>
                        <div class="bg-gray-100 dark:bg-black p-4 rounded-lg my-4 font-mono text-sm border border-gray-200 dark:border-gray-700 shadow-inner">
                            <span class="text-purple-600 dark:text-purple-400">int</span> <span class="text-blue-600 dark:text-blue-300">edad</span> = <span class="text-orange-600 dark:text-orange-400">20</span>;<br>
                            <span class="text-yellow-600 dark:text-yellow-200">printf</span>(<span class="text-green-600 dark:text-green-400">"Tengo %d años\n"</span>, edad);
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Estilos extra para animaciones sutiles y scrollbars */
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
