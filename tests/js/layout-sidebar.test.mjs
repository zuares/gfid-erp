import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { JSDOM } from 'jsdom';

const root = fileURLToPath(new URL('../../', import.meta.url));

// Use an HTML5 parser: DOMDocument::loadHTML uses different recovery rules
// and misses the nested-list bug that pushes the shell outside #app.
for (const role of ['operating', 'owner', 'admin']) {
    test(`${role}: sidebar and content stay inside their layout containers`, () => {
        const html = execFileSync('php', ['-r', `
            require 'vendor/autoload.php';
            $app = require 'bootstrap/app.php';
            $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
            config(['cache.default' => 'array', 'session.driver' => 'array']);
            $user = new App\\Models\\User(['name' => 'Layout audit', 'role' => $argv[1]]);
            auth()->setUser($user);
            echo view('layouts.app')->render();
        `, role], { cwd: root, encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
        const document = new JSDOM(html).window.document;
        const app = document.querySelector('#app');
        const drawer = document.querySelector('#mobileSidebarPanel');

        assert.equal(document.querySelector('.app-shell').parentElement, app,
            'main shell must not escape #app during HTML parsing');
        assert.equal(document.querySelector('.mobile-sidebar-footer').parentElement, drawer,
            'drawer footer must not escape into normal page flow');
        assert.equal(document.querySelector('.app-main').closest('#app'), app);

        if (role === 'operating') {
            const production = document.querySelector('#navProductionOperatingMobile');
            assert.ok(production.querySelector('a[href$="/production/cutting-jobs"]'),
                'Cutting Jobs must stay inside the collapsible production group');
            assert.ok(production.querySelector('a[href$="/production/sewing/pickups"]'));
        }
    });
}
