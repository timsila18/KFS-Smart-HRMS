import { Head, Link, router, useForm } from '@inertiajs/react';
import { Download, Eye, FileText, FileUp, Plus, RotateCcw, Search, SlidersHorizontal, WalletCards } from 'lucide-react';
import type React from 'react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

type Employee = {
    id: number;
    uuid: string;
    employee_number: string;
    full_name: string;
    employment_status: string;
    payroll_status?: string;
    account_status?: string;
    employer: string;
    station?: { name: string } | null;
    department?: { name: string } | null;
    job_position?: { title: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: Record<string, unknown>;
};

type Lookup = { id: number; name?: string; display_name?: string; title?: string };

export default function EmployeeIndex({
    employees,
    filters,
    lookups,
}: {
    employees: Paginated<Employee>;
    filters: Record<string, string>;
    lookups: { stations: Lookup[]; departments: Lookup[]; positions: Lookup[]; employers: string[] };
}) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        status: filters.status ?? '',
        employer: filters.employer ?? '',
        station_id: filters.station_id ?? '',
        department_id: filters.department_id ?? '',
        job_position_id: filters.job_position_id ?? '',
    });
    const importForm = useForm<{ file: File | null }>({ file: null });

    const applyFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/employees', form, { preserveState: true, replace: true });
    };

    const exportUrl = (format: 'excel' | 'pdf') => {
        const params = new URLSearchParams(Object.entries(form).filter(([, value]) => String(value).length > 0));
        return `/employees/export/${format}?${params.toString()}`;
    };

    const needsReinstate = (employee: Employee) => ['exited', 'inactive', 'separated'].includes(employee.employment_status)
        || employee.payroll_status === 'stopped'
        || employee.account_status === 'suspended';

    const postAction = (url: string, message: string) => {
        if (window.confirm(message)) {
            router.post(url, {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Employee Register" />
            <div className="space-y-6">
                <section className="flex flex-col justify-between gap-4 rounded-lg border bg-card p-5 sm:flex-row sm:items-center">
                    <div>
                        <p className="text-sm font-medium uppercase text-muted-foreground">Human Resource Registry</p>
                        <h1 className="mt-1 text-2xl font-semibold">Employee Register</h1>
                        <p className="mt-2 text-sm text-muted-foreground">Search, manage, export, and audit complete employee records.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={() => router.visit(exportUrl('excel'))} variant="secondary">
                            <Download className="h-4 w-4" /> Excel
                        </Button>
                        <Button onClick={() => router.visit(exportUrl('pdf'))} variant="secondary">
                            <FileText className="h-4 w-4" /> PDF
                        </Button>
                        <Link href="/employees/create" className="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">
                            <Plus className="h-4 w-4" /> New Employee
                        </Link>
                    </div>
                </section>

                <Card id="bulk-import" className="p-5 scroll-mt-24">
                    <form onSubmit={applyFilters} className="grid gap-3 xl:grid-cols-[1.2fr_150px_220px_1fr_1fr_1fr_auto]">
                        <div className="relative">
                            <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input className="pl-9" placeholder="Search employee number or name" value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} />
                        </div>
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })}>
                            <option value="">All status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="exited">Exited</option>
                        </select>
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.employer} onChange={(e) => setForm({ ...form, employer: e.target.value })}>
                            <option value="">All employers</option>
                            {(lookups.employers ?? ['KFS']).map((employer) => <option key={employer} value={employer}>{employer}</option>)}
                        </select>
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.station_id} onChange={(e) => setForm({ ...form, station_id: e.target.value })}>
                            <option value="">All stations</option>
                            {lookups.stations.map((item) => <option key={item.id} value={item.id}>{item.display_name ?? item.name}</option>)}
                        </select>
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.department_id} onChange={(e) => setForm({ ...form, department_id: e.target.value })}>
                            <option value="">All departments</option>
                            {lookups.departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                        </select>
                        <select className="rounded-md border bg-background px-3 py-2 text-sm" value={form.job_position_id} onChange={(e) => setForm({ ...form, job_position_id: e.target.value })}>
                            <option value="">All positions</option>
                            {lookups.positions.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}
                        </select>
                        <Button type="submit">
                            <SlidersHorizontal className="h-4 w-4" /> Filter
                        </Button>
                    </form>
                </Card>

                <Card className="p-5">
                    <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                        <div>
                            <h2 className="font-semibold">Bulk Staff Import</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Download the template, fill staff details, then upload it to create employee records, ESS accounts, station assignments, and bank details.
                            </p>
                            <a href="/employees/import/template" className="mt-3 inline-flex h-10 items-center justify-center gap-2 rounded-md bg-secondary px-4 text-sm font-medium text-secondary-foreground transition hover:bg-secondary/80">
                                <Download className="h-4 w-4" /> Download Onboarding Template
                            </a>
                        </div>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                importForm.post('/employees/import', { forceFormData: true, preserveScroll: true, onSuccess: () => importForm.reset() });
                            }}
                            className="grid gap-3 sm:grid-cols-[minmax(240px,1fr)_auto]"
                        >
                            <Input type="file" accept=".xlsx,.xls,.csv,.txt" onChange={(event) => importForm.setData('file', event.target.files?.[0] ?? null)} />
                            <Button type="submit" disabled={importForm.processing || !importForm.data.file}>
                                <FileUp className="h-4 w-4" /> Import Staff
                            </Button>
                        </form>
                    </div>
                    {importForm.errors.file && <p className="mt-2 text-sm text-destructive">{importForm.errors.file}</p>}
                </Card>

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">Employee</th>
                                    <th className="px-4 py-3">Station</th>
                                    <th className="px-4 py-3">Department</th>
                                    <th className="px-4 py-3">Employer</th>
                                    <th className="px-4 py-3">Position</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {employees.data.map((employee) => (
                                    <tr key={employee.uuid} className="align-top">
                                        <td className="px-4 py-3">
                                            <p className="font-medium">{employee.full_name}</p>
                                            <p className="text-xs text-muted-foreground">{employee.employee_number}</p>
                                        </td>
                                        <td className="px-4 py-3">{employee.station?.name ?? '-'}</td>
                                        <td className="px-4 py-3">{employee.department?.name ?? '-'}</td>
                                        <td className="px-4 py-3">{employee.employer ?? 'KFS'}</td>
                                        <td className="px-4 py-3">{employee.job_position?.title ?? '-'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1">
                                                <span className="rounded-md bg-secondary px-2 py-1 text-xs font-medium">{employee.employment_status}</span>
                                                <span className="rounded-md bg-secondary/70 px-2 py-1 text-xs font-medium">{employee.payroll_status ?? 'live'}</span>
                                                <span className="rounded-md bg-secondary/70 px-2 py-1 text-xs font-medium">{employee.account_status ?? 'active'}</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex min-w-56 flex-wrap justify-end gap-2">
                                                <Link href={`/employees/${employee.uuid}`} className="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-medium text-primary hover:bg-muted">
                                                    <Eye className="h-4 w-4" /> Staff File
                                                </Link>
                                                <Link href={`/employees/${employee.uuid}#salary`} className="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-medium hover:bg-muted">
                                                    <WalletCards className="h-4 w-4" /> Review Salary
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() => postAction(`/employees/${employee.uuid}/stop-salary`, `Stop salary for ${employee.full_name}?`)}
                                                    className="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-medium hover:bg-muted"
                                                >
                                                    Stop Salary
                                                </button>
                                                {needsReinstate(employee) && (
                                                    <button
                                                        type="button"
                                                        onClick={() => postAction(`/employees/${employee.uuid}/reinstate`, `Reinstate ${employee.full_name}?`)}
                                                        className="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground"
                                                    >
                                                        <RotateCcw className="h-4 w-4" /> Reinstate Staff
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                                {employees.data.length === 0 && (
                                    <tr><td className="px-4 py-8 text-center text-muted-foreground" colSpan={7}>No employees found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
