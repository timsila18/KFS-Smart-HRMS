import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpenCheck,
    CalendarDays,
    ClipboardCheck,
    FileArchive,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Landmark,
    MessageSquareText,
    ScrollText,
    Settings,
    UserRound,
    WalletCards,
} from 'lucide-react';

const items = [
    ['My Dashboard', '/ess', LayoutDashboard],
    ['My Leave', '/ess/leave', CalendarDays],
    ['My Payslips', '/ess/payslips', WalletCards],
    ['My Documents', '/ess/documents', FileText],
    ['Messages', '/ess/messages', MessageSquareText],
    ['My Notifications', '/ess/notifications', Bell],
    ['My Profile', '/ess/profile', UserRound],
    ['Duty Attendance', '/ess/attendance', ClipboardCheck],
    ['Issued Documents', '/ess/service-documents', FileArchive],
    ['Service Policies', '/ess/notices', BookOpenCheck],
    ['My Performance', '/ess/performance', ScrollText],
    ['Training', '/ess/training', GraduationCap],
    ['Loans & Deductions', '/ess/loans-deductions', Landmark],
    ['My Requests', '/ess/requests', MessageSquareText],
    ['My Settings', '/ess/settings', Settings],
] as const;

export function EssNav() {
    const { url } = usePage();

    return (
        <aside className="rounded-lg border bg-card p-3">
            <div className="mb-3 rounded-md border bg-secondary/40 p-3">
                <p className="text-xs font-bold uppercase text-primary">KFS ESS</p>
                <p className="mt-1 text-sm text-muted-foreground">Your service records, payroll, leave, notices, and personal requests.</p>
            </div>
            <nav className="grid gap-1" aria-label="Employee self service">
                {items.map(([label, href, Icon]) => {
                    const active = url === href || (href !== '/ess' && url.startsWith(`${href}/`));

                    return (
                        <Link
                            key={href}
                            href={href}
                            aria-current={active ? 'page' : undefined}
                            className={`kfs-focus flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium transition ${
                                active ? 'border-primary/30 bg-primary text-primary-foreground' : 'bg-background hover:border-primary/40 hover:bg-primary/5'
                            }`}
                        >
                            <Icon className="h-4 w-4" />
                            {label}
                        </Link>
                    );
                })}
            </nav>
        </aside>
    );
}
