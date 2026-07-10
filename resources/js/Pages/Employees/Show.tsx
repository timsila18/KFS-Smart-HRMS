import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Briefcase, Building2, Camera, FileUp, Landmark, Mail, Pencil, Phone, ShieldAlert, UserRound } from 'lucide-react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

type Employee = Record<string, any>;
type AuditLog = { uuid: string; event: string; created_at: string };

function Detail({ label, value }: { label: string; value?: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-medium uppercase text-muted-foreground">{label}</p>
            <p className="mt-1 text-sm font-medium">{value || '-'}</p>
        </div>
    );
}

function Section({ title, icon: Icon, children }: { title: string; icon: any; children: React.ReactNode }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-center gap-2">
                <Icon className="h-5 w-5 text-primary" />
                <h2 className="text-lg font-semibold">{title}</h2>
            </div>
            {children}
        </Card>
    );
}

export default function EmployeeShow({ employee, auditLogs }: { employee: { data: Employee }; auditLogs: AuditLog[] }) {
    const item = employee.data;
    const photoForm = useForm<{ photo: File | null }>({ photo: null });
    const attachmentForm = useForm<{ type: string; file: File | null }>({ type: 'employee_file', file: null });

    const uploadPhoto = (event: React.FormEvent) => {
        event.preventDefault();
        photoForm.post(`/employees/${item.uuid}/photo`, { forceFormData: true, preserveScroll: true });
    };

    const uploadAttachment = (event: React.FormEvent) => {
        event.preventDefault();
        attachmentForm.post(`/employees/${item.uuid}/attachments`, { forceFormData: true, preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title={item.full_name} />
            <div className="space-y-6">
                <section className="flex flex-col justify-between gap-4 rounded-lg border bg-card p-5 lg:flex-row lg:items-center">
                    <div className="flex items-center gap-4">
                        <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-lg bg-secondary">
                            {item.photo_url ? <img src={item.photo_url} alt={item.full_name} className="h-full w-full object-cover" /> : <UserRound className="h-9 w-9 text-muted-foreground" />}
                        </div>
                        <div>
                            <Link href="/employees" className="mb-2 inline-flex items-center gap-1 text-sm text-primary hover:underline"><ArrowLeft className="h-4 w-4" /> Register</Link>
                            <h1 className="text-2xl font-semibold">{item.full_name}</h1>
                            <p className="mt-1 text-sm text-muted-foreground">{item.employee_number} · {item.employment_status}</p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => router.visit(`/employees/${item.uuid}/edit`)}><Pencil className="h-4 w-4" /> Edit</Button>
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[1fr_340px]">
                    <div className="space-y-6">
                        <Section title="Employee Profile" icon={UserRound}>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <Detail label="First Name" value={item.first_name} />
                                <Detail label="Middle Name" value={item.middle_name} />
                                <Detail label="Last Name" value={item.last_name} />
                                <Detail label="Gender" value={item.gender} />
                                <Detail label="Date of Birth" value={item.date_of_birth} />
                                <Detail label="Hire Date" value={item.hire_date} />
                            </div>
                        </Section>

                        <Section title="Employment Details" icon={Building2}>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <Detail label="Station" value={item.station?.name} />
                                <Detail label="Department" value={item.department?.name} />
                                <Detail label="Position" value={item.job_position?.title} />
                            </div>
                        </Section>

                        <Section title="Contract Details" icon={Briefcase}>
                            <div className="space-y-3">
                                {(item.contracts ?? []).map((contract: any) => (
                                    <div key={contract.uuid} className="rounded-md border p-3">
                                        <p className="font-medium">{contract.contract_number}</p>
                                        <p className="text-sm text-muted-foreground">{contract.start_date} - {contract.end_date ?? 'Open'} · {contract.status}</p>
                                    </div>
                                ))}
                                {(item.contracts ?? []).length === 0 && <p className="text-sm text-muted-foreground">No contract records.</p>}
                            </div>
                        </Section>

                        <Section title="Salary Details" icon={Landmark}>
                            <div className="space-y-3">
                                {(item.salary_assignments ?? []).map((salary: any) => (
                                    <div key={salary.uuid} className="rounded-md border p-3">
                                        <p className="font-medium">{salary.pay_group?.name ?? 'Pay group'}</p>
                                        <p className="text-sm text-muted-foreground">{salary.effective_from} - {salary.effective_to ?? 'Current'} · {salary.status}</p>
                                    </div>
                                ))}
                                {(item.salary_assignments ?? []).length === 0 && <p className="text-sm text-muted-foreground">No salary assignment.</p>}
                            </div>
                        </Section>

                        <Section title="Qualifications & Professional Membership" icon={ShieldAlert}>
                            <div className="grid gap-4 lg:grid-cols-2">
                                <div className="space-y-2">
                                    {(item.qualifications ?? []).map((row: any) => <div key={row.uuid} className="rounded-md border p-3 text-sm">{row.qualification}<p className="text-muted-foreground">{row.institution}</p></div>)}
                                </div>
                                <div className="space-y-2">
                                    {(item.professional_memberships ?? []).map((row: any) => <div key={row.uuid} className="rounded-md border p-3 text-sm">{row.body_name}<p className="text-muted-foreground">{row.membership_number}</p></div>)}
                                </div>
                            </div>
                        </Section>
                    </div>

                    <aside className="space-y-6">
                        <Section title="Photo Upload" icon={Camera}>
                            <form onSubmit={uploadPhoto} className="space-y-3">
                                <Input type="file" accept="image/*" onChange={(e) => photoForm.setData('photo', e.target.files?.[0] ?? null)} />
                                <Button disabled={photoForm.processing}>Upload photo</Button>
                            </form>
                        </Section>

                        <Section title="Bank Details" icon={Landmark}>
                            {(item.bank_accounts ?? []).map((bank: any) => <div key={bank.uuid} className="rounded-md border p-3 text-sm">{bank.bank_name}<p className="text-muted-foreground">{bank.account_number}</p></div>)}
                        </Section>

                        <Section title="Next of Kin" icon={Phone}>
                            {(item.next_of_kin ?? []).map((kin: any) => <div key={kin.uuid} className="rounded-md border p-3 text-sm">{kin.full_name}<p className="text-muted-foreground">{kin.relationship} · {kin.phone}</p></div>)}
                        </Section>

                        <Section title="Emergency Contact" icon={Phone}>
                            {(item.emergency_contacts ?? []).map((contact: any) => <div key={contact.uuid} className="rounded-md border p-3 text-sm">{contact.full_name}<p className="text-muted-foreground">{contact.phone}</p></div>)}
                        </Section>

                        <Section title="Medical" icon={ShieldAlert}>
                            {(item.medical_records ?? []).map((medical: any) => <div key={medical.uuid} className="rounded-md border p-3 text-sm">Blood group: {medical.blood_group ?? '-'}<p className="text-muted-foreground">{medical.medical_scheme}</p></div>)}
                        </Section>

                        <Section title="Dependants" icon={UserRound}>
                            {(item.dependants ?? []).map((dependant: any) => <div key={dependant.uuid} className="rounded-md border p-3 text-sm">{dependant.full_name}<p className="text-muted-foreground">{dependant.relationship}</p></div>)}
                        </Section>

                        <Section title="Attachments" icon={FileUp}>
                            <form onSubmit={uploadAttachment} className="space-y-3">
                                <Input value={attachmentForm.data.type} onChange={(e) => attachmentForm.setData('type', e.target.value)} />
                                <Input type="file" onChange={(e) => attachmentForm.setData('file', e.target.files?.[0] ?? null)} />
                                <Button disabled={attachmentForm.processing}>Upload attachment</Button>
                            </form>
                            <div className="mt-4 space-y-2">
                                {(item.attachments ?? []).map((attachment: any) => <p key={attachment.uuid} className="rounded-md border p-2 text-sm">{attachment.file_name}</p>)}
                            </div>
                        </Section>

                        <Section title="Documents" icon={Mail}>
                            {(item.documents ?? []).map((document: any) => <div key={document.uuid} className="rounded-md border p-3 text-sm">{document.title}<p className="text-muted-foreground">{document.document_type}</p></div>)}
                        </Section>

                        <Section title="Audit Log" icon={ShieldAlert}>
                            <div className="space-y-2">
                                {auditLogs.map((log) => (
                                    <div key={log.uuid} className="rounded-md border p-3 text-sm">
                                        <p className="font-medium">{log.event}</p>
                                        <p className="text-muted-foreground">{new Date(log.created_at).toLocaleString()}</p>
                                    </div>
                                ))}
                                {auditLogs.length === 0 && <p className="text-sm text-muted-foreground">No audit events recorded for this employee.</p>}
                            </div>
                        </Section>
                    </aside>
                </section>
            </div>
        </AppLayout>
    );
}
