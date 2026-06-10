<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gray-50 text-gray-900 font-sans">
        <!-- Navbar -->
        <nav class="flex items-center justify-between p-6 px-10 bg-white border-b border-gray-100 shadow-sm">
            <div class="text-2xl font-bold text-red-600">Latinpyme</div>
            <div class="space-x-4 text-sm font-medium">
                <template v-if="!$page.props.auth.user">
                    <Link :href="route('login')" class="px-5 py-2.5 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition">Log in</Link>
                    <Link v-if="canRegister" :href="route('register')" class="px-5 py-2.5 text-white bg-red-600 rounded-xl hover:bg-red-700 transition">Registro</Link>
                </template>
                <Link v-else :href="route('dashboard')" class="px-5 py-2.5 text-white bg-red-600 rounded-xl hover:bg-red-700 transition">Dashboard</Link>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-20">
            <div class="max-w-4xl mx-auto px-6 text-center">
                <h1 class="text-5xl font-extrabold tracking-tight text-gray-950 mb-6">
                    Bienvenido a <span class="text-red-600">Latinpyme</span>
                </h1>
                <p class="text-xl text-gray-600 mb-10">Accede a tu cuenta para gestionar tus correos o regístrate para comenzar.</p>

                <!-- Tarjeta de Acceso -->
                <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-lg max-w-sm mx-auto space-y-4">
                    <Link :href="route('login')" class="block w-full py-4 text-center text-xl font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 transition">
                        Iniciar Sesión
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="block w-full py-4 text-center text-xl font-semibold text-red-600 bg-red-50 rounded-xl hover:bg-red-100 transition border border-red-200">
                        Crear una cuenta
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>
