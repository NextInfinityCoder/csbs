import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardPen,
    LayoutGrid,
    Monitor,
    Swords,
    Ticket,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as betsIndex } from '@/routes/bets';
import { index as cashiersIndex } from '@/routes/cashiers';
import { index as encodeIndex } from '@/routes/encode';
import { index as eventsIndex } from '@/routes/events';
import { index as fightsIndex } from '@/routes/fights';
import { index as stationsIndex } from '@/routes/stations';
import type { Auth, NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        visibleTo: ['admin'],
    },
    {
        title: 'Encode',
        href: encodeIndex(),
        icon: ClipboardPen,
        visibleTo: ['encoder'],
    },
    {
        title: 'Events',
        href: eventsIndex(),
        icon: CalendarDays,
    },
    {
        title: 'Fight List',
        href: fightsIndex(),
        icon: Swords,
    },
    {
        title: 'Bet List',
        href: betsIndex(),
        icon: Ticket,
    },
    {
        title: 'Cashier Management',
        href: cashiersIndex(),
        icon: Wallet,
        visibleTo: ['admin'],
    },
    {
        title: 'Station Management',
        href: stationsIndex(),
        icon: Monitor,
        visibleTo: ['admin'],
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const userType = auth.user.type;
    const visibleNavItems = mainNavItems.filter(
        (item) => !item.visibleTo || item.visibleTo.includes(userType),
    );
    const homeHref =
        userType === 'admin'
            ? dashboard()
            : userType === 'encoder'
              ? encodeIndex()
              : eventsIndex();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={visibleNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
