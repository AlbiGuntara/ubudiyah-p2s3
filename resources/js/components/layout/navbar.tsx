import { useState, useRef, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Search,
    Moon,
    Sun,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeft,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export function Navbar({
    collapsed,
    onToggleCollapse,
    onToggleMobile,
}: {
    collapsed: boolean;
    onToggleCollapse: () => void;
    onToggleMobile: () => void;
}) {
    const { auth, appearance } = usePage().props;
    const user = auth.user;
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<any[]>([]);
    const [showSearch, setShowSearch] = useState(false);
    const [showProfile, setShowProfile] = useState(false);
    const searchRef = useRef<HTMLDivElement>(null);
    const profileRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleClick = (e: MouseEvent) => {
            if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
                setShowSearch(false);
            }
            if (profileRef.current && !profileRef.current.contains(e.target as Node)) {
                setShowProfile(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    const handleSearch = async (q: string) => {
        setSearchQuery(q);
        if (q.length >= 2) {
            try {
                const res = await fetch(`/search?q=${q}`);
                const data = await res.json();
                setSearchResults(data);
                setShowSearch(true);
            } catch {
                setSearchResults([]);
            }
        } else {
            setSearchResults([]);
            setShowSearch(false);
        }
    };

    const toggleDark = () => {
        const newAppearance = appearance === 'dark' ? 'light' : 'dark';
        document.documentElement.classList.toggle('dark', newAppearance === 'dark');
        document.cookie = `appearance=${newAppearance};path=/;max-age=${60 * 60 * 24 * 365}`;
        router.post('/appearance', { appearance: newAppearance }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    if (!user) return null;

    return (
        <header className="sticky top-0 z-30 h-16 border-b border-border/50 bg-background/80 backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
            <div className="flex items-center justify-between h-full px-3 lg:px-5 gap-2">
                <div className="flex items-center gap-1.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onToggleMobile}
                        className="lg:hidden"
                    >
                        <Menu className="h-5 w-5" />
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onToggleCollapse}
                        className="hidden lg:inline-flex"
                    >
                        {collapsed ? <PanelLeft className="h-5 w-5" /> : <PanelLeftClose className="h-5 w-5" />}
                    </Button>

                    <div className="flex items-center gap-2 lg:hidden ml-1">
                        <img src="/logo/p2s3.png" alt="Logo" className="h-7 w-7 object-contain" />
                        <div className="leading-tight">
                            <p className="font-bold text-xs text-primary">Ubudiyah</p>
                            <p className="text-[10px] text-muted-foreground">P2S3 Sukorejo</p>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <div ref={searchRef} className="relative hidden sm:block">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Cari santri, panggilan, asrama..."
                            value={searchQuery}
                            onChange={(e) => handleSearch(e.target.value)}
                            onFocus={() => searchQuery.length >= 2 && setShowSearch(true)}
                            className="w-56 lg:w-64 pl-9 h-9"
                        />
                        {showSearch && searchResults.length > 0 && (
                            <div className="absolute top-full mt-1 w-full rounded-lg border bg-card shadow-lg overflow-hidden">
                                {searchResults.map((result, i) => (
                                    <Link
                                        key={i}
                                        href={result.url}
                                        onClick={() => { setShowSearch(false); setSearchQuery(''); }}
                                        className="flex items-center gap-3 px-4 py-2.5 hover:bg-accent text-sm transition-colors"
                                    >
                                        <span className="text-xs font-medium text-muted-foreground">{result.type}</span>
                                        <span>{result.label}</span>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    <Button variant="ghost" size="icon" onClick={toggleDark} className="h-9 w-9">
                        {appearance === 'dark' ? <Sun className="h-[18px] w-[18px]" /> : <Moon className="h-[18px] w-[18px]" />}
                    </Button>

                    <div ref={profileRef} className="relative">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setShowProfile(!showProfile)}
                            className="gap-2 h-9"
                        >
                            <div className="w-7 h-7 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
                                {user.name.charAt(0)}
                            </div>
                            <span className="hidden md:inline text-sm">{user.name}</span>
                        </Button>

                        {showProfile && (
                            <div className="absolute right-0 mt-1 w-48 rounded-lg border bg-card shadow-lg overflow-hidden">
                                <div className="px-4 py-3 border-b">
                                    <p className="text-sm font-medium">{user.name}</p>
                                    <p className="text-xs text-muted-foreground capitalize">{user.role.replace('_', ' ')}</p>
                                </div>
                                {user.permissions?.includes('view_audit') && (
                                    <Link
                                        href="/audit"
                                        onClick={() => setShowProfile(false)}
                                        className="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-accent transition-colors"
                                    >
                                        <Shield className="h-4 w-4" />
                                        Audit Log
                                    </Link>
                                )}
                                <button
                                    onClick={() => router.post('/logout')}
                                    className="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-accent transition-colors w-full text-left text-destructive"
                                >
                                    <LogOut className="h-4 w-4" />
                                    Logout
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}

function Shield(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
    );
}
