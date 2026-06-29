import { useStored } from './store';

// Cada lista define los valores que se pueden elegir en una celda del Auxiliar
// bancario. Su `key` se usa como nombre de la "tabla" en Configuraciones.
export const AUX_LISTS = [
    { key: 'auxiliares', label: 'Auxiliar', seed: ['Cuenta BBVA', 'Cuenta Santander', 'Caja chica'] },
    { key: 'proyectos', label: 'Proyecto', seed: ['Edificio Reforma', 'Casa Polanco', 'Local Roma'] },
    { key: 'categorias', label: 'Categoría', seed: ['Renta', 'Mantenimiento', 'Servicios', 'Impuestos', 'Seguros', 'Nómina'] },
    { key: 'subcategorias', label: 'Subcategoría', seed: ['Agua', 'Luz', 'Gas', 'Predial', 'Limpieza', 'Jardinería'] },
    { key: 'conceptos', label: 'Concepto', seed: ['Pago de renta', 'Depósito en garantía', 'Reparación', 'Comisión'] },
    { key: 'cuentas', label: 'Cuenta / Banco', seed: ['BBVA ****1234', 'Santander ****5678', 'Efectivo'] },
    { key: 'tipos', label: 'Tipo de movimiento', seed: ['Pago', 'Transferencia', 'Cobro', 'Depósito', 'Retiro'] },
    { key: 'metodos', label: 'Método de pago', seed: ['Efectivo', 'Transferencia SPEI', 'Tarjeta', 'Cheque', 'Domiciliación'] },
    { key: 'beneficiarios', label: 'Beneficiario / Proveedor', seed: ['CFE', 'SACMEX', 'Arrendatario', 'Proveedor de limpieza'] },
    { key: 'referencias', label: 'Referencia', seed: ['SPEI', 'Factura', 'Recibo', 'Contrato'] },
    { key: 'estatus', label: 'Estatus', seed: ['Pendiente', 'Pagado', 'Conciliado', 'Cancelado'] },
];

// Columnas del Auxiliar. Las de tipo `list` toman sus opciones de una lista de
// Configuraciones. Fecha, Ingreso (abono) y Egreso (cargo) NO llevan lista.
export const AUX_COLUMNS = [
    { key: 'fecha', label: 'Fecha', type: 'date', width: 'w-36' },
    { key: 'auxiliar', label: 'Auxiliar', type: 'list', list: 'auxiliares' },
    { key: 'proyecto', label: 'Proyecto', type: 'list', list: 'proyectos' },
    { key: 'categoria', label: 'Categoría', type: 'list', list: 'categorias' },
    { key: 'subcategoria', label: 'Subcategoría', type: 'list', list: 'subcategorias' },
    { key: 'concepto', label: 'Concepto', type: 'list', list: 'conceptos' },
    { key: 'cuenta', label: 'Cuenta / Banco', type: 'list', list: 'cuentas' },
    { key: 'tipo', label: 'Tipo de movimiento', type: 'list', list: 'tipos' },
    { key: 'metodo', label: 'Método de pago', type: 'list', list: 'metodos' },
    { key: 'beneficiario', label: 'Beneficiario / Proveedor', type: 'list', list: 'beneficiarios' },
    { key: 'referencia', label: 'Referencia', type: 'list', list: 'referencias' },
    { key: 'estatus', label: 'Estatus', type: 'list', list: 'estatus' },
    { key: 'ingreso', label: 'Ingreso (abono)', type: 'money' },
    { key: 'egreso', label: 'Egreso (cargo)', type: 'money' },
];

export const LIST_BY_KEY = Object.fromEntries(AUX_LISTS.map((l) => [l.key, l]));

function defaultLists() {
    const obj = {};
    for (const l of AUX_LISTS) obj[l.key] = [...l.seed];
    return obj;
}

// Ref compartido con todas las listas. Se asegura de que cada lista exista.
export function useLists() {
    const lists = useStored('lists', defaultLists);
    let changed = false;
    for (const l of AUX_LISTS) {
        if (!Array.isArray(lists.value[l.key])) {
            lists.value[l.key] = [...l.seed];
            changed = true;
        }
    }
    if (changed) lists.value = { ...lists.value };
    return lists;
}

export function blankRow() {
    const row = { id: 'id-' + Math.random().toString(36).slice(2, 10) + Date.now().toString(36) };
    for (const c of AUX_COLUMNS) row[c.key] = c.type === 'money' ? '' : '';
    return row;
}
