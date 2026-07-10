export type AuthUser = {
    uuid: string;
    name: string;
    email: string;
    status: string;
};

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: AuthUser | null;
        roles: string[];
        permissions: string[];
    };
    flash: {
        status?: string;
    };
    appearance: 'light' | 'dark' | 'system';
};
