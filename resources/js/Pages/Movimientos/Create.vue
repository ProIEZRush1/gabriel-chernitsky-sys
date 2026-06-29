<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    rentas: { type: Array, default: () => [] },
});

const form = useForm({
    auxiliar: '',
    tipo: 'cobro',
    concepto: '',
    monto: '',
    fecha: '',
    referencia: '',
    renta_id: null,
});

const submit = () => form.post(route('movimientos.store'));
</script>

<template>
    <Head title="Nuevo movimiento" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nuevo movimiento bancario</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :rentas="rentas" submit-label="Guardar movimiento" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
