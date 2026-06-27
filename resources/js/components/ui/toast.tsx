import { useEffect, useRef, useState } from 'react';
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface ToastData {
    id: string;
    message: string;
    type: ToastType;
    duration?: number;
}

interface ToastItemProps {
    toast: ToastData;
    onClose: (id: string) => void;
}

const iconMap = {
    success: CheckCircle,
    error: AlertCircle,
    warning: AlertTriangle,
    info: Info,
};

const colorMap = {
    success: {
        border: 'border-green-500',
        bg: 'bg-green-50 dark:bg-green-950/30',
        icon: 'text-green-600 dark:text-green-400',
        progress: 'bg-green-500',
    },
    error: {
        border: 'border-red-500',
        bg: 'bg-red-50 dark:bg-red-950/30',
        icon: 'text-red-600 dark:text-red-400',
        progress: 'bg-red-500',
    },
    warning: {
        border: 'border-amber-500',
        bg: 'bg-amber-50 dark:bg-amber-950/30',
        icon: 'text-amber-600 dark:text-amber-400',
        progress: 'bg-amber-500',
    },
    info: {
        border: 'border-blue-500',
        bg: 'bg-blue-50 dark:bg-blue-950/30',
        icon: 'text-blue-600 dark:text-blue-400',
        progress: 'bg-blue-500',
    },
};

function ToastItem({ toast, onClose }: ToastItemProps) {
    const [progress, setProgress] = useState(100);
    const [exiting, setExiting] = useState(false);
    const startTime = useRef(Date.now());
    const duration = toast.duration ?? 5000;
    const colors = colorMap[toast.type];
    const Icon = iconMap[toast.type];

    useEffect(() => {
        const animate = () => {
            const elapsed = Date.now() - startTime.current;
            const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
            setProgress(remaining);
            if (remaining <= 0) {
                setExiting(true);
                setTimeout(() => onClose(toast.id), 200);
            } else {
                requestAnimationFrame(animate);
            }
        };
        const raf = requestAnimationFrame(animate);
        return () => cancelAnimationFrame(raf);
    }, [toast.id, duration, onClose]);

    const handleClose = () => {
        setExiting(true);
        setTimeout(() => onClose(toast.id), 200);
    };

    return (
        <div
            className={cn(
                'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-card p-4 shadow-lg transition-all duration-300',
                colors.border,
                exiting ? 'opacity-0 translate-x-4' : 'opacity-100 translate-x-0',
            )}
        >
            <div className={cn('flex h-5 w-5 shrink-0 items-center justify-center', colors.icon)}>
                <Icon className="h-5 w-5" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-foreground">{toast.message}</p>
                <div className="mt-2 h-1 w-full rounded-full bg-muted overflow-hidden">
                    <div
                        className={cn('h-full rounded-full transition-all duration-150 ease-linear', colors.progress)}
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </div>
            <button
                onClick={handleClose}
                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
            >
                <X className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}

export function Toaster({ toasts, onClose }: { toasts: ToastData[]; onClose: (id: string) => void }) {
    return (
        <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
            {toasts.map((t) => (
                <ToastItem key={t.id} toast={t} onClose={onClose} />
            ))}
        </div>
    );
}
