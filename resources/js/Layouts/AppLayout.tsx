import { Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { CalendarCheck, CircleDollarSign, FileBarChart, KeyRound, LayoutDashboard, LogOut, UserRound, Users } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { ThemeToggle } from '@/Components/ThemeToggle';
import { PageProps } from '@/types';

export default function AppLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<PageProps>().props;
    const { url } = usePage();
    const navItems = [
        { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, children: [] },
        { href: '/employees', label: 'Employees', icon: Users, children: [
            { href: '/employees', label: 'Register' },
            { href: '/employees/create', label: 'Add Staff' },
            { href: '/employees#bulk-import', label: 'Excel Import' },
        ] },
        { href: '/ess', label: 'ESS', icon: UserRound, children: [
            { href: '/ess', label: 'My Dashboard' },
            { href: '/ess/profile', label: 'Profile' },
            { href: '/ess/requests', label: 'Apply Leave' },
            { href: '/ess/payslips', label: 'Payslips' },
            { href: '/ess/p9', label: 'P9' },
            { href: '/ess/documents', label: 'Documents' },
            { href: '/ess/messages', label: 'Messages' },
            { href: '/ess/attendance', label: 'Duty Attendance' },
            { href: '/ess/service-documents', label: 'Issued Documents' },
            { href: '/ess/notices', label: 'Service Policies' },
            { href: '/ess/loans-deductions', label: 'Loans & Deductions' },
        ] },
        { href: '/leave/approvals', label: 'Leave', icon: CalendarCheck, children: [
            { href: '/leave/approvals', label: 'Approvals' },
            { href: '/ess/requests', label: 'Apply Leave' },
            { href: '/reports/LEAVE_REPORT', label: 'Leave Report' },
        ] },
        { href: '/payroll/administration', label: 'Payroll Admin', icon: CircleDollarSign, children: [
            { href: '/payroll/administration', label: 'Earnings & Deductions' },
            { href: '/payroll/administration#institutions', label: 'SACCOs & Loans' },
        ] },
        { href: '/payroll/processing', label: 'Payroll Processing', icon: CircleDollarSign, children: [
            { href: '/payroll/processing', label: 'Open & Run Payroll' },
            { href: '/payroll/processing#import-adjustments', label: 'Import Adjustments' },
        ] },
        { href: '/reports', label: 'Reports', icon: FileBarChart, children: [
            { href: '/reports', label: 'Report Library' },
            { href: '/reports/PAYROLL_REGISTER', label: 'Payroll Register' },
            { href: '/reports/EMPLOYEE_REGISTER', label: 'Employee Register' },
            { href: '/reports/BANK_SCHEDULE', label: 'Bank Schedule' },
            { href: '/reports/P9', label: 'P9' },
        ] },
    ];

    return (
        <main className="min-h-screen bg-background">
            <a href="#main-content" className="kfs-skip-link">
                Skip to main content
            </a>
            <header className="border-b bg-card">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                    <Link href="/dashboard" className="flex min-w-0 items-center gap-3" aria-label="KFS Smart HRMS dashboard">
                        <div className="flex h-12 w-14 items-center justify-center rounded-md border bg-white p-1 shadow-sm">
                            <img src="/images/kfs-logo.png" alt="Kenya Forest Service logo" className="max-h-full max-w-full object-contain" />
                        </div>
                        <div>
                            <p className="text-sm font-semibold">KFS Smart HRMS</p>
                            <p className="text-xs text-muted-foreground">HR & Payroll Management</p>
                        </div>
                    </Link>
                    <div className="flex items-center gap-2">
                        <ThemeToggle />
                        <Button variant="ghost" onClick={() => router.post('/logout')} aria-label="Logout">
                            <LogOut className="h-4 w-4" />
                            <span className="hidden sm:inline">Logout</span>
                        </Button>
                    </div>
                </div>
            </header>

            <div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[240px_1fr] lg:px-8">
                <aside className="space-y-4" aria-label="User and primary navigation">
                    <Card className="p-4">
                        <p className="text-sm font-semibold">{auth.user?.name}</p>
                        <p className="mt-1 truncate text-xs text-muted-foreground">{auth.user?.email}</p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {auth.roles.map((role) => (
                                <span key={role} className="rounded-md bg-secondary px-2 py-1 text-xs font-medium text-secondary-foreground">
                                    {role}
                                </span>
                            ))}
                        </div>
                    </Card>

                    <nav className="flex gap-1 overflow-x-auto pb-2 lg:grid lg:overflow-visible lg:pb-0" aria-label="Primary">
                        {navItems.map((item) => {
                            const Icon = item.icon;
                            const active = url === item.href || url.startsWith(`${item.href}/`);

                            return (
                                <div key={item.href} className="min-w-fit lg:min-w-0">
                                    <Link
                                        href={item.href}
                                        aria-current={active ? 'page' : undefined}
                                        className={`kfs-focus flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm font-medium hover:bg-secondary ${
                                            active ? 'bg-secondary text-secondary-foreground' : ''
                                        }`}
                                    >
                                        <Icon className="h-4 w-4" />
                                        {item.label}
                                    </Link>
                                    {active && item.children.length > 0 && (
                                        <div className="mt-1 hidden space-y-1 border-l border-border pl-4 lg:block">
                                            {item.children.map((child) => (
                                                <Link key={child.href} href={child.href} className="block rounded-md px-2 py-1.5 text-xs text-muted-foreground hover:bg-secondary hover:text-foreground">
                                                    {child.label}
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                        <a href="#change-password" className="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium hover:bg-secondary">
                            <KeyRound className="h-4 w-4" />
                            Change Password
                        </a>
                    </nav>
                </aside>

                <section id="main-content" tabIndex={-1}>{children}</section>
            </div>
        </main>
    );
}
