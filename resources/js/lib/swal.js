// Diálogos bonitos con SweetAlert2, con el estilo de marca del sistema
// (violeta #7c3aed → fucsia #c026d3). Reemplazan a alert/confirm/prompt nativos.
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.css';

const BTN_BRAND =
    'inline-flex items-center rounded-xl bg-gradient-to-r from-[#7c3aed] to-[#c026d3] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-600/30 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2';
const BTN_DANGER =
    'inline-flex items-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-600/30 hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2';
const BTN_CANCEL =
    'inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2';
const INPUT =
    'mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-[#7c3aed] focus:ring-[#7c3aed]';

function classes(confirmButton) {
    return {
        popup: 'rounded-2xl shadow-2xl',
        title: '!text-lg !font-bold !text-slate-800',
        htmlContainer: '!text-sm !text-slate-600',
        actions: 'gap-2',
        confirmButton,
        cancelButton: BTN_CANCEL,
        input: INPUT,
    };
}

// Base reutilizable con botones sin estilos nativos de Swal
const base = (extra = {}) => ({
    buttonsStyling: false,
    reverseButtons: true,
    heightAuto: false,
    ...extra,
});

// --- Avisos (reemplazan a alert) -------------------------------------------
export function alertWarning(message, title = 'Atención') {
    return Swal.fire(
        base({
            icon: 'warning',
            title,
            text: message,
            confirmButtonText: 'Entendido',
            customClass: classes(BTN_BRAND),
        }),
    );
}

export function alertInfo(message, title = 'Aviso') {
    return Swal.fire(
        base({
            icon: 'info',
            title,
            text: message,
            confirmButtonText: 'Entendido',
            customClass: classes(BTN_BRAND),
        }),
    );
}

// --- Toast de éxito (esquina superior) -------------------------------------
export function toastSuccess(message) {
    return Swal.fire(
        base({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2600,
            timerProgressBar: true,
            customClass: { popup: 'rounded-xl shadow-lg', title: '!text-sm !text-slate-700' },
        }),
    );
}

// --- Confirmación de borrado (botón rojo) ----------------------------------
export async function confirmDelete({
    title = '¿Eliminar?',
    text = '',
    html = undefined,
    confirmButtonText = 'Sí, eliminar',
    cancelButtonText = 'Cancelar',
} = {}) {
    const r = await Swal.fire(
        base({
            icon: 'warning',
            title,
            text,
            html,
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText,
            cancelButtonText,
            customClass: classes(BTN_DANGER),
        }),
    );
    return r.isConfirmed;
}

// --- Confirmación genérica (botón de marca) --------------------------------
export async function confirmAction({
    title = '¿Confirmar?',
    text = '',
    html = undefined,
    icon = 'question',
    confirmButtonText = 'Confirmar',
    cancelButtonText = 'Cancelar',
} = {}) {
    const r = await Swal.fire(
        base({
            icon,
            title,
            text,
            html,
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            customClass: classes(BTN_BRAND),
        }),
    );
    return r.isConfirmed;
}

// --- Captura de texto (reemplaza a prompt) ---------------------------------
// Devuelve el texto capturado, o null si se cancela.
export async function promptText({
    title = '',
    text = '',
    inputLabel = '',
    inputValue = '',
    inputPlaceholder = '',
    confirmButtonText = 'Guardar',
    cancelButtonText = 'Cancelar',
    inputValidator = undefined,
} = {}) {
    const r = await Swal.fire(
        base({
            title,
            text,
            input: 'text',
            inputLabel,
            inputValue,
            inputPlaceholder,
            showCancelButton: true,
            confirmButtonText,
            cancelButtonText,
            customClass: classes(BTN_BRAND),
            inputValidator,
        }),
    );
    if (!r.isConfirmed) return null;
    return r.value ?? '';
}

export default Swal;
