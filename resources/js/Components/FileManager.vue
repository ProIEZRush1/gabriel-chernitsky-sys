<script setup>
import { ref, computed } from 'vue';
import { fileToEntry, kind, iconFor, formatSize, downloadEntry, decodeText, encodeText } from '@/lib/files';
import { confirmDelete, promptText } from '@/lib/swal';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    title: { type: String, default: 'Archivos' },
    hint: { type: String, default: 'Sube varios archivos para tenerlos en prevista y descargarlos en su formato (PDF, Word, Excel u otro).' },
});
const emit = defineEmits(['update:modelValue']);

const files = computed(() => props.modelValue || []);
const dragOver = ref(false);

// vista previa
const preview = ref(null); // entry
const editText = ref('');
const isEditing = ref(false);
const replaceInput = ref(null);

function update(list) {
    emit('update:modelValue', list);
}

async function addFiles(fileList) {
    const entries = [];
    for (const f of Array.from(fileList)) {
        entries.push(await fileToEntry(f));
    }
    update([...(props.modelValue || []), ...entries]);
}

function onPick(e) {
    if (e.target.files?.length) addFiles(e.target.files);
    e.target.value = '';
}
function onDrop(e) {
    dragOver.value = false;
    if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files);
}

function open(entry) {
    preview.value = entry;
    isEditing.value = false;
    if (kind(entry) === 'text') {
        editText.value = decodeText(entry.dataUrl);
    }
}
function close() {
    preview.value = null;
    isEditing.value = false;
}

async function remove(entry) {
    const ok = await confirmDelete({
        title: 'Eliminar archivo',
        text: `Se eliminará "${entry.name}".`,
    });
    if (!ok) return;
    update((props.modelValue || []).filter((f) => f.id !== entry.id));
    if (preview.value?.id === entry.id) close();
}

async function rename(entry) {
    const name = await promptText({
        title: 'Renombrar archivo',
        inputLabel: 'Nuevo nombre del archivo',
        inputValue: entry.name,
        confirmButtonText: 'Renombrar',
    });
    if (name == null || !name.trim()) return;
    patch(entry.id, { name: name.trim() });
}

function patch(id, changes) {
    const list = (props.modelValue || []).map((f) =>
        f.id === id ? { ...f, ...changes, updatedAt: new Date().toISOString() } : f,
    );
    update(list);
    if (preview.value?.id === id) preview.value = { ...preview.value, ...changes };
}

function saveText() {
    const type = preview.value.type && preview.value.type.startsWith('text') ? preview.value.type : 'text/plain';
    patch(preview.value.id, { dataUrl: encodeText(editText.value, type), size: new Blob([editText.value]).size });
    isEditing.value = false;
}

async function onReplace(e) {
    if (!e.target.files?.length) return;
    const entry = await fileToEntry(e.target.files[0]);
    patch(preview.value.id, { name: entry.name, type: entry.type, size: entry.size, dataUrl: entry.dataUrl });
    if (kind(preview.value) === 'text') editText.value = decodeText(entry.dataUrl);
    e.target.value = '';
}

const previewKind = computed(() => (preview.value ? kind(preview.value) : null));
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-700">{{ title }} <span class="text-slate-400">({{ files.length }})</span></h4>
        </div>

        <!-- Zona de carga -->
        <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-4 py-6 text-center transition"
            :class="dragOver ? 'border-[#7c3aed] bg-fuchsia-50' : 'border-slate-300 bg-slate-50 hover:border-[#7c3aed]'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <span class="text-2xl">⬆️</span>
            <span class="mt-1 text-sm font-semibold text-slate-700">Arrastra o haz clic para subir archivos</span>
            <span class="mt-0.5 text-xs text-slate-400">{{ hint }}</span>
            <input type="file" multiple class="hidden" @change="onPick" />
        </label>

        <!-- Cuadrícula de archivos -->
        <div v-if="files.length" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            <div
                v-for="f in files"
                :key="f.id"
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <button type="button" class="block w-full" @click="open(f)">
                    <div class="flex h-28 items-center justify-center bg-slate-50">
                        <img v-if="kind(f) === 'image'" :src="f.dataUrl" class="h-28 w-full object-cover" :alt="f.name" />
                        <span v-else class="text-4xl">{{ iconFor(f) }}</span>
                    </div>
                    <div class="px-2 py-2 text-left">
                        <p class="truncate text-xs font-semibold text-slate-700" :title="f.name">{{ f.name }}</p>
                        <p class="text-[11px] text-slate-400">{{ formatSize(f.size) }}</p>
                    </div>
                </button>
                <div class="flex border-t border-slate-100 text-[11px]">
                    <button type="button" class="flex-1 py-1.5 font-semibold text-[#7c3aed] hover:bg-slate-50" @click="open(f)">Ver</button>
                    <button type="button" class="flex-1 border-l border-slate-100 py-1.5 font-semibold text-slate-600 hover:bg-slate-50" @click="downloadEntry(f)">Descargar</button>
                    <button type="button" class="flex-1 border-l border-slate-100 py-1.5 font-semibold text-red-500 hover:bg-red-50" @click="remove(f)">✕</button>
                </div>
            </div>
        </div>

        <!-- Modal de prevista -->
        <div v-if="preview" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 p-4" @click.self="close">
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-800">{{ iconFor(preview) }} {{ preview.name }}</p>
                        <p class="text-xs text-slate-400">{{ formatSize(preview.size) }} · {{ preview.type || 'archivo' }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100" @click="close">✕</button>
                </div>

                <div class="flex-1 overflow-auto bg-slate-50 p-4">
                    <img v-if="previewKind === 'image'" :src="preview.dataUrl" class="mx-auto max-h-[60vh] rounded-lg object-contain" :alt="preview.name" />
                    <iframe v-else-if="previewKind === 'pdf'" :src="preview.dataUrl" class="h-[65vh] w-full rounded-lg border border-slate-200 bg-white"></iframe>
                    <div v-else-if="previewKind === 'text'">
                        <textarea
                            v-model="editText"
                            :readonly="!isEditing"
                            class="h-[55vh] w-full rounded-lg border-slate-300 font-mono text-xs shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]"
                            :class="isEditing ? 'bg-white' : 'bg-slate-100'"
                        ></textarea>
                    </div>
                    <div v-else class="flex h-[45vh] flex-col items-center justify-center text-center">
                        <span class="text-6xl">{{ iconFor(preview) }}</span>
                        <p class="mt-3 text-sm font-semibold text-slate-600">Vista previa no disponible para este formato</p>
                        <p class="text-xs text-slate-400">Puedes descargarlo en su formato original o reemplazarlo.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-5 py-3">
                    <template v-if="previewKind === 'text'">
                        <button v-if="!isEditing" type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="isEditing = true">✏️ Editar</button>
                        <button v-else type="button" class="rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-3 py-1.5 text-sm font-semibold text-white hover:opacity-90" @click="saveText">Guardar cambios</button>
                    </template>
                    <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="rename(preview)">Renombrar</button>
                    <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="replaceInput.click()">Reemplazar</button>
                    <input ref="replaceInput" type="file" class="hidden" @change="onReplace" />
                    <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-[#7c3aed] hover:bg-slate-50" @click="downloadEntry(preview)">Descargar</button>
                    <button type="button" class="rounded-xl px-3 py-1.5 text-sm font-semibold text-red-500 hover:bg-red-50" @click="remove(preview)">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</template>
