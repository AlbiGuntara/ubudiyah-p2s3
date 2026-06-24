import { useState } from 'react';
import { Link, usePage, useRemember } from '@inertiajs/react';
import {
    LayoutDashboard,
    Map,
    Building2,
    Users,
    UserCog,
    BookOpen,
    Gavel,
    GraduationCap,
    FileText,
    Shield,
    ChevronLeft,
    ChevronRight,
    Menu,
    X,
    Search,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface NavItem {
    label: string;
    href: string;
    icon: React.ElementType;
    roles?: string[];
}

const navItems: NavItem[] = [
    { label: 'Dashboard', href: '/', icon: LayoutDashboard },
    { label: 'Daerah', href: '/daerah', icon: Map },
    { label: 'Asrama', href: '/asrama', icon: Building2 },
    { label: 'Santri', href: '/santri', icon: Users },
    { label: 'Petugas', href: '/petugas', icon: UserCog },
    { label: 'Jenis Pelanggaran', href: '/daftar-pelanggaran', icon: BookOpen },
    { label: 'Pelanggaran', href: '/pelanggaran', icon: Gavel },
    { label: 'Pembinaan', href: '/pembinaan', icon: GraduationCap },
    { label: 'Laporan', href: '/laporan/bulanan', icon: FileText },
    { label: 'Audit Log', href: '/audit', icon: Shield, roles: ['super_admin'] },
];

export function Sidebar() {
    const page = usePage();
    const url = page.url;
    const { auth } = page.props;
    const [collapsed, setCollapsed] = useRemember(false, 'sidebar-collapsed');
    const [mobileOpen, setMobileOpen] = useState(false);

    const user = auth.user;
    if (!user) return null;

    const filteredItems = navItems.filter((item) => {
        if (!item.roles) return true;
        return item.roles.includes(user.role);
    });

    return (
        <>
            <button
                onClick={() => setMobileOpen(true)}
                className="fixed top-4 left-4 z-50 lg:hidden rounded-md p-2 bg-background border shadow-sm"
            >
                <Menu className="h-5 w-5" />
            </button>

            {mobileOpen && (
                <div className="fixed inset-0 z-40 bg-black/50 lg:hidden" onClick={() => setMobileOpen(false)} />
            )}

            <aside
                className={cn(
                    'fixed top-0 left-0 z-40 h-screen bg-sidebar-background border-r border-sidebar-border transition-all duration-300 flex flex-col',
                    collapsed ? 'w-16' : 'w-64',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                )}
            >
                <div className={cn('flex items-center h-16 border-b border-sidebar-border px-4', collapsed ? 'justify-center' : 'justify-between')}>
                    {!collapsed && (
                        <div>
                            <h1 className="font-bold text-sm text-sidebar-primary">Ubudiyah</h1>
                            <p className="text-xs text-sidebar-foreground/60">P2S3 Sukorejo</p>
                        </div>
                    )}
                    <button
                        onClick={() => setCollapsed(!collapsed)}
                        className="hidden lg:block rounded-md p-1.5 hover:bg-sidebar-accent transition-colors"
                    >
                        {collapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />}
                    </button>
                    <button onClick={() => setMobileOpen(false)} className="lg:hidden rounded-md p-1.5 hover:bg-sidebar-accent">
                        <X className="h-4 w-4" />
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto p-2 space-y-1">
                    {filteredItems.map((item) => {
                        const isActive = url === item.href || url.startsWith(item.href + '/');
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                onClick={() => setMobileOpen(false)}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors',
                                    isActive
                                        ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                                        : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                    collapsed && 'justify-center px-2',
                                )}
                            >
                                <item.icon className={cn('h-5 w-5 shrink-0', collapsed && 'h-5 w-5')} />
                                {!collapsed && <span>{item.label}</span>}
                            </Link>
                        );
                    })}
                </nav>

                <div className={cn('border-t border-sidebar-border p-3', collapsed && 'text-center')}>
                    {!collapsed ? (
                        <div className="text-xs text-sidebar-foreground/60">
                            <p className="font-medium text-sidebar-foreground">{user.name}</p>
                            <p className="capitalize">{user.role.replace('_', ' ')}</p>
                        </div>
                    ) : (
                        <div className="w-8 h-8 rounded-full bg-sidebar-primary flex items-center justify-center text-sidebar-primary-foreground text-xs font-bold">
                            {user.name.charAt(0)}
                        </div>
                    )}
                </div>
            </aside>
        </>
    );
}
