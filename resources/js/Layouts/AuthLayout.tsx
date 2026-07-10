import { PropsWithChildren } from 'react';
import { ThemeToggle } from '@/Components/ThemeToggle';

export default function AuthLayout({ children, title, subtitle }: PropsWithChildren<{ title: string; subtitle: string }>) {
    return (
        <main className="min-h-screen bg-background">
            <div className="grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
                <section className="relative hidden overflow-hidden bg-primary text-primary-foreground lg:block">
                    <div className="absolute inset-0 bg-[linear-gradient(135deg,rgba(255,255,255,0.16),transparent_42%),radial-gradient(circle_at_20%_20%,rgba(234,179,8,0.32),transparent_28%)]" />
                    <div className="relative flex h-full flex-col justify-between p-12">
                        <div className="flex items-center gap-3">
                            <div className="flex h-16 w-20 items-center justify-center rounded-md bg-white p-2 shadow-sm">
                                <img src="/images/kfs-logo.png" alt="Kenya Forest Service logo" className="max-h-full max-w-full object-contain" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold uppercase tracking-wide text-white/75">Kenya Forest Service</p>
                                <h1 className="text-2xl font-semibold">KFS Smart HRMS</h1>
                            </div>
                        </div>

                        <div className="max-w-xl">
                            <p className="text-sm font-medium uppercase tracking-wide text-white/70">Enterprise HR and Payroll</p>
                            <h2 className="mt-4 text-5xl font-semibold leading-tight">Secure workforce operations for every station.</h2>
                            <p className="mt-5 text-lg leading-8 text-white/78">
                                Role-aware access, payroll controls, employee self service, and auditable activity for KFS operations.
                            </p>
                        </div>

                        <div className="grid grid-cols-3 gap-4 text-sm text-white/78">
                            <div className="rounded-lg border border-white/15 bg-white/10 p-4">
                                <p className="font-semibold text-white">RBAC</p>
                                <p>Spatie-backed role and permission gates.</p>
                            </div>
                            <div className="rounded-lg border border-white/15 bg-white/10 p-4">
                                <p className="font-semibold text-white">Audit</p>
                                <p>Login, session, and activity trails.</p>
                            </div>
                            <div className="rounded-lg border border-white/15 bg-white/10 p-4">
                                <p className="font-semibold text-white">Secure</p>
                                <p>Timeouts, reset flows, optional 2FA.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="flex min-h-screen flex-col">
                    <header className="flex items-center justify-between px-5 py-4 sm:px-8">
                        <div className="flex items-center gap-3 lg:hidden">
                            <div className="flex h-12 w-14 items-center justify-center rounded-md border bg-white p-1 shadow-sm">
                                <img src="/images/kfs-logo.png" alt="Kenya Forest Service logo" className="max-h-full max-w-full object-contain" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase text-muted-foreground">KFS Smart HRMS</p>
                                <p className="text-sm font-semibold">Secure Access</p>
                            </div>
                        </div>
                        <div className="ml-auto">
                            <ThemeToggle />
                        </div>
                    </header>

                    <div className="flex flex-1 items-center justify-center px-5 py-8 sm:px-8">
                        <div className="w-full max-w-md">
                            <div className="mb-8">
                                <img src="/images/kfs-logo.png" alt="Kenya Forest Service logo" className="mb-4 h-16 w-auto object-contain" />
                                <p className="text-sm font-medium uppercase text-muted-foreground">Kenya Forest Service</p>
                                <h2 className="mt-2 text-3xl font-semibold">{title}</h2>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">{subtitle}</p>
                            </div>
                            {children}
                        </div>
                    </div>
                </section>
            </div>
        </main>
    );
}
