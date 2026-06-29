import { chromium } from 'playwright';

const BASE = process.env.SMOKE_BASE_URL || 'http://127.0.0.1:8123';
const EMAIL = 'gabriel-chernitsky@overcloud.us';
const PASSWORD = '0YGkzPIoQ0td';
const stamp = Date.now().toString().slice(-6);

function log(msg) {
    console.log(`\x1b[36m▶\x1b[0m ${msg}`);
}
function ok(msg) {
    console.log(`\x1b[32m✓\x1b[0m ${msg}`);
}

async function expectText(page, text, context) {
    try {
        await page.waitForFunction(
            (t) => document.body && document.body.textContent.includes(t),
            text,
            { timeout: 10000 },
        );
    } catch {
        const body = await page.evaluate(() => document.body.textContent).catch(() => '');
        throw new Error(
            `[${context}] No se encontró "${text}" en la página. URL: ${page.url()}\n--- inicio del body ---\n${body.slice(0, 600)}`,
        );
    }
}

async function run() {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    page.on('pageerror', (e) => console.error('PAGE ERROR:', e.message));

    // ---------- LOGIN ----------
    log('Abriendo /login');
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
    await expectText(page, 'Gabriel Chernitsky', 'login');

    await page.fill('#email', EMAIL);
    await page.fill('#password', PASSWORD);
    await Promise.all([
        page.waitForURL('**/dashboard', { timeout: 15000 }),
        page.click('button:has-text("Iniciar sesión")'),
    ]);
    await expectText(page, 'Gabriel Chernitsky', 'dashboard');
    if (!page.url().includes('/dashboard')) {
        throw new Error('No se llegó al dashboard tras iniciar sesión.');
    }
    ok('Login correcto, dashboard visible con "Gabriel Chernitsky"');

    // ---------- Definición de módulos ----------
    const modules = [
        {
            name: 'Propiedades',
            path: '/propiedades',
            createLink: '+ Nueva propiedad',
            unique: `Propiedad E2E ${stamp}`,
            fill: async (p, val) => {
                await p.fill('#nombre', val);
                await p.selectOption('#tipo', 'departamento');
                await p.fill('#direccion', `Calle E2E ${stamp}`);
                await p.fill('#ciudad', 'CDMX');
                await p.fill('#valor_comercial', '1500000');
                await p.selectOption('#estado', 'disponible');
            },
            submit: 'Guardar propiedad',
        },
        {
            name: 'Seguros',
            path: '/seguros',
            createLink: '+ Nueva póliza',
            unique: `Asegurado E2E ${stamp}`,
            fill: async (p, val) => {
                await p.selectOption('#ramo', 'auto');
                await p.fill('#asegurado', val);
                await p.fill('#aseguradora', 'Qualitas E2E');
                await p.fill('#numero_poliza', `POL-${stamp}`);
                await p.fill('#suma_asegurada', '600000');
                await p.fill('#prima', '12000');
            },
            submit: 'Guardar póliza',
        },
        {
            name: 'Rentas',
            path: '/rentas',
            createLink: '+ Nueva renta',
            unique: `Inquilino E2E ${stamp}`,
            fill: async (p, val) => {
                await p.fill('#inquilino', val);
                await p.fill('#monto_mensual', '18000');
                await p.fill('#dia_pago', '5');
                await p.selectOption('#estado_pago', 'con_adeudo');
                await p.fill('#tasa_moratoria', '5');
                await p.fill('#meses_adeudo', '3');
            },
            submit: 'Guardar renta',
        },
        {
            name: 'Auxiliar bancario',
            path: '/movimientos',
            createLink: '+ Nuevo movimiento',
            unique: `Movimiento E2E ${stamp}`,
            fill: async (p, val) => {
                await p.fill('#auxiliar', 'Cuenta E2E');
                await p.selectOption('#tipo', 'cobro');
                await p.fill('#concepto', val);
                await p.fill('#monto', '18000');
                await p.fill('#referencia', `REF-${stamp}`);
            },
            submit: 'Guardar movimiento',
        },
    ];

    for (const mod of modules) {
        log(`Módulo ${mod.name}: abriendo listado`);
        await page.goto(`${BASE}${mod.path}`, { waitUntil: 'networkidle' });

        log(`Módulo ${mod.name}: creando registro vía UI`);
        await Promise.all([
            page.waitForURL(`**${mod.path}/create`, { timeout: 15000 }),
            page.click(`a:has-text("${mod.createLink}")`),
        ]);

        await mod.fill(page, mod.unique);

        await Promise.all([
            page.waitForURL(`**${mod.path}`, { timeout: 15000 }),
            page.click(`button:has-text("${mod.submit}")`),
        ]);

        await expectText(page, mod.unique, `${mod.name} (tras guardar)`);
        ok(`${mod.name}: registro "${mod.unique}" aparece en la tabla`);

        // ---------- Persistencia: recargar ----------
        log(`Módulo ${mod.name}: recargando para validar persistencia`);
        await page.reload({ waitUntil: 'networkidle' });
        await expectText(page, mod.unique, `${mod.name} (tras recargar)`);
        ok(`${mod.name}: el registro persiste tras recargar (BD real)`);
    }

    // ---------- Anti-genérico ----------
    log('Verificando ausencia de marca genérica Laravel');
    const dashHtml = await (await fetch(`${BASE}/login`)).text();
    if (/laravel/i.test(dashHtml)) {
        throw new Error('La palabra "Laravel" aparece en el HTML de /login.');
    }
    ok('Sin rastros de "Laravel" en /login');

    await browser.close();
    console.log('\n\x1b[42m\x1b[30m  TODAS LAS PRUEBAS E2E PASARON  \x1b[0m\n');
}

run().catch((err) => {
    console.error('\n\x1b[41m\x1b[37m  PRUEBA E2E FALLIDA  \x1b[0m');
    console.error(err);
    process.exit(1);
});
