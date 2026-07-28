<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import HeaderAlertBell from '@/components/HeaderAlertBell.vue';
import PageHelpButton from '@/components/ui/PageHelpButton.vue';
import { useAuth } from '@/composables/useAuth';
import { Button } from '@/components/ui/button';
import PublicPortalNavigation from './PublicPortalNavigation.vue';
import PortalAccountMenu from './PortalAccountMenu.vue';

const { user, username, can, hasRole } = useAuth();
const mobileOpen = ref(false);

const canOpenAdminDashboard = computed(() =>
    can('view_admin') || hasRole('owner') || hasRole('admin'),
);
</script>

<template>
    <header class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto flex h-20 max-w-7xl items-center gap-5 px-4 sm:px-6 lg:px-8">
            <Link href="/" class="flex shrink-0 items-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005c43]">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#005c43] text-white">
                    <AppLogoIcon class="h-6 w-6" />
                </span>
                <span>
                    <span class="block text-sm font-semibold tracking-wide text-[#005c43]">ZION</span>
                    <span class="block text-base font-bold text-[#3a3a3a]">INSIGHT Portal</span>
                </span>
            </Link>

            <div class="min-w-0 flex-1">
                <PublicPortalNavigation />
            </div>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <div
                    v-if="user"
                    class="hidden max-w-48 truncate px-2 text-sm font-medium text-[#3a3a3a] md:block"
                    :title="username"
                >
                    {{ username }}
                </div>

                <Button
                    v-if="user && canOpenAdminDashboard"
                    as-child
                    class="hidden gap-2 bg-[#005c43] text-white hover:bg-[#004735] sm:inline-flex"
                >
                    <Link href="/admin">
                        <LayoutDashboard class="h-4 w-4" />
                        Dashboard
                    </Link>
                </Button>

                <HeaderAlertBell v-if="user" />
                <PageHelpButton />
                <PortalAccountMenu />
                <Button
                    variant="ghost"
                    size="icon"
                    class="lg:hidden"
                    aria-label="Toggle navigation"
                    :aria-expanded="mobileOpen"
                    @click="mobileOpen = !mobileOpen"
                >
                    <Menu class="h-5 w-5" />
                </Button>
            </div>
        </div>

        <div v-if="mobileOpen" class="border-t border-[#e3e3e3] px-4 py-3 lg:hidden">
            <nav aria-label="Mobile navigation" class="mx-auto grid max-w-7xl gap-1">
                <div v-if="user" class="mb-2 border-b border-[#e3e3e3] px-3 pb-3 text-sm font-semibold text-[#3a3a3a]">
                    {{ username }}
                </div>
                <Link href="/" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">Home</Link>
                <Link v-if="user" href="/portal/dashboard" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">My Portal</Link>
                <Link
                    v-if="user && canOpenAdminDashboard"
                    href="/admin"
                    class="flex items-center gap-2 rounded-md bg-[#005c43] px-3 py-2 font-semibold text-white hover:bg-[#004735]"
                    @click="mobileOpen = false"
                >
                    <LayoutDashboard class="h-4 w-4" />
                    Dashboard
                </Link>
                <a href="/#program" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">Program</a>
                <a href="/#resources" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">Resources</a>
                <a href="/#faqs" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">FAQs</a>
                <a href="/#support" class="rounded-md px-3 py-2 font-medium hover:bg-[#005c43]/10" @click="mobileOpen = false">Support</a>
            </nav>
        </div>
    </header>
</template>
