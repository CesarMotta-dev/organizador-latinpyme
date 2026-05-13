<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3'; 
import { ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
//import { CustomCard } from '@/Components/Avalon/Ui/custom-card';
//import { FilterMatchMode } from '@primevue/core/api';
//import { get } from '@vueuse/core';
//import { ref, computed, onMounted, reactive } from 'vue';
//import { useConfirm } from "primevue/useconfirm";
//import { useToast } from 'primevue/usetoast';
//import ConfirmPopup from 'primevue/confirmpopup';
//import NProgress from 'nprogress'
//import Tag from 'primevue/tag';
//import { Form } from '@primevue/forms';
//import { zodResolver } from '@primevue/forms/resolvers/zod';
//import { z } from 'zod';
//import DatePicker from 'primevue/datepicker';
//import ColorPicker from 'primevue/colorpicker';
//import MultiSelect from 'primevue/multiselect';
//import Dropdown from 'primevue/dropdown';
//import InputText from 'primevue/inputtext';
//import Textarea from 'primevue/textarea';

//import { Cropper, Preview } from 'vue-advanced-cropper';
//import 'vue-advanced-cropper/dist/style.css';
//import axios from 'axios';
//import { DatetimeFormat } from 'vue-i18n';
//import { getCanVGrowWithinCell } from '@fullcalendar/core/internal';

// 1. Props con seguro de vida
const props = defineProps({
    reglasDB: {
        type: Array,
        default: () => [] 
    }
});

// 2. Estado
const visibleModalRule = ref(false);
const isEditing = ref(false);
const currentRuleId = ref(null);

// 3. Filtros (Usamos 'contains' en texto plano para evitar importar FilterMatchMode)
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

// 4. Formulario (Inertia)
const form = useForm({
    correo: '',
    carpeta: '',
    asunto: '',
    observaciones: '',
    carpeta_sugerida: '',
    confirma_sugerencia: 'No',
    carpeta_elegida: '',
    id_mensaje: ''
});

const importForm = useForm({
    csv: ''
});
const visibleImportModal = ref(false);
const importErrors = ref([]);
const importStatus = ref('');
const csvInput = ref(null);

function openCsvFileDialog() {
    csvInput.value?.click();
}

// 5. Funciones
function openNew() {
    isEditing.value = false;
    currentRuleId.value = null;
    form.reset();
    form.clearErrors();
    visibleModalRule.value = true;
}

function editRule(regla) {
    isEditing.value = true;
    currentRuleId.value = regla.id; 
    form.correo = regla.correo;
    form.carpeta = regla.carpeta;
    form.asunto = regla.asunto || '';
    form.observaciones = regla.observaciones || '';
    form.carpeta_sugerida = regla.carpeta_sugerida || '';
    form.confirma_sugerencia = regla.confirma_sugerencia || 'No';
    form.carpeta_elegida = regla.carpeta_elegida || '';
    form.id_mensaje = regla.id_mensaje || '';
    form.clearErrors();
    visibleModalRule.value = true;
}

const saveRule = () => {
    if (isEditing.value) {
        form.put(route('email-rules.update', currentRuleId.value), {
            onSuccess: () => {
                visibleModalRule.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('email-rules.store'), {
            onSuccess: () => {
                visibleModalRule.value = false;
                form.reset();
            }
        });
    }
};

function openImportModal() {
    visibleImportModal.value = true;
    importForm.csv = '';
    importErrors.value = [];
    importStatus.value = '';
}

const importRules = () => {
    importErrors.value = [];
    importStatus.value = '';

    if (!importForm.csv || importForm.csv.trim() === '') {
        importErrors.value.push('Pega el contenido del Sheet en formato CSV.');
        return;
    }

    importForm.post(route('email-rules.import'), {
        onSuccess: () => {
            visibleImportModal.value = false;
            importStatus.value = 'Importación completada correctamente.';
            importForm.csv = '';
        },
        onError: (errors) => {
            importStatus.value = 'Error al importar los datos.';
        }
    });
};

function handleCsvFile(event) {
    importErrors.value = [];
    importStatus.value = '';

    const file = event.target.files?.[0];
    if (!file) {
        return;
    }

    if (!file.name.toLowerCase().endsWith('.csv')) {
        importErrors.value.push('Selecciona un archivo CSV válido.');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        importForm.csv = e.target?.result?.toString() || '';
        if (!importForm.csv) {
            importErrors.value.push('No se pudo leer el archivo CSV.');
        }
    };
    reader.onerror = () => {
        importErrors.value.push('No se pudo leer el archivo CSV.');
    };
    reader.readAsText(file, 'UTF-8');
}
</script>

<template>
    <Head title="Smart Inbox | Latinpyme" />

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
                    Smart Inbox <span class="text-red-600 font-extrabold">Latinpyme</span>
                </h2>
            </div>
        </template>

        <div class="py-12 bg-[#FAFAFA] min-h-[calc(100vh-73px)]">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Reglas de Clasificación</h3>
                            <p class="text-sm text-gray-500 mt-1">Administra el destino de tus correos entrantes</p>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="relative">
                                <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <InputText v-model="filters['global'].value" placeholder="Buscar regla..." size="small" class="pl-10" />
                            </span>
                            <Button label="Crear Regla" icon="pi pi-plus" severity="success" @click="openNew"></Button>
                            <Button label="Importar Sheet" icon="pi pi-upload" severity="info" class="ml-2" @click="openImportModal"></Button>
                            <Button label="Subir CSV" icon="pi pi-cloud-upload" severity="help" class="ml-2" @click="openImportModal"></Button>
                        </div>
                    </div>
                    
                    <div class="p-0">
                        <DataTable 
                            v-model:filters="filters" 
                            :value="reglasDB" 
                            dataKey="id"
                            tableStyle="min-width: 50rem" 
                            :globalFilterFields="['correo', 'carpeta', 'estado', 'asunto', 'carpeta_sugerida', 'confirma_sugerencia', 'carpeta_elegida', 'id_mensaje']"
                        >
                            <template #empty>
                                <div class="p-8 text-center text-gray-500">
                                    No se encontraron reglas registradas.
                                </div>
                            </template>
                            
                            <Column field="correo" header="Correo del Remitente" sortable>
                                <template #body="slotProps">
                                    <span class="font-medium text-gray-800">{{ slotProps.data.correo }}</span>
                                </template>
                            </Column>
                            
                            <Column field="carpeta" header="Carpeta Destino" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.carpeta" severity="info" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="asunto" header="Asunto" sortable>
                                <template #body="slotProps">
                                    <span class="font-medium text-gray-800">{{ slotProps.data.asunto }}</span>
                                </template>
                            </Column>

                            <Column field="carpeta_sugerida" header="Carpeta Sugerida" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.carpeta_sugerida" severity="warning" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="confirma_sugerencia" header="Confirma" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.confirma_sugerencia" severity="success" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="carpeta_elegida" header="Carpeta Elegida" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.carpeta_elegida" severity="info" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="id_mensaje" header="ID Mensaje" sortable>
                                <template #body="slotProps">
                                    <span class="text-sm text-gray-700">{{ slotProps.data.id_mensaje }}</span>
                                </template>
                            </Column>

                            <Column field="estado" header="Clasificación">
                                <template #body="slotProps">
                                    <Tag 
                                        :value="slotProps.data.estado === 'Clasificado' ? 'Clasificado' : 'Sin clasificar'" 
                                        :severity="slotProps.data.estado === 'Clasificado' ? 'success' : 'secondary'"
                                    ></Tag>
                                </template>
                            </Column>

                            <Column header="Acciones" style="min-width: 8rem">
                                <template #body="slotProps">
                                    <Button 
                                        icon="pi pi-pencil" 
                                        outlined 
                                        rounded 
                                        class="mr-2"
                                        severity="info"
                                        @click="editRule(slotProps.data)"
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>

            </div>
        </div>

        <Dialog v-model:visible="visibleModalRule" :modal="true" :style="{ width: '90vw', maxWidth: '500px' }">
            <template #header>
                <strong class="text-gray-900 text-xl">{{ isEditing ? 'Editar Regla' : 'Crear Regla' }}</strong>
            </template>
            
            <form @submit.prevent="saveRule" class="flex flex-col gap-4 mt-4">
                <div class="flex flex-col gap-1">
                    <label for="correo" class="font-semibold text-gray-700">Correo Electrónico</label>
                    <InputText 
                        id="correo" 
                        v-model="form.correo" 
                        type="email" 
                        placeholder="ejemplo@proveedor.com" 
                        :invalid="form.errors.correo ? true : false"
                    />
                    <small v-if="form.errors.correo" class="text-red-500">{{ form.errors.correo }}</small>
                </div>
                
                <div class="flex flex-col gap-1">
                    <label for="carpeta" class="font-semibold text-gray-700">Carpeta de Destino</label>
                    <InputText 
                        id="carpeta" 
                        v-model="form.carpeta" 
                        type="text" 
                        placeholder="Ej: FACTURACIÓN" 
                        :invalid="form.errors.carpeta ? true : false"
                    />
                    <small v-if="form.errors.carpeta" class="text-red-500">{{ form.errors.carpeta }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="asunto" class="font-semibold text-gray-700">Asunto</label>
                    <InputText
                        id="asunto"
                        v-model="form.asunto"
                        type="text"
                        placeholder="Asunto del correo"
                        :invalid="form.errors.asunto ? true : false"
                    />
                    <small v-if="form.errors.asunto" class="text-red-500">{{ form.errors.asunto }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="carpeta_sugerida" class="font-semibold text-gray-700">Carpeta Sugerida</label>
                    <InputText
                        id="carpeta_sugerida"
                        v-model="form.carpeta_sugerida"
                        type="text"
                        placeholder="Ej: FACTURACIÓN SUGERIDA"
                        :invalid="form.errors.carpeta_sugerida ? true : false"
                    />
                    <small v-if="form.errors.carpeta_sugerida" class="text-red-500">{{ form.errors.carpeta_sugerida }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="carpeta_elegida" class="font-semibold text-gray-700">Carpeta Elegida</label>
                    <InputText
                        id="carpeta_elegida"
                        v-model="form.carpeta_elegida"
                        type="text"
                        placeholder="Ej: FACTURACIÓN DEFINITIVA"
                        :invalid="form.errors.carpeta_elegida ? true : false"
                    />
                    <small v-if="form.errors.carpeta_elegida" class="text-red-500">{{ form.errors.carpeta_elegida }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="confirma_sugerencia" class="font-semibold text-gray-700">Confirma Sugerencia</label>
                    <InputText
                        id="confirma_sugerencia"
                        v-model="form.confirma_sugerencia"
                        type="text"
                        placeholder="Sí / No"
                        :invalid="form.errors.confirma_sugerencia ? true : false"
                    />
                    <small v-if="form.errors.confirma_sugerencia" class="text-red-500">{{ form.errors.confirma_sugerencia }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="id_mensaje" class="font-semibold text-gray-700">ID Mensaje</label>
                    <InputText
                        id="id_mensaje"
                        v-model="form.id_mensaje"
                        type="text"
                        placeholder="ID del mensaje en el correo"
                        :invalid="form.errors.id_mensaje ? true : false"
                    />
                    <small v-if="form.errors.id_mensaje" class="text-red-500">{{ form.errors.id_mensaje }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="observaciones" class="font-semibold text-gray-700">Observaciones</label>
                    <Textarea
                        id="observaciones"
                        v-model="form.observaciones"
                        rows="4"
                        placeholder="Notas o comentarios adicionales"
                        class="w-full"
                        :invalid="form.errors.observaciones ? true : false"
                    />
                    <small v-if="form.errors.observaciones" class="text-red-500">{{ form.errors.observaciones }}</small>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="visibleModalRule = false" />
                    <Button type="submit" severity="success" :label="isEditing ? 'Actualizar' : 'Guardar'" icon="pi pi-check" :loading="form.processing" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="visibleImportModal" :modal="true" :style="{ width: '90vw', maxWidth: '700px' }">
            <template #header>
                <strong class="text-gray-900 text-xl">Importar reglas desde Google Sheet</strong>
            </template>

            <div class="flex flex-col gap-4 mt-4">
                <div class="text-sm text-gray-600">
                    Pega aquí el contenido del Sheet exportado como CSV. La primera fila debe incluir los encabezados:
                    <span class="font-semibold">CORREOS, CARPETA, ASUNTO, OBSERVACIONES, CARPETA SUGERIDA, CONFIRMA SUGERENCIA, CARPETA ELEGIDA, ID_MENSAJE</span>.
                </div>

                <div class="flex flex-col gap-1">
                    <label class="font-semibold text-gray-700">Subir archivo CSV</label>
                    <div class="flex items-center gap-3">
                        <Button label="Seleccionar CSV" icon="pi pi-upload" severity="info" class="min-w-[160px]" @click="openCsvFileDialog" />
                        <span class="text-sm text-gray-500">Selecciona el CSV exportado desde Google Sheets.</span>
                    </div>
                    <input
                        ref="csvInput"
                        id="csv_file"
                        type="file"
                        accept=".csv,text/csv"
                        class="hidden"
                        @change="handleCsvFile"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label for="csv_import" class="font-semibold text-gray-700">Contenido CSV</label>
                    <Textarea
                        id="csv_import"
                        v-model="importForm.csv"
                        rows="10"
                        placeholder="CORREOS,CARPETA,ASUNTO,OBSERVACIONES,CARPETA SUGERIDA,CONFIRMA SUGERENCIA,CARPETA ELEGIDA,ID_MENSAJE"
                        class="w-full"
                    />
                    <small class="text-gray-500">También puedes pegar el contenido del CSV aquí.</small>
                </div>

                <div class="text-sm text-gray-500">
                    Si seleccionas un archivo, su contenido se cargará automáticamente en el campo anterior.
                </div>

                <div class="flex flex-col gap-2">
                    <div v-if="importStatus" class="text-sm text-green-700">{{ importStatus }}</div>
                    <div v-if="importErrors.length" class="space-y-1 text-sm text-red-700">
                        <div v-for="(error, index) in importErrors" :key="index">- {{ error }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" label="Cancelar" severity="secondary" outlined @click="visibleImportModal = false" />
                    <Button type="button" label="Importar" severity="success" icon="pi pi-check" @click="importRules" :loading="importForm.processing" />
                </div>
            </div>
        </Dialog>

    </AuthenticatedLayout>
</template>