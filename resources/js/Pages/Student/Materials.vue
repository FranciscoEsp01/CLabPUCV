<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    materials: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <Head title="Documentación y Materiales" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Documentación y Ejercicios</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Material Disponible</h3>
                    
                    <div v-if="materials.length === 0" class="text-center text-gray-500 py-8">
                        <p>No hay documentos ni ejercicios subidos todavía.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="material in materials" :key="material.id" class="bg-white dark:bg-gray-700 rounded-lg shadow border border-gray-200 dark:border-gray-600 p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full uppercase tracking-wide" 
                                        :class="material.type === 'documentation' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                        {{ material.type === 'documentation' ? 'Documentación' : 'Ejercicio' }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ new Date(material.created_at).toLocaleDateString() }}</span>
                                </div>
                                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2 line-clamp-2" :title="material.title">{{ material.title }}</h4>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-600">
                                <a :href="'/storage/' + material.file_path" target="_blank" class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Ver PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
