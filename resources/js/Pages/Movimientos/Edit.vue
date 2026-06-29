<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    movimiento: { type: Object, required: true },
    rentas: { type: Array, default: () => [] },
});

const form = useForm({
    auxiliar: props.movimiento.auxiliar ?? '',
    tipo: props.movimiento.tipo ?? 'cobro',
    concepto: props.movimiento.concepto ?? '',
    monto: props.movimiento.monto ?? '',
    fecha: props.movimiento.fecha ? String(props.movimiento.fecha).substring(0, 10) : '',
    referencia: props.movimiento.referencia ?? '',
    renta_id: props.movimiento.renta_id ?? null,
});

const submit = () => form.put(route('movimientos.update', props.movimiento.id));
</script>

<template>
    <Head title="Editar movimiento" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Editar movimiento bancario</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :rentas="rentas" submit-label="Actualizar movimiento" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
