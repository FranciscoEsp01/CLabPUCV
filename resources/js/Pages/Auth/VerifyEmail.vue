<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificación de Correo - CLab PUCV" />

        <div class="space-y-6">
            <!-- Encabezado con Icono -->
            <div class="text-center space-y-2">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Verifica tu Correo Institucional</h1>
                <p class="text-xs text-indigo-600 font-semibold uppercase tracking-wider">Pontificia Universidad Católica de Valparaíso</p>
            </div>

            <!-- Explicación -->
            <div class="text-sm text-gray-600 text-center leading-relaxed">
                ¡Gracias por registrarte en <span class="font-semibold text-gray-800">CLab PUCV</span>! Antes de comenzar a programar en el entorno seguro de C, necesitamos que confirmes tu dirección de correo institucional
                <span v-if="user?.email" class="block font-mono font-medium text-indigo-700 mt-1 bg-indigo-50/60 py-1 px-2.5 rounded-lg border border-indigo-100/60 inline-block text-xs">
                    {{ user.email }}
                </span>
                haciendo clic en el enlace que acabamos de enviarte.
            </div>

            <!-- Alerta de éxito al reenviar -->
            <div
                v-if="verificationLinkSent"
                class="rounded-xl bg-emerald-50 border border-emerald-200/80 p-4 text-sm text-emerald-800 flex items-start gap-3 shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                    <span class="font-semibold">¡Enlace enviado!</span>
                    <p class="mt-0.5 text-xs text-emerald-700">Se ha reenviado un nuevo correo de verificación a tu casilla institucional (@mail.pucv.cl). Revisa también tu carpeta de spam o correo no deseado.</p>
                </div>
            </div>

            <!-- Formulario de Reenvío y Logout -->
            <form @submit.prevent="submit" class="pt-2">
                <div class="flex flex-col gap-3">
                    <PrimaryButton
                        class="w-full justify-center py-2.5 shadow-sm text-sm font-medium tracking-wide"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Reenviando correo...' : 'Reenviar Correo de Verificación' }}</span>
                    </PrimaryButton>

                    <div class="text-center pt-2">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-xs font-medium text-gray-500 hover:text-gray-800 transition-colors underline focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded"
                        >
                            Cerrar sesión / Registrar otro correo
                        </Link>
                    </div>
                </div>
            </form>

            <!-- Nota de Seguridad y Ayuda -->
            <div class="border-t border-gray-100 pt-4 text-center">
                <p class="text-[11px] text-gray-400">
                    🔒 Por motivos de seguridad y control académico, el acceso está restringido a alumnos y docentes con correo institucional verificado. El enlace expira en 60 minutos.
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
