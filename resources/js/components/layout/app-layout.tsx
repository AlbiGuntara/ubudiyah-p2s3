import { usePage, useRemember } from '@inertiajs/react';
import { Sidebar } from './sidebar';
import { Navbar } from './navbar';
import { Toaster, type ToastData, type ToastType } from '@/components/ui/toast';
import { useState, useEffect, useCallback } from 'react';
import { cn } from '@/lib/utils';

let toastId = 0;

export function AppLayout({ children }: { children: React.ReactNode }) {
    const { flash, appearance } = usePage().props;
    const [toasts, setToasts] = useState<ToastData[]>([]);
    const [collapsed, setCollapsed] = useRemember(false, 'sidebar-collapsed');
    const [mobileOpen, setMobileOpen] = useState(false);

    const addToast = useCallback((message: string, type: ToastType) => {
        const id = String(++toastId);
        setToasts((prev) => [...prev, { id, message, type }]);
    }, []);

    const removeToast = useCallback((id: string) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    useEffect(() => {
        if (flash.success) addToast(flash.success, 'success');
        if (flash.warning) addToast(flash.warning, 'warning');
        if (flash.error) addToast(flash.error, 'error');
    }, [flash, addToast]);

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
        <div className="min-h-screen bg-gradient-to-br from-background via-background to-muted/50">
            <Sidebar
                collapsed={collapsed}
                mobileOpen={mobileOpen}
                onMobileClose={() => setMobileOpen(false)}
            />
            <div
                className={cn(
                    'transition-all duration-300',
                    collapsed ? 'lg:pl-[72px]' : 'lg:pl-64',
                )}
            >
                <Navbar
                    collapsed={collapsed}
                    onToggleCollapse={() => setCollapsed(!collapsed)}
                    onToggleMobile={() => setMobileOpen(true)}
                />
                <main className="p-4 lg:p-6">
                    {children}
                </main>
            </div>
            <Toaster toasts={toasts} onClose={removeToast} />
        </div>
    );
}
