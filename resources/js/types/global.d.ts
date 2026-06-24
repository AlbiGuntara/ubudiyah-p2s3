import { type Auth, type User } from '@/types/auth';

export {};

declare global {
    interface Window {
        // Add custom window properties here
    }
}

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            appearance: string;
            flash: {
                success?: string;
                error?: string;
            };
            [key: string]: unknown;
        };
    }
}
