import { chromium } from 'playwright';

const BASE = process.env.SMOKE_BASE_URL || 'http://127.0.0.1:8099';
const EMAIL = 'gabriel-chernitsky@overcloud.us';
const PASSWORD = '0YGkzPIoQ0td';

function ok(m) { console.log(`\x1b[32m✓\x1b[0m ${m}`); }
function log(m) { console.log(`\x1b[36m▶\x1b[0m ${m}`); }

async function expectText(page, text, ctx) {
    try {
        await page.waitForFunction((t) => document.body && document.body.textContent.includes(t), text, { timeout: 10000 });
    } catch {
        const body = await page.evaluate(() => document.body.textContent).catch(() => '');
        throw new Error(`[${ctx}] No se encontró "${text}". URL: ${page.url()}\n${body.slice(0, 500)}`);
    }
}

async function run() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text()); });

    // LOGIN
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await Promise.all([
        page.waitForURL('**/dashboard', { timeout: 15000 }),
        page.click('button:has-text("Iniciar sesión")'),
    ]);
    ok('Login correcto');

    // SIDEBAR: las 6 nuevas entradas
    for (const label of ['Clientes / arrendatarios', 'Mantenimiento', 'Auxiliar bancario', 'Reportes', 'Documentos', 'Configuraciones']) {
        const count = await page.locator(`aside >> text=${label}`).count();
        if (count < 1) throw new Error(`Falta en el menú lateral: ${label}`);
    }
    ok('Menú lateral con las 6 entradas nuevas');

    // CONFIGURACIONES: cada lista tiene tabla; el footer Overcloud aparece
    await page.goto(`${BASE}/configuraciones`, { waitUntil: 'networkidle' });
    await expectText(page, 'Categoría', 'configuraciones');
    await expectText(page, 'Tipo de movimiento', 'configuraciones');
    await expectText(page, 'Desarrollado por', 'configuraciones');
    const ovLink = await page.locator('a[href="https://wa.me/5215594356241"]').count();
    if (ovLink < 1) throw new Error('Falta el enlace de WhatsApp de Overcloud');
    ok('Configuraciones: tablas por columna + footer Overcloud con WhatsApp');

    // CONFIGURACIONES: agregar valor nuevo a "Categoría"
    const catCard = page.locator('div', { has: page.locator('h3:text-is("Categoría")') }).last();
    await catCard.locator('input[placeholder="Agregar valor…"]').fill('Servicios Especiales XYZ');
    await catCard.locator('button:text-is("+")').click();
    await expectText(page, 'Servicios Especiales XYZ', 'configuraciones-add');
    ok('Configuraciones: se agregó "Servicios Especiales XYZ" a Categoría');

    // CONFIGURACIONES: dato muy parecido dispara aviso Aceptar/Cancelar
    await catCard.locator('input[placeholder="Agregar valor…"]').fill('Servicios Especiales XYS');
    await catCard.locator('button:text-is("+")').click();
    await expectText(page, 'se parece a', 'fuzzy');
    if (await page.locator('button:has-text("Aceptar y agregar")').count() < 1) throw new Error('No hay botón Aceptar');
    if (await page.locator('button:text-is("Cancelar")').count() < 1) throw new Error('No hay botón Cancelar');
    await page.locator('button:text-is("Cancelar")').click();
    ok('Aviso de parecido con Aceptar/Cancelar funciona (cancelado)');

    // AUXILIAR: nuevo renglón y dropdown con la categoría recién creada
    await page.goto(`${BASE}/auxiliar`, { waitUntil: 'networkidle' });
    await page.click('button:has-text("+ Nuevo renglón")');
    await expectText(page, 'Saldo', 'auxiliar');
    // la opción nueva de Configuraciones debe existir en el datalist de la celda Categoría
    const hasOpt = await page.evaluate(() =>
        !!Array.from(document.querySelectorAll('datalist option')).find((o) => o.value === 'Servicios Especiales XYZ'));
    if (!hasOpt) throw new Error('La categoría creada en Configuraciones no llegó al Auxiliar');
    ok('Auxiliar: renglón nuevo + la lista de Categoría se sincroniza desde Configuraciones');

    // AUXILIAR: capturar ingreso/egreso y ver totales
    const inputs = page.locator('table tbody tr').first().locator('input');
    await page.locator('table tbody tr').first().locator('input[type="number"]').first().fill('5000');
    await page.locator('table tbody tr').first().locator('input[type="number"]').nth(1).fill('1200');
    await page.click('h2'); // blur
    await expectText(page, 'Totales', 'auxiliar-totales');
    ok('Auxiliar: ingreso/egreso capturados y fila de Totales visible');

    // CLIENTES: crear ficha
    await page.goto(`${BASE}/clientes`, { waitUntil: 'networkidle' });
    await page.click('button:has-text("+ Nuevo cliente")');
    await expectText(page, 'Datos generales', 'clientes-form');
    await expectText(page, 'Documentos y archivos', 'clientes-form');
    await page.fill('input[type="text"] >> nth=0', 'Juan Pérez E2E');
    await page.click('button:text-is("Guardar")');
    await expectText(page, 'Juan Pérez E2E', 'clientes-guardado');
    ok('Clientes: ficha con muchos campos + FileManager, guardado OK');

    // MANTENIMIENTO: crear registro
    await page.goto(`${BASE}/mantenimiento`, { waitUntil: 'networkidle' });
    await page.click('button:has-text("+ Nuevo mantenimiento")');
    await expectText(page, 'Orden de mantenimiento', 'mant-form');
    await page.fill('input[type="text"] >> nth=0', 'Casa Polanco E2E');
    await page.click('button:text-is("Guardar")');
    await expectText(page, 'Casa Polanco E2E', 'mant-guardado');
    ok('Mantenimiento: registro creado');

    // DOCUMENTOS: zona de carga presente
    await page.goto(`${BASE}/documentos`, { waitUntil: 'networkidle' });
    await expectText(page, 'Biblioteca de documentos', 'documentos');
    await expectText(page, 'Arrastra o haz clic', 'documentos');
    ok('Documentos: biblioteca con carga de archivos');

    // REPORTES: tarjetas con datos del servidor y del cliente
    await page.goto(`${BASE}/reportes`, { waitUntil: 'networkidle' });
    await expectText(page, 'Propiedades', 'reportes');
    await expectText(page, 'Auxiliar por categoría', 'reportes');
    await expectText(page, 'Desarrollado por', 'reportes');
    ok('Reportes: consolidado de todos los datos + footer Overcloud');

    if (errors.length) {
        throw new Error('Errores de página detectados:\n' + errors.join('\n'));
    }
    ok('Sin errores de consola ni 500');

    await browser.close();
    console.log('\n\x1b[32mTODO OK\x1b[0m');
}

run().catch((e) => { console.error('\x1b[31m✗ FALLO:\x1b[0m', e.message); process.exit(1); });
