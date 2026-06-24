import { usePage } from '@inertiajs/react';
import { Sidebar } from './sidebar';
import { Navbar } from './navbar';
import { Toast } from '@/components/ui/toast';
import { useState, useEffect } from 'react';

export function AppLayout({ children }: { children: React.ReactNode }) {
    const { flash, appearance } = usePage().props;
    const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' } | null>(null);

    useEffect(() => {
        if (flash.success) setToast({ message: flash.success, type: 'success' });
        if (flash.error) setToast({ message: flash.error, type: 'error' });
    }, [flash]);

    useEffect(() => {
        const root = document.documentElement;
        if (appearance === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
        const stored = document.cookie.match(/appearance=([^;]+)/);
        if (!stored && appearance === 'system') {
            root.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
        }
    }, [appearance]);

    return (
        <div className="min-h-screen bg-background">
            <Sidebar />
            <div className="lg:pl-64 transition-all duration-300">
                <Navbar />
                <main className="p-4 lg:p-6">
                    {children}
                </main>
            </div>
            {toast && (
                <Toast
                    message={toast.message}
                    type={toast.type}
                    onClose={() => setToast(null)}
                />
            )}
        </div>
    );
}
