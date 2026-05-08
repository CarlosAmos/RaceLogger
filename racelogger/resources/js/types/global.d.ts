import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            activeWorld: { id: number; name: string; current_year: number } | null;
            [key: string]: unknown;
        };
    }
}
