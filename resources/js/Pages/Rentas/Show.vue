<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    renta: { type: Object, required: true },
});

const page = usePage();

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v || 0));

const fecha = (v) => (v ? new Date(v).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' }) : '—');

const estadosPago = {
    al_corriente: 'Al corriente',
    con_adeudo: 'Con adeudo',
    pagada: 'Pagada',
};

const estadoBadge = {
    pagado: 'bg-emerald-100 text-emerald-700',
    parcial: 'bg-amber-100 text-amber-700',
    vencido: 'bg-red-100 text-red-700',
    pendiente: 'bg-slate-100 text-slate-600',
};

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

// ---- Aumento de renta ----
const aumentoSugerido = Number(props.renta.porcentaje_aumento_total ?? 0);
const aumentoForm = useForm({ porcentaje: aumentoSugerido });
const aplicarAumento = () => {
    if (confirm(`¿Aplicar un aumento de ${aumentoForm.porcentaje}% a la renta mensual?`)) {
        aumentoForm.post(route('rentas.aumentar', props.renta.id), { preserveScroll: true });
    }
};

// ---- Generar mensualidades ----
const generar = () => {
    router.post(route('rentas.pagos.generar', props.renta.id), {}, { preserveScroll: true });
};

// ---- Nueva mensualidad manual ----
const hoyPeriodo = new Date().toISOString().substring(0, 7);
const nuevaForm = useForm({
    periodo: hoyPeriodo,
    monto: props.renta.monto_mensual,
    monto_pagado: 0,
    recargo: 0,
    fecha_pago: '',
    notas: '',
});
const registrarMensualidad = () => {
    nuevaForm.post(route('rentas.pagos.store', props.renta.id), {
        preserveScroll: true,
        onSuccess: () => nuevaForm.reset('monto_pagado', 'recargo', 'fecha_pago', 'notas'),
    });
};

// ---- Editar / registrar pago de una mensualidad ----
const editId = ref(null);
const editForm = useForm({ monto: 0, monto_pagado: 0, recargo: 0, fecha_pago: '', notas: '' });
const abrirEdicion = (p) => {
    editId.value = p.id;
    editForm.clearErrors();
    editForm.monto = p.monto;
    editForm.monto_pagado = p.monto_pagado;
    editForm.recargo = p.estado === 'pagado' ? p.recargo : p.recargo_vigente;
    editForm.fecha_pago = p.fecha_pago ? String(p.fecha_pago).substring(0, 10) : new Date().toISOString().substring(0, 10);
    editForm.notas = p.notas ?? '';
};
const cerrarEdicion = () => { editId.value = null; };
const guardarPago = () => {
    editForm.patch(route('pagos.update', editId.value), {
        preserveScroll: true,
        onSuccess: () => cerrarEdicion(),
    });
};
const liquidar = (p) => {
    abrirEdicion(p);
    editForm.monto_pagado = Number(p.total_periodo);
};
const eliminarPago = (p) => {
    if (confirm(`¿Eliminar la mensualidad ${p.periodo}?`)) {
        router.delete(route('pagos.destroy', p.id), { preserveScroll: true });
    }
};

const pagos = computed(() => props.renta.pagos ?? []);
const resumen = computed(() => [
    { label: 'Total facturado', value: props.renta.total_facturado, color: 'from-[#7c3aed] to-[#c026d3]' },
    { label: 'Total cobrado', value: props.renta.total_cobrado, color: 'from-emerald-500 to-teal-500' },
    { label: 'Saldo / adeudo', value: props.renta.saldo_cuenta, color: 'from-rose-500 to-red-500' },
    { label: 'Recargos por mora', value: props.renta.total_recargos, color: 'from-amber-500 to-orange-500' },
]);
</script>

<template>
    <Head :title="`Estado de cuenta — ${renta.inquilino}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight text-slate-800">Estado de cuenta</h2>
                <Link :href="route('rentas.index')" class="text-sm font-semibold text-[#7c3aed] hover:text-[#c026d3]">← Volver a rentas</Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ page.props.flash.error }}
            </div>

            <!-- Ficha del arrendatario -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ renta.inquilino }}</h3>
                        <p class="text-sm text-slate-500">{{ renta.propiedad?.nombre ?? 'Sin propiedad asignada' }}</p>
                        <span :class="['mt-2 inline-block rounded-full px-3 py-1 text-xs font-semibold', renta.estado_pago === 'al_corriente' ? 'bg-emerald-100 text-emerald-700' : renta.estado_pago === 'con_adeudo' ? 'bg-red-100 text-red-700' : 'bg-violet-100 text-violet-700']">
                            {{ estadosPago[renta.estado_pago] ?? renta.estado_pago }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
                        <span class="text-slate-400">Renta mensual</span>
                        <span class="text-right font-bold text-slate-800">{{ currency(renta.monto_mensual) }}</span>
                        <span class="text-slate-400">Día de pago</span>
                        <span class="text-right font-semibold text-slate-700">Día {{ renta.dia_pago }} (+{{ renta.dias_gracia }} gracia)</span>
                        <span class="text-slate-400">Inicio de contrato</span>
                        <span class="text-right font-semibold text-slate-700">{{ fecha(renta.fecha_inicio) }}</span>
                        <span class="text-slate-400">Vence contrato</span>
                        <span class="text-right font-semibold text-slate-700">{{ fecha(renta.fecha_vencimiento_renta) }}</span>
                        <span class="text-slate-400">Último aumento</span>
                        <span class="text-right font-semibold text-slate-700">{{ fecha(renta.fecha_ultimo_aumento) }}</span>
                    </div>
                </div>
            </div>

            <!-- Resumen de saldo -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="c in resumen" :key="c.label" :class="['rounded-2xl bg-gradient-to-r p-5 text-white shadow-lg', c.color]">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">{{ c.label }}</p>
                    <p class="mt-2 text-2xl font-extrabold">{{ currency(c.value) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Aumento de renta -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Aumento de renta</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Aumento manual configurado: <b>{{ Number(renta.porcentaje_aumento).toFixed(2) }}%</b> ·
                        inflación del periodo: <b>{{ Number(renta.inflacion_periodo).toFixed(2) }}%</b>.
                    </p>
                    <div class="mt-4 flex flex-wrap items-end gap-3">
                        <div class="w-40">
                            <InputLabel for="pct" value="Porcentaje a aplicar (%)" />
                            <input id="pct" v-model="aumentoForm.porcentaje" type="number" step="0.01" min="0" :class="inputClass" />
                        </div>
                        <button @click="aplicarAumento" :disabled="aumentoForm.processing" class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 px-4 py-2 text-sm font-semibold text-white shadow hover:opacity-90 disabled:opacity-50">
                            Aplicar aumento
                        </button>
                        <button @click="aumentoForm.porcentaje = aumentoSugerido" type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            Automático ({{ aumentoSugerido.toFixed(2) }}%)
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-slate-400">El aumento automático usa porcentaje manual + inflación del periodo. La renta mensual se actualiza al instante.</p>
                </div>

                <!-- Registrar mensualidad -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-[#7c3aed]">Registrar mensualidad</h3>
                        <button @click="generar" class="rounded-xl border border-[#7c3aed] px-3 py-1.5 text-xs font-semibold text-[#7c3aed] hover:bg-violet-50">
                            ⟳ Generar mensualidades
                        </button>
                    </div>
                    <form @submit.prevent="registrarMensualidad" class="mt-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <InputLabel for="periodo" value="Periodo" />
                                <input id="periodo" v-model="nuevaForm.periodo" type="month" :class="inputClass" required />
                                <p v-if="nuevaForm.errors.periodo" class="mt-1 text-xs text-red-600">{{ nuevaForm.errors.periodo }}</p>
                            </div>
                            <div>
                                <InputLabel for="monto" value="Renta del periodo" />
                                <input id="monto" v-model="nuevaForm.monto" type="number" step="0.01" min="0" :class="inputClass" />
                            </div>
                            <div>
                                <InputLabel for="monto_pagado" value="Pagado" />
                                <input id="monto_pagado" v-model="nuevaForm.monto_pagado" type="number" step="0.01" min="0" :class="inputClass" />
                            </div>
                            <div>
                                <InputLabel for="fecha_pago" value="Fecha de pago" />
                                <input id="fecha_pago" v-model="nuevaForm.fecha_pago" type="date" :class="inputClass" />
                            </div>
                        </div>
                        <button type="submit" :disabled="nuevaForm.processing" class="w-full rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow hover:opacity-90 disabled:opacity-50">
                            + Agregar mensualidad
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tabla de mensualidades -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-600">Detalle de mensualidades</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3">Periodo</th>
                                <th class="px-4 py-3">Vence renta</th>
                                <th class="px-4 py-3">Vence pago</th>
                                <th class="px-4 py-3 text-right">Renta</th>
                                <th class="px-4 py-3 text-right">Recargo</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Pagado</th>
                                <th class="px-4 py-3 text-right">Saldo</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Fecha pago</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="p in pagos" :key="p.id" class="text-sm text-slate-700 hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ p.periodo }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ fecha(p.fecha_vencimiento_renta) }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ fecha(p.fecha_vencimiento_pago) }}</td>
                                <td class="px-4 py-3 text-right">{{ currency(p.monto) }}</td>
                                <td class="px-4 py-3 text-right" :class="Number(p.recargo_vigente) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400'">{{ currency(p.recargo_vigente) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ currency(p.total_periodo) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600">{{ currency(p.monto_pagado) }}</td>
                                <td class="px-4 py-3 text-right font-bold" :class="Number(p.saldo) > 0 ? 'text-red-600' : 'text-emerald-600'">{{ currency(p.saldo) }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold capitalize', estadoBadge[p.estado_calculado]]">{{ p.estado_calculado }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ fecha(p.fecha_pago) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button v-if="Number(p.saldo) > 0" @click="liquidar(p)" class="font-semibold text-emerald-600 hover:text-emerald-800">Liquidar</button>
                                    <button @click="abrirEdicion(p)" class="ml-3 font-semibold text-[#7c3aed] hover:text-[#c026d3]">Editar</button>
                                    <button @click="eliminarPago(p)" class="ml-3 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                                </td>
                            </tr>
                            <tr v-if="pagos.length === 0">
                                <td colspan="11" class="px-6 py-10 text-center text-sm text-slate-400">
                                    Sin mensualidades. Usa «Generar mensualidades» o agrega una manualmente.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de edición / registro de pago -->
        <div v-if="editId" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" @click.self="cerrarEdicion">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-800">Registrar pago</h3>
                <form @submit.prevent="guardarPago" class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="e_monto" value="Renta del periodo" />
                            <input id="e_monto" v-model="editForm.monto" type="number" step="0.01" min="0" :class="inputClass" />
                        </div>
                        <div>
                            <InputLabel for="e_recargo" value="Recargo" />
                            <input id="e_recargo" v-model="editForm.recargo" type="number" step="0.01" min="0" :class="inputClass" />
                        </div>
                        <div>
                            <InputLabel for="e_pagado" value="Monto pagado" />
                            <input id="e_pagado" v-model="editForm.monto_pagado" type="number" step="0.01" min="0" :class="inputClass" />
                        </div>
                        <div>
                            <InputLabel for="e_fecha" value="Fecha de pago" />
                            <input id="e_fecha" v-model="editForm.fecha_pago" type="date" :class="inputClass" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="e_notas" value="Notas" />
                        <textarea id="e_notas" v-model="editForm.notas" rows="2" :class="inputClass"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="cerrarEdicion" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="editForm.processing" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2 text-sm font-semibold text-white shadow hover:opacity-90 disabled:opacity-50">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
