import kfsLogo from '@/Assets/kfs-logo.png';

type KfsLogoProps = {
    className?: string;
};

export function KfsLogo({ className }: KfsLogoProps) {
    return (
        <img
            src={kfsLogo}
            alt="Kenya Forest Service logo"
            className={className}
            onError={(event) => {
                event.currentTarget.src = '/images/kfs-logo.png';
            }}
        />
    );
}
