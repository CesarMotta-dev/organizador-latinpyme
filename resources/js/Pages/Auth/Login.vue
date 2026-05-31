<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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
    role_type: 'admin', // default a admin
});

const activeTab = ref('admin'); // 'admin' | 'worker'

const setTab = (tab) => {
    activeTab.value = tab;
    form.role_type = tab;
    form.clearErrors();
};

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Iniciar sesión" />

        <div class="min-h-screen flex items-center justify-center bg-red-600 px-4 py-12">
            <div class="w-full max-w-5xl grid gap-8 lg:grid-cols-[1.3fr_1fr]">
                <div class="hidden lg:flex flex-col justify-center rounded-[2.5rem] bg-white shadow-2xl p-12 ring-1 ring-red-200/70">
                    <div class="mb-8">
                        <span class="inline-flex items-center rounded-full bg-red-600 px-4 py-1 text-sm font-semibold text-white">Latinpyme</span>
                    </div>
                    <h1 class="text-4xl font-bold text-slate-900 tracking-tight">Gestiona el correo corporativo con claridad.</h1>
                    <p class="mt-5 max-w-xl text-slate-700 leading-8">
                        Configura reglas, organiza buzones y automatiza notificaciones desde una plataforma creada para tu empresa.
                    </p>
                    <div class="mt-10 space-y-4 rounded-3xl border border-red-100 bg-red-50 p-6">
                        <p class="text-sm uppercase tracking-[0.2em] text-red-700">Ventajas</p>
                        <ul class="mt-4 space-y-3 text-slate-700 text-sm">
                            <li>• Reglas precisas por empresa y cuenta.</li>
                            <li>• Acceso seguro con correo corporativo.</li>
                            <li>• Panel limpio pensado para equipos.</li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-10 shadow-2xl ring-1 ring-red-200/50">
                    <div class="mb-6">
                        <div class="flex rounded-xl bg-slate-100 p-1">
                            <button
                                type="button"
                                @click="setTab('admin')"
                                :class="[
                                    'flex-1 rounded-lg py-2.5 text-sm font-semibold transition-all duration-200',
                                    activeTab === 'admin' 
                                        ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' 
                                        : 'text-slate-500 hover:text-slate-700'
                                ]"
                            >
                                Ingreso Administrador
                            </button>
                            <button
                                type="button"
                                @click="setTab('worker')"
                                :class="[
                                    'flex-1 rounded-lg py-2.5 text-sm font-semibold transition-all duration-200',
                                    activeTab === 'worker' 
                                        ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' 
                                        : 'text-slate-500 hover:text-slate-700'
                                ]"
                            >
                                Ingreso Colaborador
                            </button>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-slate-900">
                            {{ activeTab === 'admin' ? 'Panel de Administración' : 'Panel de Colaboradores' }}
                        </h2>
                        <p class="mt-3 text-sm text-slate-500">
                            {{ activeTab === 'admin' 
                                ? 'Accede para gestionar cuentas corporativas y asignar accesos.' 
                                : 'Accede para clasificar tus correos y ver el formulario de reglas.' 
                            }}
                        </p>
                    </div>

                    <div v-if="status" class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="email" value="Correo electrónico" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-3 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Contraseña" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-3 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                Recuérdame
                            </label>

                            <div>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-medium text-red-600 underline transition hover:text-red-800"
                                >
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <PrimaryButton
                                type="submit"
                                class="w-full sm:w-auto bg-red-600 hover:bg-red-700"
                                :class="{ 'opacity-75': form.processing }"
                                :disabled="form.processing"
                            >
                                Entrar
                            </PrimaryButton>
                            <Link
                                :href="route('register')"
                                class="text-sm font-medium text-red-600 underline transition hover:text-red-800"
                            >
                                ¿No tienes cuenta? Regístrate
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
