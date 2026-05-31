<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

const props = defineProps({
    correoEmpresarial: {
        type: Object,
        required: true
    },
    workers: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    empresa: props.correoEmpresarial.empresa || '',
    email: props.correoEmpresarial.email,
    nombre: props.correoEmpresarial.nombre,
    descripcion: props.correoEmpresarial.descripcion || '',
    service_account_key: typeof props.correoEmpresarial.service_account_key === 'object'
        ? JSON.stringify(props.correoEmpresarial.service_account_key, null, 2)
        : props.correoEmpresarial.service_account_key || '',
    estado: props.correoEmpresarial.estado,
    worker_id: props.correoEmpresarial.worker_id || null,
});

const submitted = ref(false);

const handleSubmit = () => {
    submitted.value = true;
    form.put(route('company-emails.update', props.correoEmpresarial.id), {
        onSuccess: () => {
            submitted.value = false;
        }
    });
};

const estadoOptions = [
    { label: 'Activo', value: 'Activo' },
    { label: 'Inactivo', value: 'Inactivo' }
];
</script>

<template>
    <Head title="Editar Correo Empresarial" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="bg-gray-400 p-2 rounded-xl shadow-lg border border-blue-400/30 flex items-center justify-center min-w-[60px]">
                    <img
                        src="/images/logo.png"
                        alt="Logo Latinpyme"
                        class="h-8 w-auto object-contain"
                    />
                </div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Editar Correo Empresarial
                </h2>
            </div>
        </template>

        <div class="py-12 bg-[#FAFAFA] min-h-[calc(100vh-73px)]">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">

                <!-- Contenedor estilo Modal -->
                <div class="bg-[#EBF1F6] rounded shadow-md border border-[#CDDEEE] overflow-hidden">
                    <div class="px-5 py-3 border-b border-[#CDDEEE] flex justify-between items-center bg-[#EBF1F6]">
                        <h3 class="text-sm font-semibold text-gray-700">Editar Correo Empresarial</h3>
                        <Link :href="route('company-emails.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </Link>
                    </div>

                    <div class="p-6">
                        <form @submit.prevent="handleSubmit" class="space-y-4">

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Empresa <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    v-model="form.empresa"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                    :class="{ 'p-invalid': form.errors.empresa }"
                                />
                                <p v-if="form.errors.empresa" class="text-red-500 text-xs mt-1">{{ form.errors.empresa }}</p>
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Correo Empresarial <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    v-model="form.email"
                                    type="email"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                    :class="{ 'p-invalid': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    v-model="form.nombre"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                    :class="{ 'p-invalid': form.errors.nombre }"
                                />
                                <p v-if="form.errors.nombre" class="text-red-500 text-xs mt-1">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Descripción
                                </label>
                                <Textarea
                                    v-model="form.descripcion"
                                    rows="2"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                />
                            </div>

                            <div class="rounded bg-blue-50/50 border border-blue-200 p-3 text-[12px] text-blue-700 leading-relaxed">
                                <strong>Nota:</strong> este campo es opcional. Sirve para guardar las credenciales de delegación de dominio cuando esté disponible la integración avanzada con Gmail.
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Credenciales de Cuenta de Servicio (JSON)
                                </label>
                                <Textarea
                                    v-model="form.service_account_key"
                                    rows="3"
                                    class="w-full font-mono text-[11px] border-gray-200 rounded shadow-sm"
                                />
                                <p class="text-gray-400 text-[11px] mt-1">
                                    Se encriptará y almacenará de forma segura. Deja vacío si aún no tienes delegación de dominio.
                                </p>
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Estado
                                </label>
                                <Dropdown
                                    v-model="form.estado"
                                    :options="estadoOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                />
                            </div>

                            <div>
                                <label class="block text-[13px] text-gray-600 font-medium mb-1">
                                    Asignar a Trabajador (Opcional)
                                </label>
                                <Dropdown
                                    v-model="form.worker_id"
                                    :options="workers"
                                    option-label="name"
                                    option-value="id"
                                    class="w-full text-sm border-gray-200 rounded shadow-sm"
                                    showClear
                                />
                                <p v-if="form.errors.worker_id" class="text-red-500 text-xs mt-1">{{ form.errors.worker_id }}</p>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button
                                    type="submit"
                                    class="bg-[#1a56db] hover:bg-blue-800 text-white text-sm font-medium py-2 px-5 rounded shadow-sm transition-colors flex items-center gap-2"
                                    :disabled="form.processing"
                                >
                                    Actualizar Correo
                                </button>
                                <Link :href="route('company-emails.index')" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium py-2 px-5 rounded shadow-sm transition-colors">
                                    Cancelar
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
