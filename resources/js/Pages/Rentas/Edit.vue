<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    renta: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
});

const fecha = (v) => (v ? String(v).substring(0, 10) : '');

const form = useForm({
    propiedad_id: props.renta.propiedad_id ?? null,
    inquilino: props.renta.inquilino ?? '',
    monto_mensual: props.renta.monto_mensual ?? '',
    dia_pago: props.renta.dia_pago ?? 1,
    dias_gracia: props.renta.dias_gracia ?? 0,
    fecha_inicio: fecha(props.renta.fecha_inicio),
    fecha_vencimiento_renta: fecha(props.renta.fecha_vencimiento_renta),
    estado_pago: props.renta.estado_pago ?? 'al_corriente',
    tasa_moratoria: props.renta.tasa_moratoria ?? 0,
    recargo_fijo: props.renta.recargo_fijo ?? 0,
    porcentaje_aumento: props.renta.porcentaje_aumento ?? 0,
    inflacion_periodo: props.renta.inflacion_periodo ?? 0,
    fecha_ultimo_aumento: fecha(props.renta.fecha_ultimo_aumento),
    meses_adeudo: props.renta.meses_adeudo ?? 0,
    notas: props.renta.notas ?? '',
});

const submit = () => form.put(route('rentas.update', props.renta.id));
</script>

<template>
    <Head title="Editar renta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Editar renta</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :propiedades="propiedades" submit-label="Actualizar renta" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
