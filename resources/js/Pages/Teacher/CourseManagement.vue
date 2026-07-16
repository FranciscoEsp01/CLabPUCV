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
    order: 0,
    is_visible: true,
    available_from: ''
});

const newLessonForm = useForm({
    title: '',
    content: '',
    video_url: '',
    pdf_file: null,
    order: 0
});

const selectedModuleForLesson = ref(null);
const editingLessonId = ref(null);

const editLessonForm = useForm({
    title: '',
    content: '',
    video_url: '',
    pdf_file: null,
    order: 0,
    _method: 'put' // Para que Laravel entienda la actualización con archivos
});

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

const editingModuleId = ref(null);
const editModuleForm = useForm({
    title: '',
    description: '',
    order: 0,
    is_visible: true,
    available_from: ''
});

const startEditingModule = (module) => {
    editingModuleId.value = module.id;
    editModuleForm.title = module.title;
    editModuleForm.description = module.description || '';
    editModuleForm.order = module.order;
    editModuleForm.is_visible = module.is_visible;
    editModuleForm.available_from = module.available_from ? new Date(module.available_from).toISOString().slice(0, 16) : '';
};

const cancelEditingModule = () => {
    editingModuleId.value = null;
    editModuleForm.reset();
};

const updateModule = (moduleId) => {
    editModuleForm.put(route('teacher.course.modules.update', moduleId), {
        preserveScroll: true,
        onSuccess: () => {
            editingModuleId.value = null;
            editModuleForm.reset();
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

const startEditingLesson = (lesson) => {
    editingLessonId.value = lesson.id;
    editLessonForm.title = lesson.title;
    editLessonForm.content = lesson.content || '';
    editLessonForm.video_url = lesson.video_url || '';
    editLessonForm.order = lesson.order;
    editLessonForm.pdf_file = null; 
};

const cancelEditingLesson = () => {
    editingLessonId.value = null;
    editLessonForm.reset();
};

const updateLesson = (lessonId) => {
    editLessonForm.post(route('teacher.course.lessons.update', lessonId), {
        preserveScroll: true,
        onSuccess: () => {
            editingLessonId.value = null;
            editLessonForm.reset();
        }
    });
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center h-full pt-6">
                                <input id="new-mod-visible" v-model="newModuleForm.is_visible" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2" />
                                <label for="new-mod-visible" class="text-sm text-gray-700 dark:text-gray-300">Visible para estudiantes</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Disponible desde (Opcional)</label>
                                <input v-model="newModuleForm.available_from" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" :disabled="newModuleForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar Módulo</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Módulos y Lecciones -->
                <div v-for="module in modules" :key="module.id" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <!-- Vista normal de módulo -->
                    <div v-if="editingModuleId !== module.id" class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
                        <div>
                            <div class="flex items-center">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200">Módulo {{ module.order }}: {{ module.title }}</h3>
                                <span v-if="!module.is_visible" class="ml-2 px-2 py-0.5 rounded text-xs bg-red-100 text-red-800 font-bold">Oculto</span>
                                <span v-if="module.available_from && new Date(module.available_from) > new Date()" class="ml-2 px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800 font-bold">Programado</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ module.description }}</p>
                        </div>
                        <div>
                            <button @click="startEditingModule(module)" class="px-3 py-1 bg-blue-500 text-white text-sm rounded-md hover:bg-blue-600 mr-2">Editar Módulo</button>
                            <button @click="selectedModuleForLesson = module.id" class="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 mr-2">Añadir Lección</button>
                            <button @click="deleteModule(module.id)" class="px-3 py-1 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">Eliminar Módulo</button>
                        </div>
                    </div>

                    <!-- Edición de módulo -->
                    <div v-else class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-4 bg-gray-50 dark:bg-gray-900 p-4 rounded-md">
                        <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-3">Editar Módulo</h4>
                        <form @submit.prevent="updateModule(module.id)" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título del Módulo</label>
                                    <input v-model="editModuleForm.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden</label>
                                    <input v-model="editModuleForm.order" type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción (Opcional)</label>
                                <textarea v-model="editModuleForm.description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" rows="2"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-center h-full pt-6">
                                    <input :id="'edit-mod-visible-' + module.id" v-model="editModuleForm.is_visible" type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 mr-2" />
                                    <label :for="'edit-mod-visible-' + module.id" class="text-sm text-gray-700 dark:text-gray-300">Visible para estudiantes</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Disponible desde (Opcional)</label>
                                    <input v-model="editModuleForm.available_from" type="datetime-local" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="cancelEditingModule" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
                                <button type="submit" :disabled="editModuleForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Guardar Cambios</button>
                            </div>
                        </form>
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
                        <div v-for="lesson in module.lessons" :key="lesson.id" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded border border-gray-100 dark:border-gray-600">
                            
                            <!-- Vista normal -->
                            <div v-if="editingLessonId !== lesson.id" class="flex justify-between items-center">
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ lesson.order }}. {{ lesson.title }}</span>
                                    <span v-if="lesson.video_url" class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Video</span>
                                    <span v-if="lesson.pdf_path" class="ml-2 text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded-full">PDF</span>
                                </div>
                                <div class="space-x-2 flex">
                                    <button @click="startEditingLesson(lesson)" class="text-blue-500 hover:text-blue-700 text-sm">Editar</button>
                                    <button @click="deleteLesson(lesson.id)" class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                                </div>
                            </div>
                            
                            <!-- Formulario de Edición -->
                            <div v-else class="space-y-4 pt-2">
                                <form @submit.prevent="updateLesson(lesson.id)">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título de Lección</label>
                                            <input v-model="editLessonForm.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL del Video (Opcional)</label>
                                            <input v-model="editLessonForm.video_url" type="url" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" />
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenido (Texto/Markdown)</label>
                                        <textarea v-model="editLessonForm.content" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" rows="3"></textarea>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden</label>
                                            <input v-model="editLessonForm.order" type="number" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Archivo PDF (Sobrescribir actual)</label>
                                            <input type="file" @input="editLessonForm.pdf_file = $event.target.files[0]" accept="application/pdf" class="mt-1 block w-full text-gray-700 dark:text-gray-300" />
                                            <p v-if="lesson.pdf_path" class="text-xs text-gray-500 mt-1">Actualmente tiene un PDF asignado.</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" @click="cancelEditingLesson" class="px-3 py-1 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 text-sm">Cancelar</button>
                                        <button type="submit" :disabled="editLessonForm.processing" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
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
