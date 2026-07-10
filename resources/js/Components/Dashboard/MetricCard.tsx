import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

type MetricCardProps = {
    label: string;
    value: string | number;
    meta: string;
    trend: string;
    icon: LucideIcon;
    tone?: 'green' | 'gold' | 'blue' | 'red';
};

const tones = {
    green: 'bg-primary/10 text-primary border-primary/20',
    gold: 'bg-accent/15 text-accent-foreground border-accent/30',
    blue: 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/20',
    red: 'bg-destructive/10 text-destructive border-destructive/20',
};

export function MetricCard({ label, value, meta, trend, icon: Icon, tone = 'green' }: MetricCardProps) {
    return (
        <div className="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">{label}</p>
                    <p className="mt-2 text-3xl font-semibold">{value}</p>
                </div>
                <div className={cn('flex h-10 w-10 items-center justify-center rounded-md border', tones[tone])}>
                    <Icon className="h-5 w-5" />
                </div>
            </div>
            <div className="mt-4 flex flex-col gap-1 text-sm">
                <span className="text-muted-foreground">{meta}</span>
                <span className="font-medium text-primary">{trend}</span>
            </div>
        </div>
    );
}
