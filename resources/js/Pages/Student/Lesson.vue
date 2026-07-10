<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// Estado del contenido
const currentTab = ref('doc'); // 'doc' o 'video'

const props = defineProps({
    lesson: {
        type: Object,
        required: true
    }
});

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

        <!-- Layout Principal: Full Width -->
        <div class="flex flex-col h-[calc(100vh-130px)] bg-gray-100 dark:bg-gray-900 overflow-hidden transition-colors duration-300">
            
            <!-- PANEL ÚNICO: Teoría y Video -->
            <div class="w-full max-w-7xl mx-auto flex flex-col border-x border-gray-200 dark:border-gray-700 h-full relative overflow-hidden bg-white dark:bg-gray-800 shadow-xl dark:shadow-2xl transition-colors duration-300">
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

                <!-- Contenido (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                    
                    <!-- View: Video -->
                    <div v-show="currentTab === 'video' && lesson.video_url" class="space-y-6 animate-fade-in">
                        <div class="w-full aspect-video bg-gray-900 dark:bg-black rounded-xl border border-gray-300 dark:border-gray-700 shadow-xl overflow-hidden relative group flex justify-center items-center">
                            <!-- Si es una URL de youtube u otro embed, aquí se podría poner el iframe. Por ahora simulamos un video -->
                            <a :href="lesson.video_url" target="_blank" class="text-white hover:text-blue-400 font-medium">Abrir Video Explicativo en una nueva pestaña &rarr;</a>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ lesson.title }}</h3>
                        </div>
                    </div>

                    <!-- View: Documentación Markdown -->
                    <div v-show="currentTab === 'doc'" class="text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none animate-fade-in">
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4">{{ lesson.title }}</h1>
                        
                        <!-- Mostrando el contenido tal cual (podrías usar vue-markdown o v-html si viene procesado del backend) -->
                        <div class="whitespace-pre-line text-lg">
                            {{ lesson.content || 'Sin contenido aún.' }}
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
