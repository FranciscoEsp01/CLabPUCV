<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    modules_data: {
        type: Array,
        required: true,
        default: () => []
    }
});

const page = usePage();
const points = computed(() => page.props.auth.user.points || 0);
const rank = ref('Novato C');
</script>

<template>
    <Head title="Panel de Estudiante" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Panel de Estudiante
            </h2>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-300">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Hero Section: Gamification -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl dark:shadow-2xl sm:rounded-2xl border border-gray-200 dark:border-gray-700 relative group transition-colors duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-100/50 to-purple-100/50 dark:from-blue-600/20 dark:to-purple-600/20 opacity-50 group-hover:opacity-100 transition duration-700"></div>
                    <div class="p-8 relative z-10 flex flex-col md:flex-row items-center justify-between">
                        <div>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">¡Hola, {{ page.props.auth.user.name }}!</h3>
                            <p class="text-gray-600 dark:text-gray-400">Continúa tu aprendizaje en ANSI C. Estás haciendo un gran progreso.</p>
                        </div>
                        <div class="mt-6 md:mt-0 flex items-center space-x-6">
                            <div class="text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Puntos Totales</p>
                                <p class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-500 to-blue-600 dark:from-green-400 dark:to-blue-500">{{ points }}</p>
                            </div>
                            <div class="h-12 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">Rango</p>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ rank }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Action: Sandbox -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg dark:shadow-xl relative overflow-hidden group hover:border-blue-400 dark:hover:border-blue-500 transition-colors duration-300">
                        <div class="absolute -right-16 -top-16 bg-blue-100 dark:bg-blue-500/10 w-64 h-64 rounded-full blur-3xl group-hover:bg-blue-200 dark:group-hover:bg-blue-500/20 transition-all duration-700"></div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            Zona de Práctica (Sandbox)
                        </h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-6 relative z-10">Accede al entorno de compilación en tiempo real. Resuelve retos de código y consulta al Tutor IA para mejorar tus habilidades en ANSI C.</p>
                        <Link :href="route('student.sandbox.index')" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-md dark:shadow-[0_0_15px_rgba(37,99,235,0.5)] dark:hover:shadow-[0_0_25px_rgba(37,99,235,0.7)] transition-all duration-300 transform hover:-translate-y-1">
                            Ir al Sandbox
                            <svg class="ml-2 -mr-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </Link>
                    </div>

                    <Link :href="route('student.leaderboard')" class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg dark:shadow-xl flex flex-col items-center justify-center text-center transition-all duration-300 transform hover:-translate-y-1 hover:border-blue-400 dark:hover:border-blue-500 group cursor-pointer block">
                        <div class="w-20 h-20 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 border-4 border-gray-100 dark:border-gray-600 shadow-sm transition-transform duration-300 group-hover:scale-110">
                            <svg class="w-10 h-10 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Clasificación</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Descubre en qué posición estás frente a tus compañeros.</p>
                        <span class="text-blue-600 dark:text-blue-400 font-medium transition-colors group-hover:text-blue-800 dark:group-hover:text-blue-300">
                            Ver Leaderboard &rarr;
                        </span>
                    </Link>
                </div>

                <!-- Modules Roadmap -->
                <div>
                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Tu Plan de Estudios</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="mod in modules_data" :key="mod.id" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-md hover:border-gray-300 dark:hover:border-gray-500 transition-all duration-300 transform hover:-translate-y-1">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400">
                                    Módulo {{ mod.order }}
                                </span>
                            </div>
                            <h5 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ mod.title }}</h5>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ mod.description || 'Sin descripción' }}</p>
                            
                            <div v-if="mod.lessons && mod.lessons.length > 0" class="space-y-2">
                                <h6 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Lecciones</h6>
                                <Link v-for="lesson in mod.lessons" :key="lesson.id" :href="route('student.lesson.show', lesson.id)" class="block px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 transition-colors text-sm text-gray-700 dark:text-gray-300">
                                    {{ lesson.order }}. {{ lesson.title }}
                                </Link>
                            </div>
                            <div v-else class="text-sm text-gray-500 dark:text-gray-400 italic mt-4">
                                Aún no hay lecciones
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
