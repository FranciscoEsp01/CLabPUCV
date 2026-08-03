<script setup>
import { ref, onMounted, nextTick } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const showingNavigationDropdown = ref(false);

// Dark Mode
const isDarkMode = ref(false);

onMounted(() => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    }
};

// Estado de la IA Flotante Global (Tutor C - Groq / Llama 3.3)
const aiChatOpen = ref(false);
const isAiLoading = ref(false);
const chatMessagesContainer = ref(null);
const userMessage = ref('');
const chatMessages = ref([
    { 
        role: 'ai', 
        text: '👋 ¡Hola! Soy tu **Tutor IA de ANSI C** de la PUCV. ¿Tienes dudas sobre punteros, arreglos, memoria dinámica o algún error en tu código?' 
    }
]);

const quickPrompts = [
    '¿Cómo funcionan los punteros en C?',
    '¿Cuál es la diferencia entre malloc y calloc?',
    '¿Por qué ocurre un Segmentation Fault?',
    'Explícame scanf y printf con ejemplos'
];

const scrollToBottom = async () => {
    await nextTick();
    if (chatMessagesContainer.value) {
        chatMessagesContainer.value.scrollTop = chatMessagesContainer.value.scrollHeight;
    }
};

const clearChat = () => {
    chatMessages.value = [
        { 
            role: 'ai', 
            text: '👋 ¡Conversación reiniciada! ¿En qué puedo ayudarte hoy con tu programación en C?' 
        }
    ];
};

const sendPrompt = (promptText) => {
    userMessage.value = promptText;
    sendMessage();
};

const sendMessage = async () => {
    const text = userMessage.value.trim();
    if (!text || isAiLoading.value) return;
    
    chatMessages.value.push({ role: 'user', text });
    userMessage.value = '';
    isAiLoading.value = true;
    scrollToBottom();
    
    try {
        const headers = {};
        const jwtToken = page.props?.auth?.jwt_token;
        if (jwtToken) {
            headers['Authorization'] = `Bearer ${jwtToken}`;
        }

        const response = await axios.post('/api/ai-tutor/chat', { 
            message: text,
            history: chatMessages.value.slice(-6)
        }, { headers });

        chatMessages.value.push({ 
            role: 'ai', 
            text: response.data.reply,
            provider: response.data.provider || null
        });
    } catch (error) {
        console.error(error);
        const errorMsg = error.response?.data?.reply 
            || 'Lo siento, hubo un inconveniente al conectar con el Tutor IA. Por favor, intenta de nuevo en unos segundos.';
        chatMessages.value.push({ role: 'ai', text: `⚠️ ${errorMsg}` });
    } finally {
        isAiLoading.value = false;
        scrollToBottom();
    }
};
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
            <nav
                class="border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 transition-colors duration-300"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('student.dashboard')"
                                    :active="route().current('student.dashboard')"
                                >
                                    Dashboard Estudiante
                                </NavLink>
                                <NavLink
                                    :href="route('student.materials.index')"
                                    :active="route().current('student.materials.index')"
                                >
                                    Documentación
                                </NavLink>
                                <NavLink
                                    :href="route('student.sandbox.index')"
                                    :active="route().current('student.sandbox.index')"
                                >
                                    Sandbox
                                </NavLink>
                                <!-- Link to teacher (can be gated by role later) -->
                                <NavLink
                                    v-if="$page.props.auth.user.is_teacher"
                                    :href="route('teacher.dashboard')"
                                    :active="route().current('teacher.dashboard')"
                                >
                                    Dashboard Profesor
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            
                            <!-- Theme Toggle -->
                            <button @click="toggleTheme" class="mr-4 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition-colors duration-200">
                                <svg v-if="!isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </button>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-400 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="toggleTheme"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 mr-2 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none"
                            >
                                <svg v-if="!isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </button>
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 dark:hover:text-gray-300 focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden bg-white dark:bg-gray-800"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('student.dashboard')"
                            :active="route().current('student.dashboard')"
                        >
                            Dashboard Estudiante
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('student.materials.index')"
                            :active="route().current('student.materials.index')"
                        >
                            Documentación
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('student.sandbox.index')"
                            :active="route().current('student.sandbox.index')"
                        >
                            Sandbox
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            v-if="$page.props.auth.user.is_teacher"
                            :href="route('teacher.dashboard')"
                            :active="route().current('teacher.dashboard')"
                        >
                            Dashboard Profesor
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 dark:border-gray-700 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800 dark:text-gray-200"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white dark:bg-gray-800 shadow transition-colors duration-300"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Chat AI Flotante Global (Slide Over) -->
            <div v-show="aiChatOpen" class="fixed top-[65px] right-0 bottom-0 w-80 sm:w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col shadow-2xl z-50 transform transition-transform duration-300">
                <!-- Header -->
                <div class="p-3.5 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center relative shadow-[0_0_10px_rgba(147,51,234,0.5)]">
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-400 border-2 border-white dark:border-gray-900 rounded-full animate-pulse"></span>
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-1.5">
                                <h3 class="text-gray-900 dark:text-white font-bold text-sm">Tutor IA - C</h3>
                                <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                    Groq Llama 3.3
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Asistente ANSI C · PUCV</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button 
                            @click="clearChat" 
                            title="Reiniciar chat"
                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </button>
                        <button 
                            @click="aiChatOpen = false" 
                            title="Cerrar"
                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
                
                <!-- Messages Body -->
                <div 
                    ref="chatMessagesContainer" 
                    class="flex-1 overflow-y-auto p-4 space-y-3.5 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 bg-gray-50/50 dark:bg-gray-900/50"
                >
                    <div v-for="(msg, index) in chatMessages" :key="index" class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <div 
                            class="max-w-[90%] rounded-2xl px-3.5 py-2.5 text-xs sm:text-sm shadow-sm leading-relaxed whitespace-pre-wrap font-sans" 
                            :class="msg.role === 'user' 
                                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-tr-none' 
                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-tl-none border border-gray-200/80 dark:border-gray-700/80 shadow-[0_1px_3px_rgba(0,0,0,0.05)]'"
                        >
                            {{ msg.text }}
                        </div>
                        <span v-if="msg.provider" class="text-[9px] text-gray-400 mt-1 px-1">
                            ⚡ {{ msg.provider }}
                        </span>
                    </div>

                    <!-- Typing indicator -->
                    <div v-if="isAiLoading" class="flex items-center space-x-2 text-gray-400 text-xs py-1 px-2">
                        <div class="w-2 h-2 rounded-full bg-purple-500 animate-bounce"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce [animation-delay:0.2s]"></div>
                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-bounce [animation-delay:0.4s]"></div>
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Tutor C está razonando...</span>
                    </div>

                    <!-- Prompt suggestions -->
                    <div v-if="chatMessages.length <= 1 && !isAiLoading" class="mt-4 pt-2">
                        <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">Preguntas frecuentes:</p>
                        <div class="flex flex-col space-y-1.5">
                            <button 
                                v-for="(p, i) in quickPrompts" 
                                :key="i"
                                @click="sendPrompt(p)"
                                class="text-left text-xs bg-white dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-purple-900/30 text-gray-700 dark:text-gray-300 hover:text-purple-600 dark:hover:text-purple-300 p-2 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors shadow-xs"
                            >
                                💡 {{ p }}
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Input Footer -->
                <div class="p-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg">
                    <form @submit.prevent="sendMessage" class="flex items-center space-x-2">
                        <input 
                            type="text" 
                            v-model="userMessage" 
                            :disabled="isAiLoading"
                            placeholder="Pregunta sobre C, punteros, errores..." 
                            class="flex-1 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl px-3.5 py-2 text-gray-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition-colors disabled:opacity-50" 
                        />
                        <button 
                            type="submit" 
                            :disabled="isAiLoading || !userMessage.trim()"
                            class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 disabled:opacity-50 text-white rounded-xl p-2.5 transition-all shadow-md hover:shadow-purple-500/25 flex items-center justify-center"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Botón flotante (FAB) para abrir el Tutor IA cuando está cerrado -->
            <button 
                v-show="!aiChatOpen" 
                @click="aiChatOpen = true" 
                title="Abrir Tutor IA"
                class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-tr from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 rounded-full flex items-center justify-center shadow-[0_4px_20px_rgba(147,51,234,0.4)] z-40 transition-transform transform hover:scale-110 active:scale-95"
            >
                <div class="relative">
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-emerald-400 border-2 border-white dark:border-gray-900 rounded-full animate-ping"></span>
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
            </button>
        </div>
    </div>
</template>

<style>
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #4B5563;
    border-radius: 20px;
}
</style>
