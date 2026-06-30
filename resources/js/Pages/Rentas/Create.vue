<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    propiedades: { type: Array, default: () => [] },
});

const form = useForm({
    propiedad_id: null,
    inquilino: '',
    monto_mensual: '',
    dia_pago: 1,
    dias_gracia: 0,
    fecha_inicio: '',
    fecha_vencimiento_renta: '',
    estado_pago: 'al_corriente',
    tasa_moratoria: 0,
    recargo_fijo: 0,
    porcentaje_aumento: 0,
    inflacion_periodo: 0,
    fecha_ultimo_aumento: '',
    meses_adeudo: 0,
    notas: '',
});

const submit = () => form.post(route('rentas.store'));
</script>

<template>
    <Head title="Nueva renta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nueva renta</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :propiedades="propiedades" submit-label="Guardar renta" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
