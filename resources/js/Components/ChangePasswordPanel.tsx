import { useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type React from 'react';
import { FormError } from '@/Components/FormError';
import { Button } from '@/Components/ui/button';
import { Card } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export function ChangePasswordPanel() {
    const { data, setData, put, processing, errors, reset, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        put('/password', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <Card id="change-password" className="p-5">
            <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-secondary text-secondary-foreground">
                    <KeyRound className="h-5 w-5" />
                </div>
                <div>
                    <h2 className="text-lg font-semibold">Change password</h2>
                    <p className="mt-1 text-sm text-muted-foreground">Update your password regularly to protect HR and payroll records.</p>
                </div>
            </div>

            <form onSubmit={submit} className="mt-5 grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                    <Label htmlFor="current_password">Current password</Label>
                    <Input id="current_password" type="password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} />
                    <FormError message={errors.current_password} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="new_password">New password</Label>
                    <Input id="new_password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    <FormError message={errors.password} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="new_password_confirmation">Confirm password</Label>
                    <Input id="new_password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                    <FormError message={errors.password_confirmation} />
                </div>
                <div className="md:col-span-3 flex items-center gap-3">
                    <Button disabled={processing}>Save password</Button>
                    {recentlySuccessful && <p className="text-sm text-primary">Password updated.</p>}
                </div>
            </form>
        </Card>
    );
}
