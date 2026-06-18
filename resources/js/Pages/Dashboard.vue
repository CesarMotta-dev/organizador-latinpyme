<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3'; 
import { ref, computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
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
    },
    companyEmails: {
        type: Array,
        default: () => []
    },
    selectedCompanyEmailId: {
        type: Number,
        default: null
    }
});

// 2. Estado
const selectedBuzon = ref(props.selectedCompanyEmailId);
const visibleModalRule = ref(false);
const isEditing = ref(false);
const currentRuleId = ref(null);
const selectedRules = ref([]);

// 3. Filtros (Usamos 'contains' en texto plano para evitar importar FilterMatchMode)
const filters = ref({
    global: { value: null, matchMode: 'contains' }
});

const carpetasDisponibles = computed(() => {
    const folders = new Set(props.reglasDB.map(r => r.carpeta).filter(c => c && c.toLowerCase() !== 'inbox'));
    return Array.from(folders).sort();
});

// 4. Formulario (Inertia)
const form = useForm({
    company_email_id: props.selectedCompanyEmailId,
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
    csv: '',
    company_email_id: props.selectedCompanyEmailId
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
    form.company_email_id = props.selectedCompanyEmailId;
    form.clearErrors();
    visibleModalRule.value = true;
}

function editRule(regla) {
    isEditing.value = true;
    currentRuleId.value = regla.id; 
    form.company_email_id = regla.company_email_id || props.selectedCompanyEmailId;
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

const deleteRule = (regla) => {
    if (confirm(`¿Estás seguro de que deseas eliminar la regla para ${regla.correo}?`)) {
        form.delete(route('email-rules.destroy', regla.id));
    }
};

const deleteSelectedRules = () => {
    if (!selectedRules.value || selectedRules.value.length === 0) return;
    if (confirm(`¿Estás seguro de que deseas eliminar las ${selectedRules.value.length} reglas seleccionadas?`)) {
        router.delete(route('email-rules.destroyMultiple'), {
            data: { ids: selectedRules.value.map(r => r.id) },
            onSuccess: () => {
                selectedRules.value = [];
            }
        });
    }
};

const updateRuleInline = (regla) => {
    router.put(route('email-rules.update', regla.id), regla, {
        preserveScroll: true,
        preserveState: true,
    });
};

const updateConfirmaInline = (regla) => {
    if (regla.confirma_sugerencia === 'Sí') {
        regla.carpeta = regla.carpeta_sugerida;
    } else {
        regla.carpeta = 'Sin clasificar';
    }
    router.put(route('email-rules.update', regla.id), regla, {
        preserveScroll: true,
        preserveState: true,
    });
};

const deleteAllRules = () => {
    if (confirm('¿Estás SEGURO de que deseas eliminar TODAS las reglas? Esta acción no se puede deshacer.')) {
        form.delete(route('email-rules.destroyAll', { company_email_id: props.selectedCompanyEmailId }));
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
                        
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                            <!-- Dropdown para cambiar de buzón -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-600">Buzón:</span>
                                <Dropdown 
                                    v-model="selectedBuzon"
                                    :options="companyEmails" 
                                    optionLabel="email" 
                                    optionValue="id" 
                                    placeholder="Selecciona un Buzón" 
                                    class="w-full md:w-64 border-gray-300"
                                    @change="(e) => router.get(route('dashboard'), { company_email_id: e.value })"
                                />
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <span class="relative">
                                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <InputText v-model="filters['global'].value" placeholder="Buscar regla..." size="small" class="pl-10" />
                                </span>
                                <Button label="Crear Regla" icon="pi pi-plus" severity="success" @click="openNew"></Button>
                                <Button label="Eliminar Seleccionadas" icon="pi pi-trash" severity="danger" class="ml-2" @click="deleteSelectedRules" :disabled="!selectedRules || selectedRules.length === 0"></Button>
                                <Button label="Eliminar Todas" icon="pi pi-trash" severity="danger" class="ml-2" @click="deleteAllRules" :disabled="reglasDB.length === 0"></Button>
                                <Button label="Importar CSV" icon="pi pi-cloud-upload" severity="help" class="ml-2" @click="openImportModal"></Button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-0">
                        <DataTable 
                            v-model:filters="filters" 
                            v-model:selection="selectedRules"
                            :value="reglasDB" 
                            dataKey="id"
                            scrollable
                            scrollHeight="600px"
                            tableStyle="min-width: 100%" 
                            :globalFilterFields="['correo', 'carpeta', 'estado', 'asunto', 'carpeta_sugerida', 'confirma_sugerencia', 'carpeta_elegida', 'id_mensaje']"
                        >
                            <template #empty>
                                <div class="p-8 text-center text-gray-500">
                                    No se encontraron reglas registradas.
                                </div>
                            </template>
                            
                            <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

                            <Column field="correo" header="Correo del Remitente" sortable>
                                <template #body="slotProps">
                                    <span class="font-medium text-gray-800 block max-w-[160px] truncate" :title="slotProps.data.correo">
                                        {{ slotProps.data.correo }}
                                    </span>
                                </template>
                            </Column>
                            
                            <Column field="carpeta" header="Carpeta Destino" sortable>
                                <template #body="slotProps">
                                    <Dropdown 
                                        v-model="slotProps.data.carpeta" 
                                        :options="carpetasDisponibles"
                                        editable
                                        class="w-full min-w-[140px]"
                                        @change="updateRuleInline(slotProps.data)"
                                    />
                                </template>
                            </Column>

                            <Column field="asunto" header="Asunto" sortable>
                                <template #body="slotProps">
                                    <span class="font-medium text-gray-800 block max-w-[220px] truncate" :title="slotProps.data.asunto">
                                        {{ slotProps.data.asunto }}
                                    </span>
                                </template>
                            </Column>

                            <Column field="carpeta_sugerida" header="Carpeta Sugerida" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.carpeta_sugerida" severity="warning" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="confirma_sugerencia" header="Confirma" sortable>
                                <template #body="slotProps">
                                    <Checkbox 
                                        v-model="slotProps.data.confirma_sugerencia" 
                                        trueValue="Sí" 
                                        falseValue="No" 
                                        binary
                                        @change="updateConfirmaInline(slotProps.data)"
                                    />
                                </template>
                            </Column>

                            <Column field="carpeta_elegida" header="Carpeta Elegida" sortable>
                                <template #body="slotProps">
                                    <Tag :value="slotProps.data.carpeta_elegida" severity="info" rounded></Tag>
                                </template>
                            </Column>

                            <Column field="id_mensaje" header="ID Mensaje" sortable>
                                <template #body="slotProps">
                                    <span class="text-sm text-gray-700 block max-w-[80px] truncate" :title="slotProps.data.id_mensaje">
                                        {{ slotProps.data.id_mensaje }}
                                    </span>
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
                                    <Button 
                                        icon="pi pi-trash" 
                                        outlined 
                                        rounded 
                                        severity="danger"
                                        @click="deleteRule(slotProps.data)"
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
                    <Dropdown 
                        id="carpeta" 
                        v-model="form.carpeta" 
                        :options="carpetasDisponibles"
                        editable
                        class="w-full"
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
                    <div class="flex items-center gap-2">
                        <Checkbox 
                            inputId="confirma_sugerencia" 
                            v-model="form.confirma_sugerencia" 
                            trueValue="Sí" 
                            falseValue="No" 
                            binary
                            @change="form.confirma_sugerencia === 'Sí' ? form.carpeta = form.carpeta_sugerida : form.carpeta = 'Sin clasificar'"
                        />
                        <label for="confirma_sugerencia" class="font-semibold text-gray-700 cursor-pointer">Confirma Sugerencia</label>
                    </div>
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