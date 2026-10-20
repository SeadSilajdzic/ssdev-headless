import { existsSync, readdirSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { normalizePath, searchForWorkspaceRoot } from 'vite';
import { collectPages, renderPagesModule, resolveOverride } from './vite/overrides.js';

const ALIAS_PREFIX = '@headless/';
const PAGES_MODULE = 'virtual:headless/pages';
const RESOLVED_PAGES_MODULE = `\0${PAGES_MODULE}`;
const OVERRIDE_DIR = 'resources/js/vendor/headless';

const packageDir = normalizePath(dirname(fileURLToPath(import.meta.url)));

const isFile = (path) => existsSync(path) && statSync(path).isFile();

const listVueFiles = (dir) =>
    existsSync(dir)
        ? readdirSync(dir, { recursive: true })
              .map((file) => normalizePath(String(file)))
              .filter((file) => file.endsWith('.vue'))
        : [];

/**
 * @returns {import('vite').Plugin}
 */
export default function headless() {
    let overrideDir = '';

    return {
        name: 'ssdev-headless',

        config(config) {
            const root = config.root ?? process.cwd();

            return {
                resolve: { dedupe: ['vue', '@inertiajs/vue3', 'reka-ui'] },
                // A symlinked package lives outside the project root, so the dev server must be allowed to serve it.
                server: { fs: { allow: [searchForWorkspaceRoot(root), packageDir] } },
            };
        },

        configResolved(config) {
            overrideDir = normalizePath(join(config.root, OVERRIDE_DIR));
        },

        resolveId(source) {
            if (source === PAGES_MODULE) {
                return RESOLVED_PAGES_MODULE;
            }

            if (source.startsWith(ALIAS_PREFIX)) {
                return resolveOverride(source.slice(ALIAS_PREFIX.length), { packageDir, overrideDir, exists: isFile });
            }

            return null;
        },

        load(id) {
            if (id !== RESOLVED_PAGES_MODULE) {
                return null;
            }

            return renderPagesModule(
                collectPages({
                    packagePagesDir: `${packageDir}/pages`,
                    overridePagesDir: `${overrideDir}/pages`,
                    listVueFiles,
                }),
            );
        },

        configureServer(server) {
            server.watcher.add(overrideDir);

            const reload = (file) => {
                if (!normalizePath(file).startsWith(overrideDir)) {
                    return;
                }

                const module = server.moduleGraph.getModuleById(RESOLVED_PAGES_MODULE);

                if (module) {
                    server.moduleGraph.invalidateModule(module);
                }

                server.ws.send({ type: 'full-reload' });
            };

            server.watcher.on('add', reload);
            server.watcher.on('unlink', reload);
        },
    };
}
