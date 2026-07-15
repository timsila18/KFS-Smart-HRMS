import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

type Lookup = { id: number; name?: string; display_name?: string; title?: string };
type EmployeePayload = {
    profile: Record<string, any>;
    ess: Record<string, any>;
    identifications: any[];
    contacts: any[];
    addresses: any[];
    dependants: any[];
    emergency_contacts: any[];
    next_of_kin: any[];
    qualifications: any[];
    professional_memberships: any[];
    bank_accounts: any[];
    documents: any[];
    medical_records: any[];
};

const blankPayload: EmployeePayload = {
    profile: {
        employee_number: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        gender: '',
        date_of_birth: '',
        hire_date: '',
        employment_status: 'active',
        employer: 'KFS',
        station_id: '',
        department_id: '',
        job_position_id: '',
        user_id: '',
    },
    ess: {
        email: '',
        password: 'KfsEss@2026',
    },
    identifications: [{ id_type: 'national_id', id_number: '' }],
    contacts: [{ contact_type: 'mobile', value: '', is_primary: true }],
    addresses: [{ address_type: 'residential', line_1: '', town: '', county: '', postal_code: '' }],
    dependants: [],
    emergency_contacts: [{ full_name: '', relationship: '', phone: '', email: '' }],
    next_of_kin: [{ full_name: '', relationship: '', phone: '', email: '', address: '', is_primary: true }],
    qualifications: [],
    professional_memberships: [],
    bank_accounts: [{ bank_name: '', branch_name: '', account_name: '', account_number: '', is_primary: true }],
    documents: [],
    medical_records: [{ blood_group: '', medical_scheme: '', medical_membership_number: '', allergies: '', conditions: '', disabilities: '' }],
};

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            <FormError message={error} />
        </div>
    );
}

function TextInput({ value, onChange, type = 'text' }: { value: any; onChange: (value: string) => void; type?: string }) {
    return <Input type={type} value={value ?? ''} onChange={(event) => onChange(event.target.value)} />;
}

export default function EmployeeForm({
    mode,
    employee,
    lookups,
}: {
    mode: 'create' | 'edit';
    employee: { data: any } | null;
    lookups: { stations: Lookup[]; departments: Lookup[]; positions: Lookup[]; employers: string[] };
}) {
    const initial = employee?.data ? {
        ...blankPayload,
        profile: {
            employee_number: employee.data.employee_number ?? '',
            first_name: employee.data.first_name ?? '',
            middle_name: employee.data.middle_name ?? '',
            last_name: employee.data.last_name ?? '',
            gender: employee.data.gender ?? '',
            date_of_birth: employee.data.date_of_birth ?? '',
            hire_date: employee.data.hire_date ?? '',
            employment_status: employee.data.employment_status ?? 'active',
            employer: employee.data.employer ?? 'KFS',
            station_id: employee.data.station?.id ?? '',
            department_id: employee.data.department?.id ?? '',
            job_position_id: employee.data.job_position?.id ?? '',
            user_id: '',
        },
        ess: {
            email: employee.data.ess_email ?? '',
            password: '',
        },
        identifications: employee.data.identifications ?? blankPayload.identifications,
        contacts: employee.data.contacts ?? blankPayload.contacts,
        addresses: employee.data.addresses ?? blankPayload.addresses,
        dependants: employee.data.dependants ?? [],
        emergency_contacts: employee.data.emergency_contacts ?? blankPayload.emergency_contacts,
        next_of_kin: employee.data.next_of_kin ?? blankPayload.next_of_kin,
        qualifications: employee.data.qualifications ?? [],
        professional_memberships: employee.data.professional_memberships ?? [],
        bank_accounts: employee.data.bank_accounts ?? blankPayload.bank_accounts,
        documents: employee.data.documents ?? [],
        medical_records: employee.data.medical_records ?? blankPayload.medical_records,
    } : blankPayload;

    const { data, setData, post, put, processing, errors } = useForm<EmployeePayload>(initial);

    const updateProfile = (key: string, value: string) => setData('profile', { ...data.profile, [key]: value });
    const updateEss = (key: string, value: string) => setData('ess', { ...data.ess, [key]: value });
    const updateRow = (section: keyof EmployeePayload, index: number, key: string, value: any) => {
        const rows = [...(data[section] as any[])];
        rows[index] = { ...rows[index], [key]: value };
        setData(section, rows as never);
    };
    const addRow = (section: keyof EmployeePayload, row: Record<string, any>) => setData(section, [...(data[section] as any[]), row] as never);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        if (mode === 'create') {
            post('/employees');
            return;
        }
        put(`/employees/${employee?.data.uuid}`);
    };

    return (
        <AppLayout>
            <Head title={mode === 'create' ? 'New Employee' : 'Edit Employee'} />
            <form onSubmit={submit} className="space-y-6">
                <section className="flex flex-col justify-between gap-4 rounded-lg border bg-card p-5 sm:flex-row sm:items-center">
                    <div>
                        <Link href="/employees" className="inline-flex items-center gap-1 text-sm text-primary hover:underline"><ArrowLeft className="h-4 w-4" /> Employee Register</Link>
                        <h1 className="mt-2 text-2xl font-semibold">{mode === 'create' ? 'Register Employee' : 'Edit Employee'}</h1>
                    </div>
                    <Button type="submit" disabled={processing}><Save className="h-4 w-4" /> Save Employee</Button>
                </section>

                <Card className="p-5">
                    <h2 className="mb-4 text-lg font-semibold">Employee Profile</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="Employee Number" error={errors['profile.employee_number']}><TextInput value={data.profile.employee_number} onChange={(v) => updateProfile('employee_number', v)} /></Field>
                        <Field label="First Name" error={errors['profile.first_name']}><TextInput value={data.profile.first_name} onChange={(v) => updateProfile('first_name', v)} /></Field>
                        <Field label="Middle Name"><TextInput value={data.profile.middle_name} onChange={(v) => updateProfile('middle_name', v)} /></Field>
                        <Field label="Last Name" error={errors['profile.last_name']}><TextInput value={data.profile.last_name} onChange={(v) => updateProfile('last_name', v)} /></Field>
                        <Field label="Gender"><TextInput value={data.profile.gender} onChange={(v) => updateProfile('gender', v)} /></Field>
                        <Field label="Date of Birth"><TextInput type="date" value={data.profile.date_of_birth} onChange={(v) => updateProfile('date_of_birth', v)} /></Field>
                        <Field label="Hire Date"><TextInput type="date" value={data.profile.hire_date} onChange={(v) => updateProfile('hire_date', v)} /></Field>
                        <Field label="Status">
                            <select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.profile.employment_status} onChange={(e) => updateProfile('employment_status', e.target.value)}>
                                <option value="active">Active</option><option value="inactive">Inactive</option><option value="exited">Exited</option>
                            </select>
                        </Field>
                        <Field label="Employer" error={errors['profile.employer']}>
                            <select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.profile.employer} onChange={(e) => updateProfile('employer', e.target.value)}>
                                {(lookups.employers ?? ['KFS']).map((employer) => <option key={employer} value={employer}>{employer}</option>)}
                            </select>
                        </Field>
                    </div>
                </Card>

                <Card className="p-5">
                    <h2 className="mb-4 text-lg font-semibold">Employment Details</h2>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label="Station"><select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.profile.station_id} onChange={(e) => updateProfile('station_id', e.target.value)}><option value="">Select station</option>{lookups.stations.map((item) => <option key={item.id} value={item.id}>{item.display_name ?? item.name}</option>)}</select></Field>
                        <Field label="Department"><select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.profile.department_id} onChange={(e) => updateProfile('department_id', e.target.value)}><option value="">Select department</option>{lookups.departments.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
                        <Field label="Job Position"><select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={data.profile.job_position_id} onChange={(e) => updateProfile('job_position_id', e.target.value)}><option value="">Select position</option>{lookups.positions.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}</select></Field>
                    </div>
                </Card>

                <Card className="p-5">
                    <h2 className="mb-1 text-lg font-semibold">ESS Login Access</h2>
                    <p className="mb-4 text-sm text-muted-foreground">Create or update the staff member's Employee Self Service account. They will log in with this email and land on their ESS workspace.</p>
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label="ESS Email" error={errors['ess.email']}>
                            <TextInput value={data.ess.email} onChange={(v) => updateEss('email', v)} />
                        </Field>
                        <Field label={mode === 'create' ? 'Initial Password' : 'New Password (optional)'} error={errors['ess.password']}>
                            <TextInput type="password" value={data.ess.password} onChange={(v) => updateEss('password', v)} />
                        </Field>
                    </div>
                    <p className="mt-3 text-xs text-muted-foreground">Default initial password is configurable. Current default: KfsEss@2026.</p>
                </Card>

                <Repeater title="Bank Details" rows={data.bank_accounts} onAdd={() => addRow('bank_accounts', { bank_name: '', branch_name: '', account_name: '', account_number: '', is_primary: false })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-4">
                        <TextInput value={row.bank_name} onChange={(v) => updateRow('bank_accounts', index, 'bank_name', v)} />
                        <TextInput value={row.branch_name} onChange={(v) => updateRow('bank_accounts', index, 'branch_name', v)} />
                        <TextInput value={row.account_name} onChange={(v) => updateRow('bank_accounts', index, 'account_name', v)} />
                        <TextInput value={row.account_number} onChange={(v) => updateRow('bank_accounts', index, 'account_number', v)} />
                    </div>
                )} />

                <Repeater title="Next of Kin" rows={data.next_of_kin} onAdd={() => addRow('next_of_kin', { full_name: '', relationship: '', phone: '', email: '', address: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-4">
                        <TextInput value={row.full_name} onChange={(v) => updateRow('next_of_kin', index, 'full_name', v)} />
                        <TextInput value={row.relationship} onChange={(v) => updateRow('next_of_kin', index, 'relationship', v)} />
                        <TextInput value={row.phone} onChange={(v) => updateRow('next_of_kin', index, 'phone', v)} />
                        <TextInput value={row.email} onChange={(v) => updateRow('next_of_kin', index, 'email', v)} />
                    </div>
                )} />

                <Repeater title="Emergency Contact" rows={data.emergency_contacts} onAdd={() => addRow('emergency_contacts', { full_name: '', relationship: '', phone: '', email: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-4">
                        <TextInput value={row.full_name} onChange={(v) => updateRow('emergency_contacts', index, 'full_name', v)} />
                        <TextInput value={row.relationship} onChange={(v) => updateRow('emergency_contacts', index, 'relationship', v)} />
                        <TextInput value={row.phone} onChange={(v) => updateRow('emergency_contacts', index, 'phone', v)} />
                        <TextInput value={row.email} onChange={(v) => updateRow('emergency_contacts', index, 'email', v)} />
                    </div>
                )} />

                <Repeater title="Qualifications" rows={data.qualifications} onAdd={() => addRow('qualifications', { institution: '', qualification: '', level: '', started_on: '', completed_on: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-3">
                        <TextInput value={row.institution} onChange={(v) => updateRow('qualifications', index, 'institution', v)} />
                        <TextInput value={row.qualification} onChange={(v) => updateRow('qualifications', index, 'qualification', v)} />
                        <TextInput value={row.level} onChange={(v) => updateRow('qualifications', index, 'level', v)} />
                    </div>
                )} />

                <Repeater title="Professional Membership" rows={data.professional_memberships} onAdd={() => addRow('professional_memberships', { body_name: '', membership_number: '', qualification: '', expires_at: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-4">
                        <TextInput value={row.body_name} onChange={(v) => updateRow('professional_memberships', index, 'body_name', v)} />
                        <TextInput value={row.membership_number} onChange={(v) => updateRow('professional_memberships', index, 'membership_number', v)} />
                        <TextInput value={row.qualification} onChange={(v) => updateRow('professional_memberships', index, 'qualification', v)} />
                        <TextInput type="date" value={row.expires_at} onChange={(v) => updateRow('professional_memberships', index, 'expires_at', v)} />
                    </div>
                )} />

                <Repeater title="Dependants" rows={data.dependants} onAdd={() => addRow('dependants', { full_name: '', relationship: '', date_of_birth: '', is_beneficiary: false })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-3">
                        <TextInput value={row.full_name} onChange={(v) => updateRow('dependants', index, 'full_name', v)} />
                        <TextInput value={row.relationship} onChange={(v) => updateRow('dependants', index, 'relationship', v)} />
                        <TextInput type="date" value={row.date_of_birth} onChange={(v) => updateRow('dependants', index, 'date_of_birth', v)} />
                    </div>
                )} />

                <Repeater title="Documents" rows={data.documents} onAdd={() => addRow('documents', { document_type: '', title: '', file_path: '', expires_at: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-4">
                        <TextInput value={row.document_type} onChange={(v) => updateRow('documents', index, 'document_type', v)} />
                        <TextInput value={row.title} onChange={(v) => updateRow('documents', index, 'title', v)} />
                        <TextInput value={row.file_path} onChange={(v) => updateRow('documents', index, 'file_path', v)} />
                        <TextInput type="date" value={row.expires_at} onChange={(v) => updateRow('documents', index, 'expires_at', v)} />
                    </div>
                )} />

                <Repeater title="Medical" rows={data.medical_records} onAdd={() => addRow('medical_records', { blood_group: '', medical_scheme: '', medical_membership_number: '' })} render={(row, index) => (
                    <div className="grid gap-3 md:grid-cols-3">
                        <TextInput value={row.blood_group} onChange={(v) => updateRow('medical_records', index, 'blood_group', v)} />
                        <TextInput value={row.medical_scheme} onChange={(v) => updateRow('medical_records', index, 'medical_scheme', v)} />
                        <TextInput value={row.medical_membership_number} onChange={(v) => updateRow('medical_records', index, 'medical_membership_number', v)} />
                    </div>
                )} />
            </form>
        </AppLayout>
    );
}

function Repeater({ title, rows, onAdd, render }: { title: string; rows: any[]; onAdd: () => void; render: (row: any, index: number) => React.ReactNode }) {
    return (
        <Card className="p-5">
            <div className="mb-4 flex items-center justify-between">
                <h2 className="text-lg font-semibold">{title}</h2>
                <Button type="button" variant="secondary" onClick={onAdd}>Add</Button>
            </div>
            <div className="space-y-3">
                {rows.map((row, index) => <div key={index} className="rounded-md border p-3">{render(row, index)}</div>)}
                {rows.length === 0 && <p className="text-sm text-muted-foreground">No records added.</p>}
            </div>
        </Card>
    );
}
