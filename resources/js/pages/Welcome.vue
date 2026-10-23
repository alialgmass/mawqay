<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import { dashboard, login, register as registerRoute } from '@/routes'

withDefaults(
    defineProps<{
        canRegister: boolean
    }>(),
    { canRegister: true },
)

const page = usePage()
const dir = computed(() => (page.props.locale === 'ar' ? 'rtl' : 'ltr'))

const features = [
    {
        title: 'Fully Bilingual Interface',
        description: 'Seamlessly switch between Arabic and English. Optimized for local RTL requirements and Egyptian tax regulations.',
        icon: 'language',
        span: 'lg:col-span-2',
        class: 'bg-white border',
    },
    {
        title: 'High-Speed Offline Mode',
        description: 'Continue sales even when the internet goes down. Auto-syncs once back online.',
        icon: 'bolt',
        class: 'bg-primary text-white',
    },
    {
        title: 'E-Invoicing Ready',
        description: 'Fully compliant with Egyptian Tax Authority (ETA) requirements for electronic receipts.',
        icon: 'receipt_long',
        class: 'bg-emerald-50 text-emerald-900',
    },
    {
        title: 'Advanced Inventory',
        description: 'Track thousands of products across multiple branches with real-time stock alerts.',
        icon: 'inventory_2',
        span: 'lg:col-span-2',
        class: 'bg-white border',
    },
]

const plans = [
    {
        name: 'Basic',
        price: 'EGP 499',
        period: '/month',
        desc: 'Ideal for small kiosks',
        features: ['1 Cashier Terminal', '500 Products', 'Daily Sales Reports'],
        cta: 'Select Basic',
        popular: false,
    },
    {
        name: 'Advanced',
        price: 'EGP 999',
        period: '/month',
        desc: 'For growing retailers',
        features: ['5 Cashier Terminals', 'Unlimited Products', 'Inventory Management', 'Customer Loyalty Program'],
        cta: 'Select Advanced',
        popular: true,
    },
    {
        name: 'Pro',
        price: 'EGP 1999',
        period: '/month',
        desc: 'For multi-branch chains',
        features: ['Unlimited Terminals', 'Multi-Branch Sync', 'Custom API Integration', '24/7 Priority Support'],
        cta: 'Contact Sales',
        popular: false,
    },
]
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
    </Head>

    <div class="min-h-screen bg-background text-on-surface" :dir="dir">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 bg-surface border-b border-outline-variant">
            <nav class="flex items-center justify-between h-16 px-6 max-w-[1440px] mx-auto">
                <span class="text-xl font-black text-primary">SmartPOS Egypt</span>
                <div class="hidden md:flex items-center gap-6">
                    <a href="#hero" class="text-primary font-bold border-b-2 border-primary text-sm">Home</a>
                    <a href="#pricing" class="text-on-surface-variant hover:text-primary transition-colors text-sm">Pricing</a>
                    <Link :href="registerRoute()" class="bg-secondary text-white px-6 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition-opacity">
                        Get Started
                    </Link>
                </div>
                <span class="material-symbols-outlined text-primary md:hidden">menu</span>
            </nav>
        </header>

        <main>
            <!-- Hero Section -->
            <section id="hero" class="relative overflow-hidden bg-primary py-24 md:py-32">
                <div class="max-w-[1440px] mx-auto px-6 relative z-10 grid md:grid-cols-2 gap-8 items-center">
                    <div class="text-left space-y-6">
                        <h1 class="text-4xl md:text-5xl font-bold text-white leading-tight">
                            Powering Egypt's <span class="text-emerald-400">Retail Revolution</span>
                        </h1>
                        <p class="text-lg text-indigo-200/90 max-w-lg">
                            A robust, bilingual POS solution designed for the speed of Egyptian commerce. Trusted by thousands of stores from Cairo to Alexandria.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <Link :href="registerRoute()" class="bg-secondary text-white px-8 py-3 rounded-xl text-lg font-semibold text-center transition-transform active:scale-95 hover:opacity-90">
                                Start Free Trial
                            </Link>
                            <a href="#pricing" class="border border-indigo-400/30 text-white px-8 py-3 rounded-xl text-lg font-semibold text-center hover:bg-white/10 transition-colors">
                                View Plans
                            </a>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="bg-white/10 backdrop-blur-sm p-4 rounded-xl border border-white/20 shadow-2xl">
                            <div class="bg-white/5 rounded-lg h-64 flex items-center justify-center">
                                <span class="material-symbols-outlined text-6xl text-indigo-300/50">dashboard</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bento Features -->
            <section class="py-24 bg-background">
                <div class="max-w-[1440px] mx-auto px-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="f in features" :key="f.title" :class="[f.span || '', f.class, 'p-8 rounded-xl flex flex-col justify-between min-h-[200px]']">
                            <span class="material-symbols-outlined text-4xl mb-4" :class="f.class.includes('text-white') ? 'text-emerald-400' : 'text-secondary'">{{ f.icon }}</span>
                            <div>
                                <h3 class="text-xl font-semibold" :class="f.class.includes('text-white') ? '' : 'text-primary'">{{ f.title }}</h3>
                                <p class="mt-2 text-sm" :class="f.class.includes('text-white') ? 'text-white/80' : 'text-on-surface-variant'">{{ f.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Pricing -->
            <section id="pricing" class="py-24 bg-surface-container-low">
                <div class="max-w-[1440px] mx-auto px-6">
                    <div class="text-center mb-12">
                        <h2 class="text-3xl md:text-4xl font-bold text-primary">Simple, Transparent Pricing</h2>
                        <p class="text-lg text-on-surface-variant mt-2">Choose the plan that fits your business scale.</p>
                    </div>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div v-for="plan in plans" :key="plan.name"
                            class="bg-white p-8 rounded-xl border flex flex-col relative"
                            :class="plan.popular ? 'border-2 border-secondary shadow-xl scale-105 z-10' : 'border-outline-variant hover:shadow-lg transition-shadow'"
                        >
                            <div v-if="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-secondary text-white px-4 py-1 rounded-full text-sm font-bold">
                                Most Popular
                            </div>
                            <div class="mb-6">
                                <h3 class="text-2xl font-semibold text-primary">{{ plan.name }}</h3>
                                <p class="text-sm text-on-surface-variant">{{ plan.desc }}</p>
                            </div>
                            <div class="mb-6">
                                <span class="text-4xl font-bold text-primary">{{ plan.price }}</span>
                                <span class="text-on-surface-variant text-sm">{{ plan.period }}</span>
                            </div>
                            <ul class="space-y-3 mb-8 flex-1">
                                <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-2 text-sm">
                                    <span class="material-symbols-outlined text-secondary text-base">check_circle</span>
                                    {{ feat }}
                                </li>
                            </ul>
                            <button class="w-full py-2.5 rounded-lg font-bold transition-all text-sm"
                                :class="plan.popular ? 'bg-secondary text-white hover:opacity-90' : 'border border-primary text-primary hover:bg-primary/5'"
                            >
                                {{ plan.cta }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Signup Form -->
            <section class="py-24 bg-primary relative overflow-hidden">
                <div class="max-w-[1440px] mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
                    <div class="text-white">
                        <h2 class="text-3xl md:text-4xl font-bold">Ready to grow your business?</h2>
                        <p class="text-lg mt-4 text-indigo-200/80">Join over 5,000 Egyptian merchants who modernized their operations with SmartPOS. Start your 14-day free trial today.</p>
                        <div class="mt-8 flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-emerald-400">verified_user</span>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Secure Data</h4>
                                    <p class="text-sm text-indigo-200/70">Bank-level encryption for all your records.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-emerald-400">support_agent</span>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold">Local Support</h4>
                                    <p class="text-sm text-indigo-200/70">Dedicated team in Cairo and Alexandria.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-8 rounded-xl shadow-2xl">
                        <form class="space-y-4">
                            <div>
                                <Label for="welcome-store-name">Store Name</Label>
                                <Input id="welcome-store-name" placeholder="e.g. Al-Amal Supermarket" />
                            </div>
                            <div>
                                <Label for="welcome-owner-name">Owner Name</Label>
                                <Input id="welcome-owner-name" placeholder="Full Name" />
                            </div>
                            <div>
                                <Label for="welcome-email">Email Address</Label>
                                <Input id="welcome-email" type="email" placeholder="name@example.com" />
                            </div>
                            <div>
                                <Label for="welcome-password">Password</Label>
                                <Input id="welcome-password" type="password" placeholder="Min. 8 characters" />
                            </div>
                            <Link :href="registerRoute()" class="w-full block">
                                <Button class="w-full py-3 text-lg font-semibold bg-secondary hover:opacity-90">
                                    Create Account
                                </Button>
                            </Link>
                            <p class="text-center text-sm text-on-surface-variant mt-4">
                                By signing up, you agree to our <a href="#" class="text-primary underline">Terms of Service</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-surface-container-highest py-6 border-t border-outline-variant">
            <div class="max-w-[1440px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col items-center md:items-start">
                    <span class="text-xl font-black text-primary">SmartPOS Egypt</span>
                    <p class="text-sm text-on-surface-variant mt-1">&copy; {{ new Date().getFullYear() }} SmartPOS Tech Solutions. All rights reserved.</p>
                </div>
                <div class="flex gap-6">
                    <a href="#" class="text-on-surface-variant hover:text-primary text-sm">Privacy Policy</a>
                    <a href="#" class="text-on-surface-variant hover:text-primary text-sm">Terms</a>
                    <a href="#" class="text-on-surface-variant hover:text-primary text-sm">Contact Us</a>
                </div>
            </div>
        </footer>
    </div>
</template>
