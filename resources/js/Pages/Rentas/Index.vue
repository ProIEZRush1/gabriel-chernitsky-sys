<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { confirmDelete } from '@/lib/swal';

const props = defineProps({
    rentas: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    resumen: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref(props.filters.q ?? '');
const estado = ref(props.filters.estado_pago ?? '');

const estados = { al_corriente: 'Al corriente', con_adeudo: 'Con adeudo', pagada: 'Pagada' };

const currency = (v) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(v || 0));

const submitFilters = () => {
    router.get(route('rentas.index'), { q: search.value, estado_pago: estado.value }, { preserveState: true, replace: true });
};

const destroy = async (item) => {
    const ok = await confirmDelete({
        title: 'Eliminar renta',
        text: `Se eliminará la renta de "${item.inquilino}" y su historial de pagos. Esta acción no se puede deshacer.`,
    });
    if (ok) {
        router.delete(route('rentas.destroy', item.id));
    }
};

const hoyISO = new Date().toISOString();
const periodoGenerar = ref(hoyISO.substring(0, 7));
const generando = ref(false);
const generarMensualidades = () => {
    generando.value = true;
    router.post(route('rentas.generar-todas'), { periodo: periodoGenerar.value }, {
        preserveScroll: true,
        onFinish: () => { generando.value = false; },
    });
};

const estadoCuentaInfo = {
    adeudo: { label: 'Adeudo', class: 'text-red-600' },
    pagado: { label: 'Pagado', class: 'text-emerald-600' },
    excedente: { label: 'Pago excedente', class: 'text-violet-600' },
};

// Clic en cualquier parte de la fila abre el estado de cuenta de la renta.
const irAEstadoCuenta = (item) => {
    router.visit(route('rentas.show', item.id));
};

const tarjetas = [
    { key: 'rentas', label: 'Rentas activas', color: 'from-[#7c3aed] to-[#c026d3]', money: false },
    { key: 'cobrado', label: 'Total cobrado', color: 'from-emerald-500 to-teal-500', money: true },
    { key: 'por_cobrar', label: 'Por cobrar (adeudo)', color: 'from-rose-500 to-red-500', money: true },
    { key: 'recargos', label: 'Recargos por mora', color: 'from-amber-500 to-orange-500', money: true },
];
</script>

<template>
    <Head title="Rentas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Rentas y control de pagos</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <div v-if="page.props.flash?.success" class="rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ page.props.flash.error }}
            </div>

            <!-- Resumen de cartera -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="t in tarjetas" :key="t.key" :class="['rounded-2xl bg-gradient-to-r p-5 text-white shadow-lg', t.color]">
                    <p class="text-xs font-semibold uppercase tracking-wider opacity-90">{{ t.label }}</p>
                    <p class="mt-2 text-2xl font-extrabold">
                        {{ t.money ? currency(resumen[t.key]) : (resumen[t.key] ?? 0) }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <form @submit.prevent="submitFilters" class="flex w-full max-w-xl gap-2">
                    <input v-model="search" type="search" placeholder="Buscar por inquilino…" class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]" />
                    <select v-model="estado" @change="submitFilters" class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]">
                        <option value="">Todos los estados</option>
                        <option value="al_corriente">Al corriente</option>
                        <option value="con_adeudo">Con adeudo</option>
                        <option value="pagada">Pagada</option>
                    </select>
                    <button type="submit" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Buscar</button>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="periodoGenerar"
                        type="month"
                        title="Mes/año a generar"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"
                    />
                    <button
                        type="button"
                        @click="generarMensualidades"
                        :disabled="generando"
                        title="Genera la renta de cada arrendatario (cuenta por cobrar) del mes/año elegido; no duplica lo que ya exista."
                        class="inline-flex items-center justify-center rounded-xl border border-[#7c3aed] px-4 py-2 text-sm font-semibold text-[#7c3aed] hover:bg-violet-50 disabled:opacity-50"
                    >
                        ⟳ Generar rentas del mes
                    </button>
                    <Link :href="route('rentas.reporte')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Reporte
                    </Link>
                    <Link :href="route('rentas.create')" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-4 py-2 text-sm font-semibold text-white shadow-lg hover:opacity-90">
                        + Nueva renta
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Inquilino</th>
                            <th class="px-6 py-3">Propiedad</th>
                            <th class="px-6 py-3">Renta mensual</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Estado de cuenta</th>
                            <th class="px-6 py-3">Recargos</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="item in rentas" :key="item.id" @click="irAEstadoCuenta(item)" class="cursor-pointer text-sm text-slate-700 hover:bg-violet-50/60">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ item.inquilino }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ item.propiedad?.nombre ?? '—' }}</td>
                            <td class="px-6 py-4">
                                {{ currency(item.monto_mensual) }}
                                <span v-if="item.tiene_iva" class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-violet-700" :title="`Total con IVA: ${currency(item.monto_con_iva)}`">+IVA</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['rounded-full px-3 py-1 text-xs font-semibold', item.estado_pago === 'al_corriente' ? 'bg-emerald-100 text-emerald-700' : item.estado_pago === 'con_adeudo' ? 'bg-red-100 text-red-700' : 'bg-violet-100 text-violet-700']">
                                    {{ estados[item.estado_pago] ?? item.estado_pago }}
                                </span>
                                <span v-if="item.periodos_vencidos > 0" class="ml-2 rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                                    {{ item.periodos_vencidos }} venc.
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="estadoCuentaInfo[item.estado_cuenta]?.class">
                                <span>{{ estadoCuentaInfo[item.estado_cuenta]?.label ?? item.estado_cuenta }}</span>
                                <span class="block text-xs font-normal text-slate-400">
                                    {{ item.estado_cuenta === 'excedente' ? currency(item.excedente) + ' a favor' : currency(item.saldo_cuenta) }}
                                </span>
                            </td>
                            <td class="px-6 py-4" :class="Number(item.total_recargos) > 0 ? 'text-amber-600' : 'text-slate-400'">{{ currency(item.total_recargos) }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap" @click.stop>
                                <Link :href="route('rentas.show', item.id)" class="font-semibold text-[#7c3aed] hover:text-[#c026d3]">Estado de cuenta</Link>
                                <Link :href="route('rentas.edit', item.id)" class="ml-4 font-semibold text-slate-500 hover:text-slate-800">Editar</Link>
                                <button @click="destroy(item)" class="ml-4 font-semibold text-red-500 hover:text-red-700">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="rentas.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-400">Aún no hay rentas registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
