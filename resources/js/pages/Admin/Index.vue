<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { BookOpenCheck, Building2, KeyRound, Palette, ShieldCheck, Users } from 'lucide-vue-next'
import PageContainer from '@/components/layout/PageContainer.vue'
import PageHeader from '@/components/layout/PageHeader.vue'
import SectionHeader from '@/components/layout/SectionHeader.vue'
import StatCard from '@/components/data/StatCard.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuth } from '@/composables/useAuth'

const { username, can } = useAuth()

const tools = [
    { title: 'Users and Permissions', description: 'Manage user access and assigned permissions.', href: '/admin/users', icon: Users, permission: 'access_permissions' },
    { title: 'Roles', description: 'Maintain roles and their permission sets.', href: '/admin/roles', icon: ShieldCheck, permission: 'view_admin' },
    { title: 'Permissions', description: 'Review the application permission catalog.', href: '/admin/permissions', icon: KeyRound, permission: 'view_admin' },
    { title: 'Organizations', description: 'Manage the organizational structure used throughout IRAD.', href: '/admin/organizations', icon: Building2, permission: 'view_admin' },
    { title: 'Page Help', description: 'Maintain contextual help displayed on application pages.', href: '/admin/page-help', icon: BookOpenCheck, permission: 'view_admin' },
    { title: 'Component Showcase', description: 'Review reusable interface patterns and design standards.', href: '/admin/component-showcase', icon: Palette, permission: 'view_admin' },
]
</script>

<template>
    <Head title="Administration" />

    <PageContainer v-if="can('view_admin')">
        <PageHeader
            eyebrow="Administration"
            title="Admin Center"
            :description="`Welcome, ${username}. Manage application configuration, access, and shared resources.`"
        />

        <section class="space-y-4">
            <SectionHeader title="Administration overview" description="A quick view of the areas maintained from this workspace." />
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard label="Access Management" value="Users" description="Roles and permissions" :icon="ShieldCheck" />
                <StatCard label="Organization" value="Structure" description="Organizations, groups, and teams" :icon="Building2" />
                <StatCard label="User Support" value="Page Help" description="Contextual guidance" :icon="BookOpenCheck" />
            </div>
        </section>

        <section class="space-y-4">
            <SectionHeader title="Administration tools" description="Choose an area to review or update." />
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="tool in tools.filter((item) => can(item.permission))"
                    :key="tool.title"
                    :href="tool.href"
                    class="group rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <Card class="h-full transition-colors group-hover:border-primary/40 group-hover:bg-muted/20">
                        <CardHeader>
                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-lg border bg-muted/50 text-muted-foreground group-hover:text-foreground">
                                <component :is="tool.icon" class="h-5 w-5" aria-hidden="true" />
                            </div>
                            <CardTitle class="text-base">{{ tool.title }}</CardTitle>
                            <CardDescription>{{ tool.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="text-sm font-medium text-primary">Open tool →</CardContent>
                    </Card>
                </Link>
            </div>
        </section>
    </PageContainer>
</template>
