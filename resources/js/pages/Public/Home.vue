<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CircleHelp, ContactRound, FileText, LifeBuoy, UsersRound } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    isAuthenticated: boolean;
    program: {
        eyebrow: string;
        name: string;
        summary: string;
        contract_year: string;
        contract_number: string;
        period_of_performance: string;
    };
    homeContent: {
        primary_action_label: string;
        secondary_action_label: string;
        program_contacts_title: string;
        program_contacts_description: string;
        resources_title: string;
        resources_description: string;
        requests_title: string;
        requests_description: string;
        faqs_title: string;
        faqs_description: string;
        support_title: string;
        support_description: string;
        pmo_title: string;
        pmo_description: string;
    };
}>();
</script>

<template>
    <Head :title="program.name" />

    <section class="border-b border-[var(--portal-border)] bg-[var(--portal-surface)]">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_0.8fr] lg:px-8 lg:py-20">
            <div class="max-w-3xl">
                <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-[var(--portal-primary)]">{{ program.eyebrow }}</p>
                <h1 class="text-4xl font-bold tracking-tight text-[var(--portal-text)] sm:text-5xl">Welcome to the {{ program.name }}</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-[color:var(--portal-text-muted)]">{{ program.summary }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link v-if="isAuthenticated" href="/portal/dashboard">
                        <Button class="bg-[var(--portal-primary)] text-white hover:bg-[var(--portal-primary-hover)]">
                            {{ homeContent.primary_action_label }}
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                    <a href="#program">
                        <Button variant="outline" class="border-[var(--portal-border)]">
                            {{ homeContent.secondary_action_label }}
                        </Button>
                    </a>
                </div>
            </div>

            <aside id="program" class="rounded-2xl border border-[var(--portal-border)] bg-[var(--portal-background)] p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[var(--portal-primary)]">{{ homeContent.secondary_action_label }}</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="font-semibold">Contract year</dt><dd class="mt-1 text-[color:var(--portal-text-muted)]">{{ program.contract_year }}</dd></div>
                    <div><dt class="font-semibold">Contract number</dt><dd class="mt-1 text-[color:var(--portal-text-muted)]">{{ program.contract_number }}</dd></div>
                    <div><dt class="font-semibold">Period of performance</dt><dd class="mt-1 text-[color:var(--portal-text-muted)]">{{ program.period_of_performance }}</dd></div>
                </dl>
            </aside>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <UsersRound class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.program_contacts_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.program_contacts_description }}</p>
            </article>
            <article id="resources" class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <BookOpen class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.resources_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.resources_description }}</p>
            </article>
            <article class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <FileText class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.requests_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.requests_description }}</p>
            </article>
            <article id="faqs" class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <CircleHelp class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.faqs_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.faqs_description }}</p>
            </article>
            <article id="support" class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <LifeBuoy class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.support_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.support_description }}</p>
            </article>
            <article class="rounded-xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-sm">
                <ContactRound class="h-7 w-7 text-[var(--portal-primary)]" />
                <h2 class="mt-4 text-lg font-bold">{{ homeContent.pmo_title }}</h2>
                <p class="mt-2 text-sm leading-6 text-[color:var(--portal-text-muted)]">{{ homeContent.pmo_description }}</p>
            </article>
        </div>
    </section>
</template>
