import * as React from 'react';
import { cn } from '@/lib/utils';

type ButtonProps = React.ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
};

const variants = {
    primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    ghost: 'hover:bg-secondary text-foreground',
    danger: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
};

export function Button({ className, variant = 'primary', type = 'button', ...props }: ButtonProps) {
    return (
        <button
            type={type}
            className={cn(
                'kfs-focus inline-flex h-10 items-center justify-center gap-2 rounded-md px-4 text-sm font-medium transition disabled:pointer-events-none disabled:opacity-50',
                variants[variant],
                className,
            )}
            {...props}
        />
    );
}
