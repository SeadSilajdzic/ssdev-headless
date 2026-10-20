import type { HeadlessSharedProps } from '@headless/types';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: HeadlessSharedProps;
    }
}
