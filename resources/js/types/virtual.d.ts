declare module 'virtual:headless/pages' {
    const pages: Record<string, () => Promise<unknown>>;
    export default pages;
}
