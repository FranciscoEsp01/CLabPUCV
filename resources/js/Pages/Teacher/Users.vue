<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        required: true
    }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isAdmin = computed(() => currentUser.value.role === 'admin');

// Filtros y Búsqueda
const searchQuery = ref('');
const roleFilter = ref('all');

const filteredUsers = computed(() => {
    return props.users.filter(user => {
        const matchesSearch = 
            user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            user.email.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesRole = roleFilter.value === 'all' || user.role === roleFilter.value;
        
        return matchesSearch && matchesRole;
    });
});

// Estadísticas rápidas
const stats = computed(() => {
    return {
        total: props.users.length,
        students: props.users.filter(u => u.role === 'student').length,
        teachers: props.users.filter(u => u.role === 'teacher').length,
        admins: props.users.filter(u => u.role === 'admin').length,
    };
});

// Estado del Modal de Eliminación
const showDeleteModal = ref(false);
const userToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (isDeleting.value) return;
    showDeleteModal.value = false;
    userToDelete.value = null;
};

const confirmDelete = () => {
    if (!userToDelete.value || !isAdmin.value) return;

    isDeleting.value = true;
    router.delete(route('teacher.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

// Actualización de Rol
const updateRole = (userId, newRole) => {
    if (!isAdmin.value) return;

    // Evitar que el administrador se quite su propio rol de admin por accidente
    if (userId === currentUser.value.id && newRole !== 'admin') {
        if (!confirm('Advertencia de Seguridad: Estás intentando quitar tu propio rol de Administrador. Si continúas, podrías perder el acceso a las funciones administrativas. ¿Deseas continuar?')) {
            return;
        }
    }
    
    useForm({
        role: newRole
    }).patch(route('teacher.users.updateRole', userId), {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Gestión de Usuarios - CLab PUCV" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="font-bold text-xl text-gray-800 dark:text-gray-100 leading-tight flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Gestión de Usuarios y Permisos
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Administración integral de cuentas institucionales (@mail.pucv.cl), roles académicos y accesos al sistema.
                    </p>
                </div>
                <div v-if="isAdmin" class="flex items-center gap-1.5 px-3 py-1 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 rounded-full text-xs font-semibold text-red-700 dark:text-red-300">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Modo Administrador Activo
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Notificaciones Flash de Éxito / Error -->
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ $page.props.flash.success }}</p>
                        </div>
                    </div>
                </transition>

                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="$page.props.errors?.user || $page.props.errors?.role || $page.props.flash?.error" class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-xl flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-100 dark:bg-red-900/60 text-red-700 dark:text-red-300 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ $page.props.errors?.user || $page.props.errors?.role || $page.props.flash?.error }}
                            </p>
                        </div>
                    </div>
                </transition>

                <!-- Tarjetas de Métricas -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-3.5">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Usuarios</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ stats.total }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-3.5">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Estudiantes</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ stats.students }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-3.5">
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Profesores</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ stats.teachers }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-3.5">
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Administradores</p>
                            <p class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ stats.admins }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabla Principal con Controles de Búsqueda y Filtro -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700/60">
                    
                    <!-- Barra de Herramientas -->
                    <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row gap-3 items-center justify-between">
                        <div class="relative w-full sm:w-80">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar por nombre o correo institucional..."
                                class="w-full pl-9 pr-3 py-2 text-xs sm:text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <label class="text-xs text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">Filtrar por rol:</label>
                            <select 
                                v-model="roleFilter"
                                class="text-xs sm:text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-800 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="all">Todos los Roles ({{ stats.total }})</option>
                                <option value="student">Estudiantes ({{ stats.students }})</option>
                                <option value="teacher">Profesores ({{ stats.teachers }})</option>
                                <option value="admin">Administradores ({{ stats.admins }})</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla de Usuarios -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50/80 dark:bg-gray-900/60">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Correo Institucional</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol Actual</th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Asignación de Rol</th>
                                    <th v-if="isAdmin" scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-red-500 dark:text-red-400 uppercase tracking-wider">Acciones (Admin)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-750/30 transition-colors">
                                    
                                    <!-- Nombre y Avatar -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100 block">{{ user.name }}</span>
                                                <span v-if="user.id === currentUser.id" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300">
                                                    Tu Cuenta Activa
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300 font-mono text-xs">
                                        {{ user.email }}
                                    </td>

                                    <!-- Rol Actual (Badge) -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="user.role === 'admin'" class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300 border border-red-200 dark:border-red-800">
                                            🛡️ Administrador
                                        </span>
                                        <span v-else-if="user.role === 'teacher'" class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                            👨‍🏫 Profesor
                                        </span>
                                        <span v-else class="px-2.5 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            🎓 Estudiante
                                        </span>
                                    </td>

                                    <!-- Modificar Rol (Solo habilitado para Admins) -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <select 
                                            v-if="isAdmin"
                                            :value="user.role"
                                            @change="updateRole(user.id, $event.target.value)"
                                            class="block w-40 pl-3 pr-8 py-1.5 text-xs font-medium border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 rounded-lg dark:bg-gray-900 dark:text-gray-200 shadow-sm"
                                        >
                                            <option value="student">Estudiante</option>
                                            <option value="teacher">Profesor</option>
                                            <option value="admin">Administrador</option>
                                        </select>
                                        <span v-else class="text-xs text-gray-400 italic">Solo administradores</span>
                                    </td>

                                    <!-- Acciones de Eliminación (SOLO ADMINISTRADOR) -->
                                    <td v-if="isAdmin" class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Si es el propio usuario logueado, no puede autoeliminarse -->
                                            <span v-if="user.id === currentUser.id" class="text-xs text-gray-400 italic px-2 py-1 bg-gray-100 dark:bg-gray-700/50 rounded-md">
                                                En uso
                                            </span>
                                            
                                            <!-- Botón de eliminación exclusivo para Admin -->
                                            <button 
                                                v-else
                                                @click="openDeleteModal(user)"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-900/60 text-red-700 dark:text-red-300 text-xs font-semibold rounded-lg border border-red-200 dark:border-red-800 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
                                                title="Eliminar usuario permanentemente"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredUsers.length === 0">
                                    <td :colspan="isAdmin ? 5 : 4" class="px-6 py-12 text-center text-gray-400">
                                        <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-sm font-medium">No se encontraron usuarios con los criterios de búsqueda.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de Confirmación de Eliminación Permanente (Solo Admin) -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay con desenfoque de fondo -->
                <div @click="closeDeleteModal" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Contenedor del Modal -->
                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200 dark:border-gray-700">
                    <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start gap-4">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 sm:mx-0">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-gray-100" id="modal-title">
                                    ¿Eliminar usuario permanentemente?
                                </h3>
                                <div class="mt-2 space-y-2">
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-300">
                                        Estás a punto de eliminar la cuenta de <strong class="text-gray-800 dark:text-gray-100 font-semibold">{{ userToDelete?.name }}</strong> (<span class="font-mono text-xs text-indigo-600 dark:text-indigo-400">{{ userToDelete?.email }}</span>).
                                    </p>
                                    <div class="p-3 bg-red-50 dark:bg-red-950/40 rounded-lg border border-red-200 dark:border-red-800/60 text-[11px] sm:text-xs text-red-700 dark:text-red-300">
                                        ⚠️ <strong>Acción Irreversible:</strong> Se eliminarán permanentemente todas sus entregas de código en Sandbox, progreso de lecciones, historial y archivos asociados.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/60 px-6 py-3.5 sm:flex sm:flex-row-reverse gap-2 border-t border-gray-100 dark:border-gray-700">
                        <button 
                            @click="confirmDelete"
                            :disabled="isDeleting"
                            type="button" 
                            class="w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 hover:bg-red-700 text-xs sm:text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto disabled:opacity-50 transition-colors"
                        >
                            <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isDeleting ? 'Eliminando...' : 'Sí, Eliminar Usuario' }}</span>
                        </button>
                        <button 
                            @click="closeDeleteModal"
                            :disabled="isDeleting"
                            type="button" 
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto transition-colors"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
