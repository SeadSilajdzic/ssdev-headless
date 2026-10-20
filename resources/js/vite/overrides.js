const EXTENSIONS = ['', '.ts', '.vue', '.js', '/index.ts'];

const normalize = (path) => path.replaceAll('\\', '/').replace(/\/+$/, '');

/**
 * @param {string} subpath
 * @param {{ packageDir: string, overrideDir: string, exists: (path: string) => boolean }} options
 * @returns {string | null}
 */
export function resolveOverride(subpath, { packageDir, overrideDir, exists }) {
    for (const dir of [overrideDir, packageDir]) {
        for (const extension of EXTENSIONS) {
            const candidate = `${normalize(dir)}/${normalize(subpath)}${extension}`;

            if (exists(candidate)) {
                return candidate;
            }
        }
    }

    return null;
}

/**
 * @param {{ packagePagesDir: string, overridePagesDir: string, listVueFiles: (dir: string) => string[] }} options
 * @returns {Record<string, string>}
 */
export function collectPages({ packagePagesDir, overridePagesDir, listVueFiles }) {
    const overrides = new Set(listVueFiles(overridePagesDir).map(normalize));
    const pages = {};

    for (const file of listVueFiles(packagePagesDir).map(normalize)) {
        const dir = overrides.has(file) ? overridePagesDir : packagePagesDir;
        pages[file.replace(/\.vue$/, '')] = `${normalize(dir)}/${file}`;
    }

    return pages;
}

/**
 * @param {Record<string, string>} pages
 * @returns {string}
 */
export function renderPagesModule(pages) {
    const entries = Object.keys(pages)
        .sort()
        .map((name) => `  ${JSON.stringify(name)}: () => import(${JSON.stringify(pages[name])}),\n`)
        .join('');

    return `export default {\n${entries}};\n`;
}
