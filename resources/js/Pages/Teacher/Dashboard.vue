<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        required: true
    },
    recent_students: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <Head title="Panel del Profesor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Panel del Profesor</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Welcome Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Bienvenido, Profesor</h3>
                            <p class="text-gray-600 dark:text-gray-400">Desde aquí puedes gestionar el contenido, ver el progreso de los estudiantes y configurar los módulos de ANSI C.</p>
                        </div>
                    </div>
                </div>

                <!-- Stats / Quick Links -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Módulos Activos</h4>
                        <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">{{ stats.active_courses }}</p>
                        <p class="text-sm text-gray-500 mt-2 hover:text-blue-500 cursor-pointer transition-colors">Gestionar currículo &rarr;</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Estudiantes Registrados</h4>
                        <p class="text-4xl font-bold text-green-600 dark:text-green-400">{{ stats.total_students }}</p>
                        <p class="text-sm text-gray-500 mt-2 hover:text-green-500 cursor-pointer transition-colors">Ver lista completa &rarr;</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                        <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Revisiones Pendientes</h4>
                        <p class="text-4xl font-bold text-purple-600 dark:text-purple-400">{{ stats.pending_reviews }}</p>
                        <p class="text-sm text-gray-500 mt-2 hover:text-purple-500 cursor-pointer transition-colors">Revisar envíos &rarr;</p>
                    </div>
                </div>

                <!-- Main Management Area -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Estudiantes Registrados Recientemente</h3>
                    <div class="space-y-4">
                        <div v-for="student in recent_students" :key="student.id" class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ student.name }} <span class="text-sm font-normal text-gray-500">({{ student.email }})</span></p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Se unió a la plataforma</p>
                            </div>
                            <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded-full">Reciente</span>
                        </div>
                        <div v-if="recent_students.length === 0" class="text-gray-500 dark:text-gray-400 text-sm py-4">
                            No hay estudiantes registrados recientemente.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
