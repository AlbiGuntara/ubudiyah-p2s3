import { cn } from '@/lib/utils';

const variants = {
    default: 'bg-primary text-primary-foreground border-transparent',
    secondary: 'bg-secondary text-secondary-foreground',
    destructive: 'bg-destructive text-destructive-foreground border-transparent',
    outline: 'text-foreground',
    success: 'bg-green-600 text-white border-transparent',
    warning: 'bg-yellow-600 text-white border-transparent',
} as const;

interface BadgeProps extends React.HTMLAttributes<HTMLDivElement> {
    variant?: keyof typeof variants;
}

function Badge({ className, variant = 'default', ...props }: BadgeProps) {
    return (
        <div
            className={cn(
                'inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}

export { Badge };
