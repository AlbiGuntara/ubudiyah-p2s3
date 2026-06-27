import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';

interface ModalProps {
    open: boolean;
    onClose: () => void;
    title?: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl';
    footer?: React.ReactNode;
}

const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
};

export function Modal({ open, onClose, title, description, children, className, maxWidth = 'md', footer }: ModalProps) {
    const panelRef = useRef<HTMLDivElement>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (open) {
            requestAnimationFrame(() => setVisible(true));
        } else {
            setVisible(false);
        }
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const handler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') onClose();
        };
        document.addEventListener('keydown', handler);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', handler);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className={cn(
                    'fixed inset-0 transition-opacity duration-200',
                    visible ? 'opacity-100' : 'opacity-0',
                )}
                style={{ backgroundColor: 'rgba(0,0,0,0.55)' }}
                onClick={onClose}
            />
            <div
                ref={panelRef}
                className={cn(
                    'relative z-50 w-full rounded-xl border bg-card text-card-foreground p-0 shadow-2xl transition-all duration-200',
                    visible ? 'opacity-100 scale-100' : 'opacity-0 scale-95',
                    maxWidthClasses[maxWidth],
                    className,
                )}
            >
                <div className="flex items-start justify-between gap-4 border-b px-6 py-4">
                    <div className="min-w-0 flex-1">
                        {title && (
                            <h2 className="text-lg font-semibold text-foreground">
                                {title}
                            </h2>
                        )}
                        {description && (
                            <p className="mt-0.5 text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring transition-colors"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <div className="px-6 py-5">
                    {children}
                </div>

                {footer && (
                    <div className="flex items-center justify-end gap-3 border-t px-6 py-4">
                        {footer}
                    </div>
                )}
            </div>
        </div>
    );
}
