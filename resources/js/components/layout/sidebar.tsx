import { Fragment, useState, useRef, useCallback, useLayoutEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
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
    UserPlus,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface NavItem {
    label: string;
    href: string;
    icon: React.ElementType;
    roles?: string[];
    permissions?: string[];
}

interface NavGroup {
    title: string;
    items: NavItem[];
}

const navGroups: NavGroup[] = [
    {
        title: 'Menu',
        items: [
            { label: 'Dashboard', href: '/', icon: LayoutDashboard, permissions: ['view_dashboard'] },
        ],
    },
    {
        title: 'Master Data',
        items: [
            { label: 'Daerah', href: '/daerah', icon: Map, permissions: ['view_daerah'] },
            { label: 'Asrama', href: '/asrama', icon: Building2, permissions: ['view_asrama'] },
            { label: 'Santri', href: '/santri', icon: Users, permissions: ['view_santri'] },
            { label: 'Petugas', href: '/petugas', icon: UserCog, permissions: ['view_petugas'] },
            { label: 'Jenis Pelanggaran', href: '/daftar-pelanggaran', icon: BookOpen, permissions: ['view_daftar_pelanggaran'] },
        ],
    },
    {
        title: 'Transaksi',
        items: [
            { label: 'Pelanggaran', href: '/pelanggaran', icon: Gavel, permissions: ['view_pelanggaran'] },
            { label: 'Pembinaan', href: '/pembinaan', icon: GraduationCap, permissions: ['view_pembinaan'] },
        ],
    },
    {
        title: 'Laporan',
        items: [
            { label: 'Laporan', href: '/laporan', icon: FileText, permissions: ['view_laporan'] },
        ],
    },
    {
        title: 'Pengaturan',
        items: [
            { label: 'Audit Log', href: '/audit', icon: Shield, permissions: ['view_audit'] },
            { label: 'Pengguna', href: '/users', icon: UserPlus, roles: ['super_admin'] },
        ],
    },
];

export function Sidebar({
    collapsed,
    mobileOpen,
    onMobileClose,
}: {
    collapsed: boolean;
    mobileOpen: boolean;
    onMobileClose: () => void;
}) {
    const page = usePage();
    const pathname = page.url.split('?')[0];
    const { auth } = page.props;

    const user = auth.user;
    if (!user) return null;

    const filteredGroups = navGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                if (item.roles && !item.roles.includes(user.role)) return false;
                if (item.permissions) {
                    return item.permissions.some((p) => user.permissions?.includes(p));
                }
                return true;
            }),
        }))
        .filter((group) => group.items.length > 0);

    const [tooltip, setTooltip] = useState<{ label: string; top: number } | null>(null);
    const asideRef = useRef<HTMLElement>(null);
    const navScrollRef = useRef<HTMLDivElement>(null);
    const scrollPos = useRef(0);

    const onNavScroll = useCallback(() => {
        if (navScrollRef.current) {
            scrollPos.current = navScrollRef.current.scrollTop;
        }
    }, []);

    useLayoutEffect(() => {
        if (navScrollRef.current) {
            navScrollRef.current.scrollTop = scrollPos.current;
        }
    });

    const showTooltip = (label: string, e: React.MouseEvent) => {
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
        setTooltip({ label, top: rect.top + rect.height / 2 });
    };

    const hideTooltip = () => setTooltip(null);

    return (
        <>
            {mobileOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
                    onClick={onMobileClose}
                />
            )}

            <aside
                ref={asideRef}
                className={cn(
                    'fixed top-0 left-0 z-40 min-h-screen h-dvh bg-sidebar-background border-r border-sidebar-border transition-all duration-300 flex flex-col',
                    collapsed ? 'w-[72px]' : 'w-64',
                    mobileOpen
                        ? 'translate-x-0 shadow-2xl'
                        : '-translate-x-full lg:translate-x-0',
                )}
            >
                <div className={cn('flex items-center h-16 border-b border-sidebar-border shrink-0', collapsed ? 'justify-center px-3' : 'px-4')}>
                    <div className={cn('flex items-center gap-3', collapsed && 'flex-col gap-1')}>
                        <img
                            src="/logo/p2s3.png"
                            alt="Logo"
                            className={cn(
                                'object-contain shrink-0',
                                collapsed ? 'h-8 w-8' : 'h-9 w-9',
                            )}
                        />
                        {!collapsed && (
                            <div className="min-w-0">
                                <h1 className="font-bold text-sm text-sidebar-primary leading-tight">Ubudiyah</h1>
                                <p className="text-[11px] text-sidebar-foreground/50 leading-tight">P2S3 Sukorejo</p>
                            </div>
                        )}
                    </div>
                </div>

                <nav ref={navScrollRef} onScroll={onNavScroll} className={cn('flex-1 overflow-y-auto py-3 sidebar-scroll', collapsed ? 'px-2' : 'px-3')}>
                    <div className={cn('space-y-5', collapsed && 'space-y-3')}>
                        {filteredGroups.map((group) => (
                            <Fragment key={group.title}>
                                {!collapsed && (
                                    <p className="px-2 text-[11px] font-semibold uppercase text-sidebar-foreground/35 tracking-[0.08em]">
                                        {group.title}
                                    </p>
                                )}
                                <div className="space-y-0.5">
                                    {group.items.map((item) => {
                                        const isActive = pathname === item.href || pathname.startsWith(item.href + '/');
                                        const Icon = item.icon;
                                        return (
                                            <Link
                                                key={item.href}
                                                href={item.href}
                                                onClick={onMobileClose}
                                                className={cn(
                                                    'relative flex items-center rounded-lg text-sm transition-all duration-200',
                                                    collapsed ? 'justify-center h-11 w-11 mx-auto' : 'gap-3 px-3 py-2.5',
                                                    isActive
                                                        ? 'bg-sidebar-primary/10 text-sidebar-primary font-medium'
                                                        : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                                )}
                                                onMouseEnter={collapsed ? (e) => showTooltip(item.label, e) : undefined}
                                                onMouseLeave={collapsed ? hideTooltip : undefined}
                                            >
                                                {isActive && (
                                                    <span className="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-full bg-sidebar-primary" />
                                                )}
                                                <Icon className={cn('h-5 w-5 shrink-0', collapsed && 'h-5 w-5')} />
                                                {!collapsed && <span className="truncate">{item.label}</span>}
                                            </Link>
                                        );
                                    })}
                                </div>
                            </Fragment>
                        ))}
                    </div>
                </nav>

                <div className={cn('border-t border-sidebar-border shrink-0 px-3 py-3', collapsed && 'text-center')}>
                    {!collapsed ? (
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-full bg-sidebar-primary flex items-center justify-center text-sidebar-primary-foreground text-xs font-bold shrink-0">
                                {user.name.charAt(0)}
                            </div>
                            <div className="min-w-0 text-xs text-sidebar-foreground/60">
                                <p className="font-medium text-sidebar-foreground truncate">{user.name}</p>
                                <p className="capitalize truncate">{user.role.replace('_', ' ')}</p>
                            </div>
                        </div>
                    ) : (
                        <div
                            className="relative"
                            onMouseEnter={(e) => showTooltip(user.name, e)}
                            onMouseLeave={hideTooltip}
                        >
                            <div className="w-9 h-9 rounded-full bg-sidebar-primary flex items-center justify-center text-sidebar-primary-foreground text-xs font-bold mx-auto cursor-default">
                                {user.name.charAt(0)}
                            </div>
                        </div>
                    )}
                </div>

                {collapsed && tooltip && (
                    <div
                        className="fixed z-[100] pointer-events-none animate-in fade-in slide-in-from-left-1 duration-150"
                        style={{
                            left: asideRef.current ? asideRef.current.getBoundingClientRect().right + 10 : 80,
                            top: tooltip.top,
                            transform: 'translateY(-50%)',
                        }}
                    >
                        <div className="relative px-3 py-2 rounded-lg bg-gradient-to-br from-sidebar-foreground to-sidebar-foreground/90 text-sidebar-background text-xs font-semibold whitespace-nowrap shadow-xl ring-1 ring-white/10">
                            {tooltip.label}
                            <div
                                className="absolute left-0 top-1/2 -translate-x-1 -translate-y-1/2 border-4 border-transparent border-r-sidebar-foreground"
                            />
                        </div>
                    </div>
                )}
            </aside>
        </>
    );
}
