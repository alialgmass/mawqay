<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface TopProduct {
    product_id: number | null;
    name: string;
    total_qty: string;
    total_revenue: string;
}

interface LowStockProduct {
    id: number;
    name: string;
    category_id: number | null;
    category: { id: number; name: string } | null;
    stock_qty: string;
    min_stock: string;
}

interface SalesTrendPoint {
    date: string;
    total: number;
}

interface PaymentBreakdown {
    payment_method: string;
    count: number;
    total: number;
    percentage: number;
}

interface DashboardMetrics {
    today_sales: number;
    yesterday_sales: number;
    yesterday_transactions: number;
    yesterday_avg_sale: number;
    transaction_count: number;
    avg_sale: number;
    low_stock_count: number;
    low_stock_products: LowStockProduct[];
    top_product: TopProduct | null;
    sales_trend: SalesTrendPoint[];
    payment_breakdown: PaymentBreakdown[];
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    },
});

const props = defineProps<{
    metrics: DashboardMetrics;
}>();

const trend = (current: number, previous: number) => {
    if (previous === 0) return { value: '—', up: true };
    const pct = ((current - previous) / previous) * 100;
    return {
        value: `${pct >= 0 ? '+' : ''}${pct.toFixed(1)}%`,
        up: pct >= 0,
    };
};

const maxTrend = Math.max(...props.metrics.sales_trend.map((d) => d.total), 1);

const circumference = 2 * Math.PI * 80;
const paymentColors = ['bg-primary', 'bg-secondary', 'bg-amber-600', 'bg-blue-500'];

const paymentStrokes = ['#070235', '#006c4a', '#cf7100', '#3b82f6'];

const donutSegments = () => {
    let offset = 0;
    return props.metrics.payment_breakdown.map((pm) => {
        const dashLength = (pm.percentage / 100) * circumference;
        const seg = {
            ...pm,
            dashLength,
            offset: -offset,
        };
        offset += dashLength;
        return seg;
    });
};

const paymentLabels: Record<string, string> = {
    cash: 'Cash',
    card: 'Card',
    mixed: 'Mixed',
    deferred: 'Deferred',
};

const priorityLevel = (stock: number, min: number) => {
    const ratio = stock / min;
    if (ratio <= 0.3) return { label: 'Critical', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
    return { label: 'Warning', class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
};

const quickLinks = [
    { title: 'Point of Sale', icon: 'point_of_sale', href: '/pos', desc: 'Process sales and manage transactions', color: 'bg-primary' },
    { title: 'Products', icon: 'inventory_2', href: '/products', desc: 'Manage your product catalog and inventory', color: 'bg-secondary' },
    { title: 'Customers', icon: 'group', href: '/customers', desc: 'View customer profiles and debt management', color: 'bg-amber-600' },
    { title: 'Reports', icon: 'assessment', href: '/reports', desc: 'Sales analytics and business insights', color: 'bg-blue-600' },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-6 max-w-[1600px] mx-auto">
        <!-- KPI Cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative rounded-xl border bg-card p-5 flex flex-col justify-between h-32 overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[80px]">payments</span>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Total Sales Today</p>
                    <p class="text-3xl font-bold text-primary mt-1">${{ Number(metrics.today_sales).toFixed(2) }}</p>
                </div>
                <div
                    class="flex items-center gap-1 text-xs font-bold"
                    :class="trend(metrics.today_sales, metrics.yesterday_sales).up ? 'text-secondary' : 'text-destructive'"
                >
                    <span class="material-symbols-outlined text-sm">{{ trend(metrics.today_sales, metrics.yesterday_sales).up ? 'trending_up' : 'trending_down' }}</span>
                    {{ trend(metrics.today_sales, metrics.yesterday_sales).value }} vs yesterday
                </div>
            </div>
            <div class="relative rounded-xl border bg-card p-5 flex flex-col justify-between h-32 overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[80px]">receipt_long</span>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Transactions</p>
                    <p class="text-3xl font-bold text-primary mt-1">{{ metrics.transaction_count }}</p>
                </div>
                <div
                    class="flex items-center gap-1 text-xs font-bold"
                    :class="trend(metrics.transaction_count, metrics.yesterday_transactions).up ? 'text-secondary' : 'text-destructive'"
                >
                    <span class="material-symbols-outlined text-sm">{{ trend(metrics.transaction_count, metrics.yesterday_transactions).up ? 'trending_up' : 'trending_down' }}</span>
                    {{ trend(metrics.transaction_count, metrics.yesterday_transactions).value }} vs yesterday
                </div>
            </div>
            <div class="relative rounded-xl border bg-card p-5 flex flex-col justify-between h-32 overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-[80px]">average_pace</span>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Average Sale</p>
                    <p class="text-3xl font-bold text-primary mt-1">${{ Number(metrics.avg_sale).toFixed(2) }}</p>
                </div>
                <div
                    class="flex items-center gap-1 text-xs font-bold"
                    :class="trend(metrics.avg_sale, metrics.yesterday_avg_sale).up ? 'text-secondary' : 'text-destructive'"
                >
                    <span class="material-symbols-outlined text-sm">{{ trend(metrics.avg_sale, metrics.yesterday_avg_sale).up ? 'trending_up' : 'trending_down' }}</span>
                    {{ trend(metrics.avg_sale, metrics.yesterday_avg_sale).value }} vs yesterday
                </div>
            </div>
            <div class="rounded-xl bg-primary text-primary-foreground p-5 flex flex-col justify-center items-center text-center">
                <span class="material-symbols-outlined text-secondary text-3xl mb-1">bolt</span>
                <p class="font-bold text-xs uppercase tracking-widest opacity-80">Quick Action</p>
                <p class="text-base mt-2">Open POS Terminal</p>
            </div>
        </section>

        <!-- Quick Links -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link
                v-for="link in quickLinks"
                :key="link.title"
                :href="link.href"
                class="group rounded-xl border bg-card p-6 hover:shadow-md transition-all hover:-translate-y-0.5"
            >
                <div class="flex items-center gap-4">
                    <div :class="[link.color, 'w-12 h-12 rounded-xl flex items-center justify-center']">
                        <span class="material-symbols-outlined text-white text-2xl">{{ link.icon }}</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">{{ link.title }}</h3>
                        <p class="text-sm text-muted-foreground">{{ link.desc }}</p>
                    </div>
                </div>
            </Link>
        </section>

        <!-- Charts Row -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sales Trend -->
            <div class="lg:col-span-2 rounded-xl border bg-card p-5">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Sales Performance</h3>
                        <p class="text-sm text-muted-foreground">Revenue trend over the last 30 days</p>
                    </div>
                </div>
                <div class="h-56 w-full flex items-end gap-1 px-2">
                    <div
                        v-for="(point, idx) in metrics.sales_trend"
                        :key="point.date"
                        class="flex-1 rounded-t-sm transition-all duration-200 hover:opacity-80"
                        :class="idx === metrics.sales_trend.length - 1 ? 'bg-primary' : 'bg-primary/40'"
                        :style="{ height: `${Math.max((point.total / maxTrend) * 100, 2)}%` }"
                        :title="`${point.date}: $${Number(point.total).toFixed(2)}`"
                    />
                </div>
                <div class="flex justify-between mt-2 px-2 text-xs text-muted-foreground">
                    <span>{{ metrics.sales_trend[0]?.date }}</span>
                    <span>{{ metrics.sales_trend[Math.floor(metrics.sales_trend.length / 2)]?.date }}</span>
                    <span>{{ metrics.sales_trend[metrics.sales_trend.length - 1]?.date }}</span>
                </div>
            </div>

            <!-- Payment Methods Donut -->
            <div class="rounded-xl border bg-card p-5 flex flex-col">
                <h3 class="text-lg font-semibold mb-4">Payment Methods</h3>
                <div class="flex-1 flex flex-col items-center justify-center relative">
                    <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 192 192">
                        <circle cx="96" cy="96" fill="transparent" r="80" stroke="#f1f5f9" stroke-width="20" />
                        <circle
                            v-for="(seg, idx) in donutSegments()"
                            :key="seg.payment_method"
                            cx="96"
                            cy="96"
                            fill="transparent"
                            r="80"
                            :stroke="paymentStrokes[idx]"
                            :stroke-dasharray="`${seg.dashLength} ${circumference}`"
                            :stroke-dashoffset="seg.offset"
                            stroke-width="20"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold">{{ metrics.payment_breakdown.length }}</span>
                        <span class="text-[10px] uppercase font-bold text-muted-foreground">Methods</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-4">
                    <div
                        v-for="(pm, idx) in metrics.payment_breakdown"
                        :key="pm.payment_method"
                        class="flex items-center gap-2"
                    >
                        <div
                            class="w-3 h-3 rounded-full shrink-0"
                            :class="paymentColors[idx]"
                        />
                        <span class="text-sm">{{ paymentLabels[pm.payment_method] ?? pm.payment_method }} ({{ pm.percentage }}%)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Low Stock Alerts Table -->
        <section class="rounded-xl border bg-card overflow-hidden">
            <div class="px-5 py-3 border-b flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold">Low Stock Alerts</h3>
                    <p class="text-sm text-muted-foreground">Items requiring immediate reorder</p>
                </div>
                <Button variant="outline" size="sm">Export List</Button>
            </div>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Product Name</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead class="text-right">Current Stock</TableHead>
                        <TableHead class="text-right">Min. Stock</TableHead>
                        <TableHead class="text-center">Priority</TableHead>
                        <TableHead class="text-right">Action</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="metrics.low_stock_products.length === 0">
                        <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                            No low stock items
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="product in metrics.low_stock_products" :key="product.id">
                        <TableCell class="font-medium">{{ product.name }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ product.category?.name ?? '—' }}</TableCell>
                        <TableCell
                            class="text-right font-mono text-sm"
                            :class="{ 'text-destructive': Number(product.stock_qty) <= Number(product.min_stock) * 0.3 }"
                        >
                            {{ Number(product.stock_qty).toFixed(0) }} units
                        </TableCell>
                        <TableCell class="text-right font-mono text-sm">{{ Number(product.min_stock).toFixed(0) }} units</TableCell>
                        <TableCell class="text-center">
                            <span
                                class="px-2 py-0.5 rounded-full text-[11px] font-bold uppercase"
                                :class="priorityLevel(Number(product.stock_qty), Number(product.min_stock)).class"
                            >
                                {{ priorityLevel(Number(product.stock_qty), Number(product.min_stock)).label }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="link" size="sm">Reorder</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </section>

        <!-- Top Product Card -->
        <section v-if="metrics.top_product" class="rounded-xl border bg-card p-5">
            <h3 class="text-lg font-semibold mb-3">Top Product</h3>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-muted-foreground">Name</p>
                    <p class="font-medium">{{ metrics.top_product.name }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Qty Sold</p>
                    <p class="font-medium">{{ Number(metrics.top_product.total_qty).toFixed(0) }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">Revenue</p>
                    <p class="font-medium">${{ Number(metrics.top_product.total_revenue).toFixed(2) }}</p>
                </div>
            </div>
        </section>

        <!-- Welcome Section -->
        <section class="rounded-xl border bg-card p-8">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-3xl text-primary">store</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold">Welcome to your Store!</h2>
                    <p class="text-muted-foreground mt-1">
                        Get started by adding products, managing inventory, or processing sales in the POS terminal.
                        Use the quick links above to jump to any module.
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
