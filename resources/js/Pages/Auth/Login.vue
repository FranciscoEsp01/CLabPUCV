<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <TextInput
                    id="email"
                    type="email"
                    class="block w-full border-0 border-b border-gray-300 focus:border-black focus:ring-0 px-0 py-2 placeholder-gray-400 bg-transparent"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Email"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <TextInput
                    id="password"
                    type="password"
                    class="block w-full border-0 border-b border-gray-300 focus:border-black focus:ring-0 px-0 py-2 placeholder-gray-400 bg-transparent"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-6">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="border-gray-300 rounded-sm text-black focus:ring-black" />
                    <span class="ms-2 text-sm text-gray-500">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-500 hover:text-black hover:underline focus:outline-none"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="mt-8">
                <button
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-black hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </button>
            </div>
            
            <div class="mt-8 flex items-center justify-center space-x-4">
                <div class="flex-1 border-t border-gray-200"></div>
                <span class="text-xs text-gray-400 uppercase tracking-wider">O ingresa con</span>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>

            <div class="mt-8">
                <a
                    :href="route('google.login')"
                    class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black transition-colors items-center"
                >
                    <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        <path d="M1 1h22v22H1z" fill="none"/>
                    </svg>
                    Google
                </a>
            </div>
        </form>
    </GuestLayout>
</template>
