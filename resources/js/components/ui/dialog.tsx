import * as React from 'react';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

function Dialog({ open, onClose, title, description, children, className, maxWidth = 'md' }: DialogProps) {
    if (!open) return null;

    const maxWidthClasses = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center">
            <div className="fixed inset-0 bg-black/80" onClick={onClose} />
            <div
                className={cn(
                    'relative z-50 w-full mx-4 rounded-lg border bg-card p-6 shadow-lg',
                    maxWidthClasses[maxWidth],
                    className,
                )}
            >
                <div className="flex items-center justify-between mb-4">
                    <div>
                        {title && <h2 className="text-lg font-semibold">{title}</h2>}
                        {description && <p className="text-sm text-muted-foreground">{description}</p>}
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-md p-1 hover:bg-accent transition-colors"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

export { Dialog };
