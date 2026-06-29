<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import FileManager from '@/Components/FileManager.vue';
import { useStored, uid } from '@/lib/store';

const clientes = useStored('clientes', () => []);

// Campos agrupados por sección. type: text|date|number|select|textarea
const SECTIONS = [
    {
        title: 'Datos generales',
        fields: [
            { key: 'nombre', label: 'Nombre completo', type: 'text', req: true, col: 2 },
            { key: 'tipo', label: 'Tipo', type: 'select', options: ['Arrendatario', 'Cliente', 'Propietario', 'Aval'] },
            { key: 'estado', label: 'Estatus', type: 'select', options: ['Activo', 'Inactivo', 'Prospecto', 'Moroso'] },
            { key: 'rfc', label: 'RFC', type: 'text' },
            { key: 'curp', label: 'CURP', type: 'text' },
            { key: 'fecha_nacimiento', label: 'Fecha de nacimiento', type: 'date' },
            { key: 'estado_civil', label: 'Estado civil', type: 'select', options: ['Soltero(a)', 'Casado(a)', 'Divorciado(a)', 'Viudo(a)', 'Unión libre'] },
            { key: 'nacionalidad', label: 'Nacionalidad', type: 'text' },
            { key: 'identificacion', label: 'Tipo de identificación', type: 'select', options: ['INE', 'Pasaporte', 'Cédula', 'Licencia'] },
            { key: 'num_identificacion', label: 'No. de identificación', type: 'text' },
        ],
    },
    {
        title: 'Contacto',
        fields: [
            { key: 'email', label: 'Correo electrónico', type: 'text' },
            { key: 'telefono', label: 'Teléfono', type: 'text' },
            { key: 'telefono2', label: 'Teléfono alterno / WhatsApp', type: 'text' },
            { key: 'direccion', label: 'Dirección', type: 'text', col: 2 },
            { key: 'colonia', label: 'Colonia', type: 'text' },
            { key: 'ciudad', label: 'Ciudad', type: 'text' },
            { key: 'estado_geo', label: 'Estado', type: 'text' },
            { key: 'cp', label: 'Código postal', type: 'text' },
        ],
    },
    {
        title: 'Información económica y laboral',
        fields: [
            { key: 'ocupacion', label: 'Ocupación / Profesión', type: 'text' },
            { key: 'empresa', label: 'Empresa donde labora', type: 'text' },
            { key: 'puesto', label: 'Puesto', type: 'text' },
            { key: 'ingresos', label: 'Ingresos mensuales (MXN)', type: 'number' },
            { key: 'fuente_ingresos', label: 'Fuente de ingresos', type: 'text' },
            { key: 'ref_laboral', label: 'Referencia laboral (tel.)', type: 'text' },
        ],
    },
    {
        title: 'Arrendamiento',
        fields: [
            { key: 'propiedad', label: 'Propiedad / Inmueble asignado', type: 'text', col: 2 },
            { key: 'inicio_contrato', label: 'Inicio de contrato', type: 'date' },
            { key: 'fin_contrato', label: 'Fin de contrato', type: 'date' },
            { key: 'renta_mensual', label: 'Renta mensual (MXN)', type: 'number' },
            { key: 'deposito', label: 'Depósito en garantía (MXN)', type: 'number' },
            { key: 'dia_pago', label: 'Día de pago', type: 'number' },
            { key: 'aval', label: 'Aval / Fiador', type: 'text' },
        ],
    },
    {
        title: 'Contacto de emergencia',
        fields: [
            { key: 'emergencia_nombre', label: 'Nombre', type: 'text' },
            { key: 'emergencia_parentesco', label: 'Parentesco', type: 'text' },
            { key: 'emergencia_tel', label: 'Teléfono', type: 'text' },
        ],
    },
];

function emptyCliente() {
    const c = { id: uid(), archivos: [], notas: '' };
    for (const s of SECTIONS) for (const f of s.fields) c[f.key] = '';
    c.tipo = 'Arrendatario';
    c.estado = 'Activo';
    return c;
}

const search = ref('');
const editing = ref(null); // copia editable

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return clientes.value;
    return clientes.value.filter((c) => [c.nombre, c.email, c.telefono, c.rfc, c.propiedad].join(' ').toLowerCase().includes(q));
});

const money = (v) => (v ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v)) : '—');

function openNew() {
    editing.value = emptyCliente();
}
function openEdit(c) {
    editing.value = JSON.parse(JSON.stringify(c));
}
function closeEditor() {
    editing.value = null;
}
function save() {
    if (!editing.value.nombre?.trim()) {
        alert('El nombre es obligatorio.');
        return;
    }
    const exists = clientes.value.some((c) => c.id === editing.value.id);
    clientes.value = exists
        ? clientes.value.map((c) => (c.id === editing.value.id ? editing.value : c))
        : [...clientes.value, editing.value];
    closeEditor();
}
function remove(c) {
    if (!confirm(`¿Eliminar a "${c.nombre}" y todos sus archivos?`)) return;
    clientes.value = clientes.value.filter((x) => x.id !== c.id);
}

function badgeClass(estado) {
    return {
        Activo: 'bg-emerald-100 text-emerald-700',
        Inactivo: 'bg-slate-200 text-slate-600',
        Prospecto: 'bg-sky-100 text-sky-700',
        Moroso: 'bg-rose-100 text-rose-700',
    }[estado] || 'bg-slate-100 text-slate-600';
}
</script>

<template>
    <Head title="Clientes o arrendatarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Clientes o arrendatarios</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <input v-model="search" type="search" placeholder="Buscar por nombre, correo, RFC o propiedad…" class="w-full max-w-md rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                <button type="button" class="shrink-0 rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="openNew">+ Nuevo cliente / arrendatario</button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="c in filtered" :key="c.id" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#7c3aed] to-[#c026d3] text-sm font-bold text-white">
                                {{ (c.nombre || '?').slice(0, 1).toUpperCase() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-bold text-slate-800">{{ c.nombre }}</p>
                                <p class="text-xs text-slate-400">{{ c.tipo }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="badgeClass(c.estado)">{{ c.estado || '—' }}</span>
                    </div>
                    <dl class="mt-4 space-y-1 text-sm text-slate-600">
                        <div class="flex justify-between gap-2"><dt class="text-slate-400">Propiedad</dt><dd class="truncate font-medium">{{ c.propiedad || '—' }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-slate-400">Renta</dt><dd class="font-medium">{{ money(c.renta_mensual) }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-slate-400">Teléfono</dt><dd class="truncate font-medium">{{ c.telefono || '—' }}</dd></div>
                        <div class="flex justify-between gap-2"><dt class="text-slate-400">Archivos</dt><dd class="font-medium">{{ (c.archivos || []).length }} 📎</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">
                        <button type="button" class="flex-1 rounded-lg bg-slate-100 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200" @click="openEdit(c)">Ver / Editar</button>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold text-rose-500 hover:bg-rose-50" @click="remove(c)">Eliminar</button>
                    </div>
                </div>

                <div v-if="!filtered.length" class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center text-sm text-slate-400">
                    No hay clientes o arrendatarios registrados todavía.
                </div>
            </div>

            <AppFooter />
        </div>

        <!-- Editor -->
        <div v-if="editing" class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4" @click.self="closeEditor">
            <div class="my-6 w-full max-w-4xl rounded-2xl bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl border-b border-slate-200 bg-white px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-800">Ficha del cliente / arrendatario</h3>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="closeEditor">✕</button>
                </div>

                <div class="space-y-7 px-6 py-5">
                    <section v-for="sec in SECTIONS" :key="sec.title">
                        <h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#7c3aed]">{{ sec.title }}</h4>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div v-for="f in sec.fields" :key="f.key" :class="f.col === 2 ? 'sm:col-span-2' : ''">
                                <label class="mb-1 block text-xs font-semibold text-slate-500">{{ f.label }}<span v-if="f.req" class="text-rose-500"> *</span></label>
                                <select v-if="f.type === 'select'" v-model="editing[f.key]" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                                    <option value="">—</option>
                                    <option v-for="o in f.options" :key="o" :value="o">{{ o }}</option>
                                </select>
                                <input v-else :type="f.type" v-model="editing[f.key]" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                            </div>
                        </div>
                    </section>

                    <section>
                        <h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#7c3aed]">Notas</h4>
                        <textarea v-model="editing.notas" rows="3" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" placeholder="Observaciones, acuerdos, historial…"></textarea>
                    </section>

                    <section>
                        <h4 class="mb-3 text-sm font-bold uppercase tracking-wide text-[#7c3aed]">Documentos y archivos</h4>
                        <FileManager v-model="editing.archivos" title="Archivos del cliente" />
                    </section>
                </div>

                <div class="sticky bottom-0 flex justify-end gap-2 rounded-b-2xl border-t border-slate-200 bg-white px-6 py-4">
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100" @click="closeEditor">Cancelar</button>
                    <button type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90" @click="save">Guardar</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
