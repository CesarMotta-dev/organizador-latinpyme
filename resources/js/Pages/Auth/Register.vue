<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'admin',
    });

    const selectedRole = ref('admin');

    const setRole = (role) => {
        selectedRole.value = role;
        form.role = role;
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Registrarse" />

        <div class="min-h-screen flex items-center justify-center bg-red-600 px-4 py-12">
            <div class="w-full max-w-5xl grid gap-8 lg:grid-cols-[1fr_1.3fr]">
                <div class="rounded-[2.5rem] bg-white p-12 shadow-2xl ring-1 ring-red-200/70">
                    <div class="mb-8">
                        <span class="inline-flex items-center rounded-full bg-red-600 px-4 py-1 text-sm font-semibold text-white">Latinpyme</span>
                    </div>
                    <h1 class="text-4xl font-bold text-slate-900 tracking-tight">Crea tu cuenta empresarial.</h1>
                    <p class="mt-5 max-w-xl text-slate-700 leading-8">
                        Regístrate con tu correo corporativo y empieza a controlar reglas, bandejas y notificaciones en un mismo lugar.
                    </p>
                    <div class="mt-10 space-y-4 rounded-3xl border border-red-100 bg-red-50 p-6">
                        <p class="text-sm uppercase tracking-[0.2em] text-red-700">Por qué usarlo</p>
                        <ul class="mt-4 space-y-3 text-slate-700 text-sm">
                            <li>• Gestiona múltiples cuentas y empresas.</li>
                            <li>• Configura reglas automáticas por buzón.</li>
                            <li>• Navegación clara y enfocada.</li>
                        </ul>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-10 shadow-2xl ring-1 ring-red-200/50">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-slate-900">Registro</h2>
                        <p class="mt-3 text-sm text-slate-500">Crea tu cuenta y elige tu tipo de acceso.</p>
                    </div>

                    <!-- Selector de Rol -->
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-slate-700 mb-3">¿Qué tipo de cuenta necesitas?</p>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Tarjeta Admin -->
                            <button
                                type="button"
                                @click="setRole('admin')"
                                :class="[
                                    'flex flex-col items-center gap-2 rounded-2xl border-2 p-4 transition-all duration-200 text-center',
                                    selectedRole === 'admin'
                                        ? 'border-red-500 bg-red-50 shadow-md'
                                        : 'border-slate-200 bg-white hover:border-red-300'
                                ]"
                            >
                                <span class="text-2xl">👔</span>
                                <span class="text-sm font-bold text-slate-900">Administrador</span>
                                <span class="text-xs text-slate-500 leading-tight">Gestiona cuentas y asigna accesos a colaboradores</span>
                            </button>
                            <!-- Tarjeta Colaborador -->
                            <button
                                type="button"
                                @click="setRole('worker')"
                                :class="[
                                    'flex flex-col items-center gap-2 rounded-2xl border-2 p-4 transition-all duration-200 text-center',
                                    selectedRole === 'worker'
                                        ? 'border-red-500 bg-red-50 shadow-md'
                                        : 'border-slate-200 bg-white hover:border-red-300'
                                ]"
                            >
                                <span class="text-2xl">🧑‍💻</span>
                                <span class="text-sm font-bold text-slate-900">Colaborador</span>
                                <span class="text-xs text-slate-500 leading-tight">Accede a tu formulario de reglas asignado</span>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nombre completo" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-3 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500"
                                v-model="form.name"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="email" value="Correo empresarial" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-3 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500"
                                v-model="form.email"
                                required
                                autocomplete="username"
                            />
                            <p class="mt-2 text-sm text-slate-500">
                                Usa tu correo oficial, por ejemplo <code>@latinpymes.com</code> o <code>@latinpyme.com</code>.
                            </p>
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
                                autocomplete="new-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirmar contraseña" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="mt-3 block w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-red-500 focus:ring-red-500"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                            />
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <Link
                                :href="route('login')"
                                class="text-sm font-medium text-red-600 underline transition hover:text-red-800"
                            >
                                Ya tengo cuenta
                            </Link>

                            <PrimaryButton
                                class="w-full sm:w-auto bg-red-600 hover:bg-red-700"
                                :class="{ 'opacity-75': form.processing }"
                                :disabled="form.processing"
                            >
                                Registrarse
                            </PrimaryButton>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
