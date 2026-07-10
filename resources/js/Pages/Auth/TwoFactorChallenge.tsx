import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import AuthLayout from '@/Layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { FormError } from '@/Components/FormError';

export default function TwoFactorChallenge() {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    return (
        <AuthLayout title="Two-factor verification" subtitle="Enter a recovery or authenticator code to finish signing in.">
            <Head title="Two-Factor Challenge" />
            <form onSubmit={(e) => { e.preventDefault(); post('/two-factor-challenge'); }} className="space-y-5">
                <div className="space-y-2">
                    <Label htmlFor="code">Verification code</Label>
                    <Input id="code" value={data.code} autoComplete="one-time-code" onChange={(e) => setData('code', e.target.value)} />
                    <FormError message={errors.code} />
                </div>
                <Button className="w-full" disabled={processing}>
                    <ShieldCheck className="h-4 w-4" />
                    Verify
                </Button>
            </form>
        </AuthLayout>
    );
}
