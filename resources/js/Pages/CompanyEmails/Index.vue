<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    companyEmails: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <Head title="Correos Empresariales | Latinpyme" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3 w-full">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900">Correos Empresariales</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestiona los correos corporativos y sus reglas asociadas.</p>
                </div>
                <Link :href="route('company-emails.create')" class="inline-flex">
                    <Button label="Nuevo Correo" icon="pi pi-plus" severity="success" />
                </Link>
            </div>
        </template>

        <div class="py-12 bg-[#FAFAFA] min-h-[calc(100vh-73px)]">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden p-6">
                    <DataTable :value="companyEmails" dataKey="id" emptyMessage="No hay correos empresariales registrados.">
                        <Column field="empresa" header="Empresa" sortable>
                            <template #body="slotProps">
                                <span class="font-medium text-gray-800">{{ slotProps.data.empresa }}</span>
                            </template>
                        </Column>
                        <Column field="email" header="Correo" sortable />
                        <Column field="nombre" header="Nombre" sortable />
                        <Column field="estado" header="Estado">
                            <template #body="slotProps">
                                <Tag :value="slotProps.data.estado" severity="slotProps.data.estado === 'Activo' ? 'success' : 'danger'" rounded />
                            </template>
                        </Column>
                        <Column header="Delegación">
                            <template #body="slotProps">
                                <Tag
                                    :value="slotProps.data.service_account_key ? 'Configurada' : 'No configurada'"
                                    :severity="slotProps.data.service_account_key ? 'success' : 'warning'"
                                    rounded
                                />
                            </template>
                        </Column>
                        <Column header="Acciones" style="min-width: 10rem">
                            <template #body="slotProps">
                                <Link :href="route('company-emails.edit', slotProps.data.id)">
                                    <Button icon="pi pi-pencil" rounded severity="info" text />
                                </Link>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
