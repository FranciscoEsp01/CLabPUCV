<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    modules: {
        type: Array,
        required: true
    }
});

// Formularios para creación
const newModuleForm = useForm({
    title: '',
    description: '',
    order: 0
});

const newLessonForm = useForm({
    title: '',
    content: '',
    video_url: '',
    pdf_file: null,
    order: 0
});

const selectedModuleForLesson = ref(null);

const createModule = () => {
    newModuleForm.post(route('teacher.course.modules.store'), {
        preserveScroll: true,
        onSuccess: () => newModuleForm.reset()
    });
};

const createLesson = (moduleId) => {
    newLessonForm.post(route('teacher.course.lessons.store', moduleId), {
        preserveScroll: true,
        onSuccess: () => {
            newLessonForm.reset();
            selectedModuleForLesson.value = null;
        }
    });
};

const deleteModule = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar este módulo? Se eliminarán todas sus lecciones.')) {
        useForm({}).delete(route('teacher.course.modules.destroy', id), {
            preserveScroll: true
        });
    }
};

const deleteLesson = (id) => {
    if (confirm('¿Estás seguro de que deseas eliminar esta lección?')) {
        useForm({}).delete(route('teacher.course.lessons.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Gestión del Currículo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestión del Currículo</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Crear Módulo -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-4">Crear Nuevo Módulo</h3>
                    <form @submit.prevent="createModule" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Módulo</label>
                                <input v-model="newModuleForm.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden</label>
                                <input v-model="newModuleForm.order" type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción (Opcional)</label>
                            <textarea v-model="newModuleForm.description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" rows="2"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="newModuleForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar Módulo</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Módulos y Lecciones -->
                <div v-for="module in modules" :key="module.id" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Módulo {{ module.order }}: {{ module.title }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ module.description }}</p>
                        </div>
                        <div>
                            <button @click="selectedModuleForLesson = module.id" class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 mr-2">Añadir Lección</button>
                            <button @click="deleteModule(module.id)" class="px-3 py-1 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Eliminar Módulo</button>
                        </div>
                    </div>

                    <!-- Formulario de Lección (Visible si se seleccionó) -->
                    <div v-if="selectedModuleForLesson === module.id" class="bg-gray-50 dark:bg-gray-900 p-4 rounded-md mb-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3">Nueva Lección para el Módulo {{ module.title }}</h4>
                        <form @submit.prevent="createLesson(module.id)" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título de Lección</label>
                                    <input v-model="newLessonForm.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL del Video (Opcional)</label>
                                    <input v-model="newLessonForm.video_url" type="url" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenido (Texto/Markdown)</label>
                                <textarea v-model="newLessonForm.content" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" rows="3"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden</label>
                                    <input v-model="newLessonForm.order" type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo PDF (Opcional)</label>
                                    <input type="file" @input="newLessonForm.pdf_file = $event.target.files[0]" accept="application/pdf" class="mt-1 block w-full text-gray-700 dark:text-gray-300" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="selectedModuleForLesson = null" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                                <button type="submit" :disabled="newLessonForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar Lección</button>
                            </div>
                        </form>
                    </div>

                    <!-- Lecciones del módulo -->
                    <div class="space-y-2 pl-4">
                        <div v-for="lesson in module.lessons" :key="lesson.id" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded border border-gray-100 dark:border-gray-600">
                            <div>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ lesson.order }}. {{ lesson.title }}</span>
                                <span v-if="lesson.video_url" class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Video</span>
                            </div>
                            <button @click="deleteLesson(lesson.id)" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                        </div>
                        <div v-if="module.lessons.length === 0" class="text-sm text-gray-500 dark:text-gray-400 italic">
                            No hay lecciones en este módulo aún.
                        </div>
                    </div>
                </div>
                <div v-if="modules.length === 0" class="bg-white dark:bg-gray-800 p-6 rounded-lg text-center text-gray-500">
                    Aún no hay módulos creados.
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
