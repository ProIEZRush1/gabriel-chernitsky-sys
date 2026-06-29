<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    renta: { type: Object, required: true },
    propiedades: { type: Array, default: () => [] },
});

const form = useForm({
    propiedad_id: props.renta.propiedad_id ?? null,
    inquilino: props.renta.inquilino ?? '',
    monto_mensual: props.renta.monto_mensual ?? '',
    dia_pago: props.renta.dia_pago ?? 1,
    fecha_inicio: props.renta.fecha_inicio ? String(props.renta.fecha_inicio).substring(0, 10) : '',
    estado_pago: props.renta.estado_pago ?? 'al_corriente',
    tasa_moratoria: props.renta.tasa_moratoria ?? 0,
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
