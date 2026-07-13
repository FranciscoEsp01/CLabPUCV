<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    topStudents: {
        type: Array,
        required: true,
        default: () => [],
    }
});

const getTrophyColor = (index) => {
    switch(index) {
        case 0: return 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20'; // Gold
        case 1: return 'text-gray-400 bg-gray-400/10 border-gray-400/20'; // Silver
        case 2: return 'text-amber-600 bg-amber-600/10 border-amber-600/20'; // Bronze
        default: return 'text-gray-500 bg-gray-100 border-gray-200';
    }
};

const getAvatarUrl = (avatar, name) => {
    if (avatar) return avatar;
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff&size=128`;
};
</script>

<template>
    <Head title="Tabla de Posiciones" />

    <AuthenticatedLayout>
        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header Section -->
                <div class="flex flex-col items-center justify-center space-y-4 mb-12">
                    <div class="h-20 w-20 bg-black text-white flex items-center justify-center rounded-2xl shadow-xl transform rotate-3 hover:rotate-0 transition-transform duration-300">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight text-center">
                        Tabla de Posiciones
                    </h1>
                    <p class="text-gray-500 text-center max-w-lg">
                        Los mejores estudiantes del curso ordenados por su puntaje acumulado. ¡Sigue aprendiendo para subir de posición!
                    </p>
                </div>

                <!-- Leaderboard List -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <ul class="divide-y divide-gray-50">
                        <li 
                            v-for="(student, index) in topStudents" 
                            :key="student.id"
                            class="group flex items-center justify-between p-6 hover:bg-gray-50 transition-colors duration-200"
                        >
                            <div class="flex items-center space-x-6">
                                <!-- Rank Position -->
                                <div 
                                    class="flex items-center justify-center w-12 h-12 rounded-full border-2 font-bold text-lg transition-transform duration-300 group-hover:scale-110"
                                    :class="getTrophyColor(index)"
                                >
                                    {{ index + 1 }}
                                </div>
                                
                                <!-- User Avatar -->
                                <div class="relative">
                                    <img :src="getAvatarUrl(student.avatar, student.name)" :alt="student.name" class="w-14 h-14 rounded-full object-cover shadow-sm ring-2 ring-white">
                                    <div v-if="index < 3" class="absolute -top-1 -right-1 w-5 h-5 bg-black rounded-full flex items-center justify-center border-2 border-white">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    </div>
                                </div>

                                <!-- User Info -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 group-hover:text-black transition-colors">{{ student.name }}</h3>
                                    <p class="text-sm text-gray-500 font-medium" v-if="student.id === $page.props.auth.user.id">Este eres tú</p>
                                </div>
                            </div>
                            
                            <!-- Points -->
                            <div class="flex flex-col items-end">
                                <span class="text-2xl font-black text-gray-900 tracking-tighter">{{ student.points }}</span>
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Puntos</span>
                            </div>
                        </li>
                        
                        <li v-if="!topStudents || topStudents.length === 0" class="p-12 text-center text-gray-500">
                            Aún no hay estudiantes en la tabla de posiciones.
                        </li>
                    </ul>
                </div>
                
            </div>
        </div>
    </AuthenticatedLayout>
</template>
