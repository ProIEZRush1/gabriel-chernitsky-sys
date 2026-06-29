<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Form from './Form.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    propiedades: { type: Array, default: () => [] },
});

const form = useForm({
    ramo: 'inmueble',
    asegurado: '',
    beneficiario: '',
    aseguradora: '',
    numero_poliza: '',
    agente_venta: '',
    suma_asegurada: '',
    prima: '',
    condiciones: '',
    vigencia_inicio: '',
    vigencia_fin: '',
    estado: 'vigente',
    propiedad_id: null,
});

const submit = () => form.post(route('seguros.store'));
</script>

<template>
    <Head title="Nueva póliza" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Nueva póliza de seguro</h2>
        </template>

        <div class="mx-auto max-w-3xl">
            <Form :form="form" :propiedades="propiedades" submit-label="Guardar póliza" @submit="submit" />
        </div>
    </AuthenticatedLayout>
</template>
