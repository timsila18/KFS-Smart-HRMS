import { Head, router, useForm } from '@inertiajs/react';
import { Building2, Pencil, Plus, Save, Trash2, WalletCards } from 'lucide-react';
import type React from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

type Component = {
    id: number;
    uuid: string;
    code: string;
    name: string;
    pay_code_type: string;
    component_group: string;
    component_subtype: string | null;
    calculation_method: string;
    default_amount: string | null;
    default_rate: string | null;
    is_taxable: boolean;
    is_pensionable: boolean;
    is_recurring: boolean;
    requires_membership: boolean;
    is_active: boolean;
};

type Institution = {
    id: number;
    uuid: string;
    institution_type: string;
    code: string;
    name: string;
    registration_number: string | null;
    contact_person: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    products: Product[];
};

type Product = {
    id: number;
    uuid: string;
    pay_code_id: number | null;
    product_type: string;
    code: string;
    name: string;
    calculation_method: string;
    default_amount: string | null;
    default_rate: string | null;
    is_active: boolean;
};

type PageLink = { url: string | null; label: string; active: boolean };

const emptyComponent = {
    code: '',
    name: '',
    pay_code_type: 'earning',
    component_group: 'allowance',
    component_subtype: '',
    calculation_method: 'fixed',
    default_amount: '',
    default_rate: '',
    is_taxable: false,
    is_pensionable: false,
    is_recurring: true,
    requires_membership: false,
    is_active: true,
    sort_order: 0,
    calculation_rules: {},
};

const emptyInstitution = {
    institution_type: 'sacco',
    code: '',
    name: '',
    registration_number: '',
    contact_person: '',
    phone: '',
    email: '',
    configuration: {},
    is_active: true,
};

const emptyProduct = {
    pay_code_id: '',
    product_type: 'loan',
    code: '',
    name: '',
    calculation_method: 'fixed',
    default_amount: '',
    default_rate: '',
    rules: {},
    is_active: true,
};

export default function PayrollAdministration({
    components,
    institutions,
    lookups,
}: {
    components: { data: Component[]; links: PageLink[] };
    institutions: Institution[];
    lookups: Record<string, string[]>;
}) {
    const componentForm = useForm({ ...emptyComponent });
    const institutionForm = useForm({ ...emptyInstitution });
    const productForm = useForm({ ...emptyProduct });
    const [editingComponent, setEditingComponent] = React.useState<Component | null>(null);
    const [editingInstitution, setEditingInstitution] = React.useState<Institution | null>(null);
    const [productInstitution, setProductInstitution] = React.useState<Institution | null>(institutions[0] ?? null);
    const [editingProduct, setEditingProduct] = React.useState<Product | null>(null);

    const submitComponent = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => { componentForm.reset(); setEditingComponent(null); } };
        editingComponent
            ? componentForm.put(`/payroll/administration/components/${editingComponent.uuid}`, options)
            : componentForm.post('/payroll/administration/components', options);
    };

    const submitInstitution = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => { institutionForm.reset(); setEditingInstitution(null); } };
        editingInstitution
            ? institutionForm.put(`/payroll/administration/institutions/${editingInstitution.uuid}`, options)
            : institutionForm.post('/payroll/administration/institutions', options);
    };

    const submitProduct = (event: React.FormEvent) => {
        event.preventDefault();
        if (!productInstitution) return;
        const options = { preserveScroll: true, onSuccess: () => { productForm.reset(); setEditingProduct(null); } };
        editingProduct
            ? productForm.put(`/payroll/administration/institutions/${productInstitution.uuid}/products/${editingProduct.uuid}`, options)
            : productForm.post(`/payroll/administration/institutions/${productInstitution.uuid}/products`, options);
    };

    const editComponent = (item: Component) => {
        setEditingComponent(item);
        componentForm.setData({
            code: item.code,
            name: item.name,
            pay_code_type: item.pay_code_type,
            component_group: item.component_group,
            component_subtype: item.component_subtype ?? '',
            calculation_method: item.calculation_method,
            default_amount: item.default_amount ?? '',
            default_rate: item.default_rate ?? '',
            is_taxable: item.is_taxable,
            is_pensionable: item.is_pensionable,
            is_recurring: item.is_recurring,
            requires_membership: item.requires_membership,
            is_active: item.is_active,
            sort_order: 0,
            calculation_rules: {},
        });
    };

    const editInstitution = (item: Institution) => {
        setEditingInstitution(item);
        institutionForm.setData({
            institution_type: item.institution_type,
            code: item.code,
            name: item.name,
            registration_number: item.registration_number ?? '',
            contact_person: item.contact_person ?? '',
            phone: item.phone ?? '',
            email: item.email ?? '',
            configuration: {},
            is_active: item.is_active,
        });
    };

    const editProduct = (institution: Institution, item: Product) => {
        setProductInstitution(institution);
        setEditingProduct(item);
        productForm.setData({
            pay_code_id: item.pay_code_id ? String(item.pay_code_id) : '',
            product_type: item.product_type,
            code: item.code,
            name: item.name,
            calculation_method: item.calculation_method,
            default_amount: item.default_amount ?? '',
            default_rate: item.default_rate ?? '',
            rules: {},
            is_active: item.is_active,
        });
    };

    return (
        <AppLayout>
            <Head title="Payroll Administration" />
            <div className="space-y-6">
                <section className="rounded-lg border bg-primary p-5 text-primary-foreground">
                    <p className="text-sm font-semibold uppercase text-white/75">Configurable Payroll Master Data</p>
                    <h1 className="mt-2 text-3xl font-semibold">Payroll Administration</h1>
                    <p className="mt-3 max-w-3xl text-sm leading-6 text-white/80">
                        Configure unlimited earnings, deductions, SACCOs, welfare funds, loans, insurance products, bank recoveries, pension items, and statutory payroll components.
                    </p>
                </section>

                <section className="grid gap-6 xl:grid-cols-[420px_1fr]">
                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <WalletCards className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">{editingComponent ? 'Edit Component' : 'Create Component'}</h2>
                        </div>
                        <form onSubmit={submitComponent} className="space-y-4">
                            <Field label="Code" error={componentForm.errors.code}><Input value={componentForm.data.code} onChange={(e) => componentForm.setData('code', e.target.value)} /></Field>
                            <Field label="Name" error={componentForm.errors.name}><Input value={componentForm.data.name} onChange={(e) => componentForm.setData('name', e.target.value)} /></Field>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Select label="Type" value={componentForm.data.pay_code_type} options={lookups.pay_code_types} onChange={(value) => componentForm.setData('pay_code_type', value)} />
                                <Select label="Group" value={componentForm.data.component_group} options={[...lookups.earning_groups, ...lookups.deduction_groups]} onChange={(value) => componentForm.setData('component_group', value)} />
                                <Field label="Subtype"><Input value={componentForm.data.component_subtype} onChange={(e) => componentForm.setData('component_subtype', e.target.value)} /></Field>
                                <Select label="Calculation" value={componentForm.data.calculation_method} options={lookups.calculation_methods} onChange={(value) => componentForm.setData('calculation_method', value)} />
                                <Field label="Default Amount"><Input type="number" value={componentForm.data.default_amount} onChange={(e) => componentForm.setData('default_amount', e.target.value)} /></Field>
                                <Field label="Default Rate"><Input type="number" value={componentForm.data.default_rate} onChange={(e) => componentForm.setData('default_rate', e.target.value)} /></Field>
                            </div>
                            <div className="grid gap-2 text-sm sm:grid-cols-2">
                                {(['is_taxable', 'is_pensionable', 'is_recurring', 'requires_membership', 'is_active'] as const).map((key) => (
                                    <label key={key} className="flex items-center gap-2 rounded-md border p-2">
                                        <input type="checkbox" checked={Boolean(componentForm.data[key])} onChange={(e) => componentForm.setData(key, e.target.checked)} />
                                        {key.replaceAll('_', ' ')}
                                    </label>
                                ))}
                            </div>
                            <Button disabled={componentForm.processing}><Save className="h-4 w-4" /> Save Component</Button>
                        </form>
                    </Card>

                    <Card className="overflow-hidden">
                        <div className="border-b p-5">
                            <h2 className="text-lg font-semibold">Payroll Components</h2>
                            <p className="mt-1 text-sm text-muted-foreground">Earnings and deductions are configured here, including statutory, departmental, SACCO, loan, welfare, pension, overtime, arrears, and allowances.</p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/50 text-xs uppercase text-muted-foreground">
                                    <tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Group</th><th className="px-4 py-3">Calculation</th><th className="px-4 py-3"></th></tr>
                                </thead>
                                <tbody className="divide-y">
                                    {components.data.map((item) => (
                                        <tr key={item.uuid}>
                                            <td className="px-4 py-3 font-medium">{item.code}</td>
                                            <td className="px-4 py-3">{item.name}</td>
                                            <td className="px-4 py-3">{item.pay_code_type}</td>
                                            <td className="px-4 py-3">{item.component_group}</td>
                                            <td className="px-4 py-3">{item.calculation_method}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => editComponent(item)}><Pencil className="h-4 w-4" /></Button>
                                                    <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => router.delete(`/payroll/administration/components/${item.uuid}`, { preserveScroll: true })}><Trash2 className="h-4 w-4" /></Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </section>

                <section className="grid gap-6 xl:grid-cols-[420px_1fr]">
                    <Card className="p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <Building2 className="h-5 w-5 text-primary" />
                            <h2 className="text-lg font-semibold">{editingInstitution ? 'Edit Institution' : 'Create Institution'}</h2>
                        </div>
                        <form onSubmit={submitInstitution} className="space-y-4">
                            <Select label="Institution Type" value={institutionForm.data.institution_type} options={lookups.institution_types} onChange={(value) => institutionForm.setData('institution_type', value)} />
                            <Field label="Code" error={institutionForm.errors.code}><Input value={institutionForm.data.code} onChange={(e) => institutionForm.setData('code', e.target.value)} /></Field>
                            <Field label="Name" error={institutionForm.errors.name}><Input value={institutionForm.data.name} onChange={(e) => institutionForm.setData('name', e.target.value)} /></Field>
                            <Field label="Registration Number"><Input value={institutionForm.data.registration_number} onChange={(e) => institutionForm.setData('registration_number', e.target.value)} /></Field>
                            <Field label="Contact Person"><Input value={institutionForm.data.contact_person} onChange={(e) => institutionForm.setData('contact_person', e.target.value)} /></Field>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <Field label="Phone"><Input value={institutionForm.data.phone} onChange={(e) => institutionForm.setData('phone', e.target.value)} /></Field>
                                <Field label="Email"><Input value={institutionForm.data.email} onChange={(e) => institutionForm.setData('email', e.target.value)} /></Field>
                            </div>
                            <Button disabled={institutionForm.processing}><Save className="h-4 w-4" /> Save Institution</Button>
                        </form>
                    </Card>

                    <Card className="p-5">
                        <h2 className="text-lg font-semibold">Institutions and Products</h2>
                        <div className="mt-4 space-y-4">
                            {institutions.map((institution) => (
                                <div key={institution.uuid} className="rounded-lg border p-4">
                                    <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                        <div>
                                            <p className="font-semibold">{institution.name}</p>
                                            <p className="text-sm text-muted-foreground">{institution.code} - {institution.institution_type}</p>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="secondary" className="h-8" onClick={() => { setProductInstitution(institution); setEditingProduct(null); productForm.setData({ ...emptyProduct }); }}><Plus className="h-4 w-4" /> Product</Button>
                                            <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => editInstitution(institution)}><Pencil className="h-4 w-4" /></Button>
                                            <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => router.delete(`/payroll/administration/institutions/${institution.uuid}`, { preserveScroll: true })}><Trash2 className="h-4 w-4" /></Button>
                                        </div>
                                    </div>
                                    <div className="mt-3 grid gap-2 md:grid-cols-2">
                                        {institution.products.map((product) => (
                                            <div key={product.uuid} className="rounded-md bg-muted/50 p-3 text-sm">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div><p className="font-medium">{product.name}</p><p className="text-muted-foreground">{product.code} - {product.product_type}</p></div>
                                                    <div className="flex gap-1">
                                                        <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => editProduct(institution, product)}><Pencil className="h-4 w-4" /></Button>
                                                        <Button type="button" variant="ghost" className="h-8 px-2" onClick={() => router.delete(`/payroll/administration/institutions/${institution.uuid}/products/${product.uuid}`, { preserveScroll: true })}><Trash2 className="h-4 w-4" /></Button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </section>

                <Card className="p-5">
                    <h2 className="text-lg font-semibold">{editingProduct ? 'Edit Product' : 'Create Institution Product'}</h2>
                    <p className="mt-1 text-sm text-muted-foreground">Selected institution: {productInstitution?.name ?? 'None'}</p>
                    <form onSubmit={submitProduct} className="mt-4 grid gap-4 md:grid-cols-4">
                        <Select label="Product Type" value={productForm.data.product_type} options={lookups.product_types} onChange={(value) => productForm.setData('product_type', value)} />
                        <Field label="Code"><Input value={productForm.data.code} onChange={(e) => productForm.setData('code', e.target.value)} /></Field>
                        <Field label="Name"><Input value={productForm.data.name} onChange={(e) => productForm.setData('name', e.target.value)} /></Field>
                        <Select label="Calculation" value={productForm.data.calculation_method} options={lookups.calculation_methods} onChange={(value) => productForm.setData('calculation_method', value)} />
                        <Field label="Default Amount"><Input type="number" value={productForm.data.default_amount} onChange={(e) => productForm.setData('default_amount', e.target.value)} /></Field>
                        <Field label="Default Rate"><Input type="number" value={productForm.data.default_rate} onChange={(e) => productForm.setData('default_rate', e.target.value)} /></Field>
                        <div className="space-y-2">
                            <Label>Linked Pay Component</Label>
                            <select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={productForm.data.pay_code_id} onChange={(event) => productForm.setData('pay_code_id', event.target.value)}>
                                <option value="">None</option>
                                {components.data.map((component) => <option key={component.id} value={component.id}>{component.code} - {component.name}</option>)}
                            </select>
                        </div>
                        <div className="flex items-end">
                            <Button disabled={!productInstitution || productForm.processing}><Save className="h-4 w-4" /> Save Product</Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <div className="space-y-2"><Label>{label}</Label>{children}<FormError message={error} /></div>;
}

function Select({ label, value, options, onChange }: { label: string; value: string; options: string[]; onChange: (value: string) => void }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            <select className="h-10 w-full rounded-md border bg-background px-3 text-sm" value={value} onChange={(event) => onChange(event.target.value)}>
                {options.map((option) => <option key={option} value={option}>{option.replaceAll('_', ' ')}</option>)}
            </select>
        </div>
    );
}
