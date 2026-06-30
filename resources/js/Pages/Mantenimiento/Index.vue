<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import FileManager from '@/Components/FileManager.vue';
import { useStored, uid, todayISO } from '@/lib/store';

const registros = useStored('mantenimiento', () => []);

const TIPOS = ['Preventivo', 'Correctivo', 'Mejora', 'Emergencia', 'Inspección'];
const PRIORIDADES = ['Baja', 'Media', 'Alta', 'Urgente'];
const ESTADOS = ['Solicitado', 'Programado', 'En proceso', 'Completado', 'Cancelado'];

function emptyReg() {
    return {
        id: uid(),
        inmueble: '',
        ubicacion: '',
        tipo: 'Preventivo',
        prioridad: 'Media',
        estado: 'Solicitado',
        descripcion: '',
        responsable: '',
        proveedor: '',
        fecha_solicitud: todayISO(),
        fecha_programada: '',
        fecha_completado: '',
        costo_estimado: '',
        costo_real: '',
        garantia: '',
        notas: '',
        archivos: [],
    };
}

const search = ref('');
const filtroEstado = ref('');
const editing = ref(null);

const filtered = computed(() =>
    registros.value.filter((r) => {
        const q = search.value.trim().toLowerCase();
        const okQ = !q || [r.inmueble, r.descripcion, r.responsable, r.proveedor].join(' ').toLowerCase().includes(q);
        const okE = !filtroEstado.value || r.estado === filtroEstado.value;
        return okQ && okE;
    }),
);

const money = (v) => (v ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v)) : '—');

const totalEstimado = computed(() => registros.value.reduce((a, r) => a + Number(r.costo_estimado || 0), 0));
const totalReal = computed(() => registros.value.reduce((a, r) => a + Number(r.costo_real || 0), 0));

function openNew() {
    editing.value = emptyReg();
}
function openEdit(r) {
    editing.value = JSON.parse(JSON.stringify(r));
}
function close() {
    editing.value = null;
}
function save() {
    if (!editing.value.inmueble?.trim()) {
        alert('Indica el inmueble.');
        return;
    }
    const exists = registros.value.some((r) => r.id === editing.value.id);
    registros.value = exists
        ? registros.value.map((r) => (r.id === editing.value.id ? editing.value : r))
        : [...registros.value, editing.value];
    close();
}
function remove(r) {
    if (!confirm('¿Eliminar este registro de mantenimiento?')) return;
    registros.value = registros.value.filter((x) => x.id !== r.id);
}

function estadoClass(e) {
    return {
        Solicitado: 'bg-slate-200 text-slate-600',
        Programado: 'bg-sky-100 text-sky-700',
        'En proceso': 'bg-amber-100 text-amber-700',
        Completado: 'bg-emerald-100 text-emerald-700',
        Cancelado: 'bg-rose-100 text-rose-700',
    }[e] || 'bg-slate-100 text-slate-600';
}
function prioridadClass(p) {
    return {
        Baja: 'text-slate-500',
        Media: 'text-sky-600',
        Alta: 'text-amber-600',
        Urgente: 'text-rose-600',
    }[p] || 'text-slate-500';
}
</script>

<template>
    <Head title="Mantenimiento de inmuebles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Mantenimiento de inmuebles</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-5">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-400">Registros</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ registros.length }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-400">Costo estimado</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ money(totalEstimado) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-400">Costo real</p>
                    <p class="mt-1 text-2xl font-extrabold text-slate-800">{{ money(totalReal) }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-full max-w-xl gap-2">
                    <input v-model="search" type="search" placeholder="Buscar inmueble, descripción o responsable…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="filtroEstado" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos los estatus</option>
                        <option v-for="e in ESTADOS" :key="e" :value="e">{{ e }}</option>
                    </select>
                </div>
                <button type="button" class="shrink-0 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="openNew">+ Nuevo mantenimiento</button>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3">Inmueble</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3">Prioridad</th>
                                <th class="px-4 py-3">Programado</th>
                                <th class="px-4 py-3">Estatus</th>
                                <th class="px-4 py-3 text-right">Costo real</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="r in filtered" :key="r.id" class="text-sm text-slate-700 hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ r.inmueble }}<span v-if="r.descripcion" class="block max-w-xs truncate text-xs font-normal text-slate-400">{{ r.descripcion }}</span></td>
                                <td class="px-4 py-3">{{ r.tipo }}</td>
                                <td class="px-4 py-3 font-semibold" :class="prioridadClass(r.prioridad)">{{ r.prioridad }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ r.fecha_programada || '—' }}</td>
                                <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="estadoClass(r.estado)">{{ r.estado }}</span></td>
                                <td class="px-4 py-3 text-right font-semibold">{{ money(r.costo_real) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]" @click="openEdit(r)">Editar</button>
                                    <button type="button" class="ml-3 font-semibold text-rose-500 hover:text-rose-700" @click="remove(r)">Eliminar</button>
                                </td>
                            </tr>
                            <tr v-if="!filtered.length">
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">No hay registros de mantenimiento.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <AppFooter />
        </div>

        <!-- Editor -->
        <div v-if="editing" class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4" @click.self="close">
            <div class="my-6 w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-slate-200 bg-white px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-800">Orden de mantenimiento</h3>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="close">✕</button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Inmueble *</label>
                            <input v-model="editing.inmueble" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Ubicación / Área</label>
                            <input v-model="editing.ubicacion" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Tipo</label>
                            <select v-model="editing.tipo" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"><option v-for="t in TIPOS" :key="t">{{ t }}</option></select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Prioridad</label>
                            <select v-model="editing.prioridad" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"><option v-for="p in PRIORIDADES" :key="p">{{ p }}</option></select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Estatus</label>
                            <select v-model="editing.estado" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"><option v-for="e in ESTADOS" :key="e">{{ e }}</option></select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Responsable</label>
                            <input v-model="editing.responsable" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Proveedor / Contratista</label>
                            <input v-model="editing.proveedor" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Fecha de solicitud</label>
                            <input v-model="editing.fecha_solicitud" type="date" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Fecha programada</label>
                            <input v-model="editing.fecha_programada" type="date" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Fecha de término</label>
                            <input v-model="editing.fecha_completado" type="date" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Costo estimado (MXN)</label>
                            <input v-model="editing.costo_estimado" type="number" step="0.01" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Costo real (MXN)</label>
                            <input v-model="editing.costo_real" type="number" step="0.01" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Garantía</label>
                            <input v-model="editing.garantia" type="text" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" placeholder="Ej. 6 meses en mano de obra" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Descripción del trabajo</label>
                            <textarea v-model="editing.descripcion" rows="3" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"></textarea>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#7c3aed]">Evidencias y documentos</h4>
                        <FileManager v-model="editing.archivos" title="Fotos, cotizaciones, facturas" />
                    </div>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100" @click="close">Cancelar</button>
                    <button type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="save">Guardar</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
