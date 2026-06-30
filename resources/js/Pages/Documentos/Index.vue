<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppFooter from '@/Components/AppFooter.vue';
import FileManager from '@/Components/FileManager.vue';
import { useStored, uid } from '@/lib/store';
import { encodeText } from '@/lib/files';
import { promptText } from '@/lib/swal';

const documentos = useStored('documentos', () => []);

// Crear un documento de texto en línea (editable desde la prevista)
async function nuevoTexto() {
    const nombre = await promptText({
        title: 'Nuevo documento de texto',
        inputLabel: 'Nombre del documento (ej. Contrato.txt)',
        inputValue: 'Nuevo documento.txt',
        inputPlaceholder: 'Nuevo documento.txt',
        confirmButtonText: 'Crear documento',
    });
    if (nombre == null) return;
    const name = nombre.trim() || 'Nuevo documento.txt';
    const finalName = /\.(txt|md|csv|html|json)$/i.test(name) ? name : name + '.txt';
    documentos.value = [
        ...documentos.value,
        {
            id: uid(),
            name: finalName,
            type: 'text/plain',
            size: 0,
            dataUrl: encodeText('', 'text/plain'),
            updatedAt: new Date().toISOString(),
        },
    ];
}
</script>

<template>
    <Head title="Documentos en línea" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold tracking-tight text-slate-800">Documentos en línea</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-5">
            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <p class="max-w-2xl text-sm text-slate-600">
                    Sube documentos para tenerlos en prevista y descargarlos en su formato original
                    (PDF, Word, Excel u otro). También puedes crear un documento de texto en línea y editarlo aquí mismo.
                </p>
                <button type="button" class="shrink-0 rounded-xl border border-[#7c3aed] bg-white px-4 py-2 text-sm font-semibold text-[#7c3aed] hover:bg-fuchsia-50" @click="nuevoTexto">
                    + Documento de texto en línea
                </button>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <FileManager v-model="documentos" title="Biblioteca de documentos" />
            </div>

            <AppFooter />
        </div>
    </AuthenticatedLayout>
</template>
