<script setup lang="ts">
import {
    Link,
} from '@inertiajs/vue3';
import {
    LayoutGrid,
    ShoppingCart,
    Package,
    Users,
    BarChart3,
    Settings,
    LifeBuoy,
    Send
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import {
    dashboard
} from '@/routes';
import {
    index as posIndex
} from '@/routes/pos';
import {
    index as productsIndex
} from '@/routes/inventory/products';
import {
    index as customersIndex
} from '@/routes/customers';
import type {
    NavItem
} from '@/types';
import {
    useDirection
} from '@/composables/useDirection';

const {
    direction
} = useDirection();

const mainNavItems: NavItem[] = [{
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'POS',
        href: posIndex(),
        icon: ShoppingCart,
    },
    {
        title: 'Products',
        href: productsIndex(),
        icon: Package,
    },
    {
        title: 'Customers',
        href: customersIndex(),
        icon: Users,
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart3,
    },
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [{
        title: 'Support',
        href: '#',
        icon: LifeBuoy,
    },
    {
        title: 'Feedback',
        href: '#',
        icon: Send,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" :side="direction === 'rtl' ? 'right' : 'left'">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
