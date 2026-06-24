import { cn } from '@/lib/utils';

const variants = {
    default: 'bg-primary/10 text-primary border-transparent',
    secondary: 'bg-secondary text-secondary-foreground',
    destructive: 'bg-destructive/10 text-destructive border-transparent',
    outline: 'text-foreground',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
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
