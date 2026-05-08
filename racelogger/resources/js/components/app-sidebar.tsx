import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, Car, Download, Flag, LayoutGrid, List, MapPin, Trophy, Globe } from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';
import { dashboard } from '@/routes';
import worlds from '@/routes/worlds';
import series from '@/routes/series';
import seasons from '@/routes/seasons';
import tracks from '@/routes/tracks';
import records from '@/routes/records';
import teams from '@/routes/teams';

export function AppSidebar() {
    const activeWorldId = usePage().props.activeWorld?.id ?? null;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
            icon: LayoutGrid,
        },
        {
            title: 'Worlds',
            href: worlds.index().url,
            icon: Globe,
        },
        {
            title: 'Series',
            href: series.index().url,
            icon: List,
        },
        {
            title: 'Seasons',
            href: seasons.index().url,
            icon: CalendarDays,
        },
        {
            title: 'Tracks',
            href: tracks.index().url,
            icon: MapPin,
        },
        {
            title: 'Teams',
            href: teams.index().url,
            icon: Car,
        },
        {
            title: 'Records',
            href: records.index().url,
            icon: Trophy,
        },
        ...(activeWorldId === 1
            ? [{ title: 'Import', href: '/import', icon: Download } as NavItem]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Select World',
            href: '/',
            icon: Flag,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard().url} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
            </SidebarFooter>
        </Sidebar>
    );
}
