<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    deletedEmails: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'Desconocida';
    return dayjs(dateString).format('DD/MM/YYYY HH:mm');
};
</script>

<template>
    <Head title="Correos Eliminados" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Correos Eliminados (>72 Hrs)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">
                                Aquí puedes consultar el registro de los correos que fueron eliminados automáticamente como parte del flujo de automatización (después de 72 horas).
                            </p>
                        </div>

                        <div class="overflow-x-auto bg-white rounded-lg border border-gray-200 shadow-sm">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 font-semibold">Remitente</th>
                                        <th scope="col" class="px-6 py-3 font-semibold">Asunto</th>
                                        <th scope="col" class="px-6 py-3 font-semibold">Fecha de Eliminación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="email in deletedEmails.data" :key="email.id" class="border-b hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            {{ email.correo_remitente || 'No especificado' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-block max-w-[300px] truncate" :title="email.asunto">
                                                {{ email.asunto || 'Sin Asunto' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ formatDate(email.fecha_eliminacion || email.created_at) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="deletedEmails.data.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center space-y-3">
                                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <p>No se encontraron registros de correos eliminados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación Simple -->
                        <div v-if="deletedEmails.links && deletedEmails.links.length > 3" class="mt-4 flex justify-center">
                            <div class="flex flex-wrap space-x-1">
                                <template v-for="(link, key) in deletedEmails.links" :key="key">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                    <a v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-blue-50 text-blue-600': link.active }" :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
