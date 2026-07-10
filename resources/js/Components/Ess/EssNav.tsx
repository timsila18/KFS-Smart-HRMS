import { Link } from '@inertiajs/react';
import { Bell, Briefcase, CalendarDays, FileText, GraduationCap, LayoutDashboard, MessageSquarePlus, ScrollText, UserRound, WalletCards } from 'lucide-react';

const items = [
    ['Dashboard', '/ess', LayoutDashboard],
    ['Profile', '/ess/profile', UserRound],
    ['Payslips', '/ess/payslips', WalletCards],
    ['P9', '/ess/p9', FileText],
    ['Leave', '/ess/leave', CalendarDays],
    ['Training', '/ess/training', GraduationCap],
    ['Performance', '/ess/performance', ScrollText],
    ['Payroll History', '/ess/payroll-history', WalletCards],
    ['Contracts', '/ess/contracts', Briefcase],
    ['Documents', '/ess/documents', FileText],
    ['Notifications', '/ess/notifications', Bell],
    ['Requests', '/ess/requests', MessageSquarePlus],
] as const;

export function EssNav() {
    return (
        <nav className="flex gap-2 overflow-x-auto pb-2">
            {items.map(([label, href, Icon]) => (
                <Link key={href} href={href} className="inline-flex shrink-0 items-center gap-2 rounded-md border bg-card px-3 py-2 text-sm font-medium hover:border-primary hover:bg-primary/5">
                    <Icon className="h-4 w-4 text-primary" />
                    {label}
                </Link>
            ))}
        </nav>
    );
}
