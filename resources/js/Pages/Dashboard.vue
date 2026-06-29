<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

const businessName = computed(() => page.props.name ?? 'Mi Negocio');
const userFirstName = computed(() => {
    const name = (page.props.auth?.user?.name ?? '').trim();
    return name ? name.split(/\s+/)[0] : '';
});

const currency = (value) =>
    new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));

const modules = computed(() => [
    {
        label: 'Propiedades',
        emoji: '🏢',
        count: props.metrics.propiedades ?? 0,
        hint: 'Inmuebles administrados',
        route: 'propiedades.index',
        gradient: 'from-[#7c3aed] to-[#a855f7]',
    },
    {
        label: 'Seguros',
        emoji: '🛡️',
        count: props.metrics.seguros ?? 0,
        hint: 'Pólizas inmueble, auto y médico',
        route: 'seguros.index',
        gradient: 'from-[#a21caf] to-[#c026d3]',
    },
    {
        label: 'Rentas',
        emoji: '🔑',
        count: props.metrics.rentas ?? 0,
        hint: 'Contratos de renta activos',
        route: 'rentas.index',
        gradient: 'from-[#7c3aed] to-[#c026d3]',
    },
    {
        label: 'Auxiliar bancario',
        emoji: '🏦',
        count: props.metrics.movimientos ?? 0,
        hint: 'Movimientos registrados',
        route: 'movimientos.index',
        gradient: 'from-[#c026d3] to-[#db2777]',
    },
]);

const finance = computed(() => [
    {
        label: 'Renta mensual contratada',
        value: currency(props.metrics.renta_mensual),
    },
    {
        label: 'Rentas con adeudo',
        value: props.metrics.rentas_con_adeudo ?? 0,
    },
    {
        label: 'Adeudo total (con moratorios)',
        value: currency(props.metrics.total_adeudo),
    },
    {
        label: 'Interés moratorio acumulado',
        value: currency(props.metrics.interes_moratorio),
    },
    {
        label: 'Suma asegurada total',
        value: currency(props.metrics.suma_asegurada),
    },
]);
</script>

<template>
    <Head title="Panel de control" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">
                Panel de control
            </h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-8">
            <!-- Hero -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7c3aed] to-[#c026d3] p-8 text-white shadow-xl shadow-fuchsia-500/20 sm:p-10"
            >
                <div
                    class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-fuchsia-300/20 blur-2xl"
                ></div>
                <div class="relative">
                    <p class="text-sm font-medium uppercase tracking-widest text-white/70">
                        Sistema de propiedades, rentas y seguros
                    </p>
                    <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">
                        Hola<span v-if="userFirstName">, {{ userFirstName }}</span> 👋
                    </h1>
                    <p class="mt-3 max-w-2xl text-base text-white/85">
                        Bienvenido al panel de
                        <span class="font-semibold">{{ businessName }}</span>.
                        Administra tus inmuebles, pólizas de seguro, contratos de renta
                        con interés moratorio y el auxiliar bancario, todo en un solo lugar.
                    </p>
                </div>
            </section>

            <!-- Module summary cards -->
            <section>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">
                    Tus módulos
                </h3>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="mod in modules"
                        :key="mod.label"
                        :href="route(mod.route)"
                        class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <div class="flex items-start justify-between">
                            <span
                                :class="[
                                    'flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br text-2xl shadow-md',
                                    mod.gradient,
                                ]"
                            >
                                {{ mod.emoji }}
                            </span>
                            <span
                                class="text-xs font-semibold text-[#7c3aed] opacity-0 transition group-hover:opacity-100"
                            >
                                Ver / Administrar →
                            </span>
                        </div>
                        <p class="mt-4 text-3xl font-extrabold text-slate-800">
                            {{ mod.count }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-slate-600">
                            {{ mod.label }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ mod.hint }}</p>
                    </Link>
                </div>
            </section>

            <!-- Finance overview -->
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm lg:col-span-2"
                >
                    <h3 class="text-lg font-bold text-slate-800">
                        Resumen financiero
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Cifras calculadas a partir de tus rentas y seguros registrados.
                    </p>
                    <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div
                            v-for="item in finance"
                            :key="item.label"
                            class="rounded-xl bg-slate-50 p-4"
                        >
                            <dt class="text-xs font-medium text-slate-500">
                                {{ item.label }}
                            </dt>
                            <dd class="mt-1 text-xl font-bold text-slate-800">
                                {{ item.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div
                    class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white shadow-sm"
                >
                    <div>
                        <h3 class="text-lg font-bold">Accesos rápidos</h3>
                        <p class="mt-2 text-sm text-slate-300">
                            Crea un nuevo registro en cualquier módulo.
                        </p>
                        <div class="mt-5 space-y-2">
                            <Link
                                :href="route('rentas.create')"
                                class="block rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/20"
                            >
                                + Nueva renta
                            </Link>
                            <Link
                                :href="route('seguros.create')"
                                class="block rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/20"
                            >
                                + Nueva póliza de seguro
                            </Link>
                            <Link
                                :href="route('movimientos.create')"
                                class="block rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/20"
                            >
                                + Nuevo movimiento bancario
                            </Link>
                        </div>
                    </div>
                    <p class="mt-6 text-xs text-slate-400">
                        Plataforma impulsada por
                        <span class="font-semibold text-slate-200">Overcloud</span>
                    </p>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
