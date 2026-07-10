import { Moon, Monitor, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/Components/ui/button';

type Appearance = 'light' | 'dark' | 'system';

export function ThemeToggle() {
    const [appearance, setAppearance] = useState<Appearance>(() => {
        const match = document.cookie.match(/(?:^|; )appearance=([^;]*)/);
        return (match?.[1] as Appearance) || 'system';
    });

    useEffect(() => {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.classList.toggle('dark', appearance === 'dark' || (appearance === 'system' && prefersDark));
        document.cookie = `appearance=${appearance};path=/;max-age=31536000;samesite=lax`;
    }, [appearance]);

    const next = () => setAppearance(appearance === 'light' ? 'dark' : appearance === 'dark' ? 'system' : 'light');
    const Icon = appearance === 'light' ? Sun : appearance === 'dark' ? Moon : Monitor;

    return (
        <Button type="button" variant="ghost" className="h-9 w-9 px-0" onClick={next} aria-label="Toggle theme">
            <Icon className="h-4 w-4" />
        </Button>
    );
}
