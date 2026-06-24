import { useEffect } from 'react';
import { X, CheckCircle, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ToastProps {
    message: string;
    type?: 'success' | 'error';
    onClose: () => void;
    duration?: number;
}

export function Toast({ message, type = 'success', onClose, duration = 5000 }: ToastProps) {
    useEffect(() => {
        const timer = setTimeout(onClose, duration);
        return () => clearTimeout(timer);
    }, [onClose, duration]);

    return (
        <div
            className={cn(
                'fixed bottom-4 right-4 z-[100] flex items-center gap-3 rounded-lg border bg-card px-4 py-3 shadow-lg animate-in slide-in-from-right',
                type === 'success' ? 'border-green-500/50' : 'border-red-500/50',
            )}
        >
            {type === 'success' ? (
                <CheckCircle className="h-5 w-5 text-green-500" />
            ) : (
                <AlertCircle className="h-5 w-5 text-red-500" />
            )}
            <p className="text-sm">{message}</p>
            <button onClick={onClose} className="ml-2 rounded-md p-1 hover:bg-accent">
                <X className="h-4 w-4" />
            </button>
        </div>
    );
}
