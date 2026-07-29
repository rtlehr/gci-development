<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarDays } from 'lucide-vue-next';
import PublicFaqAccordion from '@/components/content/PublicFaqAccordion.vue';

type ContentPage = {
    title: string;
    slug: string;
    summary: string | null;
    content_html: string | null;
    page_type: string;
    help_key: string | null;
    updated_at: string | null;
    faq_items: Array<{
        id?: number;
        question: string;
        answer: string;
    }>;
};

const props = defineProps<{
    contentPage: ContentPage;
}>();

const eyebrowByType: Record<string, string> = {
    standard: 'Program Information',
    faq: 'Frequently Asked Questions',
    contact_directory: 'Program Contacts',
    resource_library: 'Program Resources',
    announcement: 'Program Announcement',
    policy: 'Policy and Documentation',
};
</script>

<template>
    <Head :title="contentPage.title" />

    <article class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <header class="border-b border-[var(--portal-border)] pb-8">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[var(--portal-primary)]">
                {{ eyebrowByType[contentPage.page_type] ?? 'Program Information' }}
            </p>

            <h1 class="mt-3 text-4xl font-bold tracking-tight text-[var(--portal-text)]">
                {{ contentPage.title }}
            </h1>

            <p
                v-if="contentPage.summary"
                class="mt-4 max-w-3xl text-lg leading-8 text-[color:var(--portal-text-muted)]"
            >
                {{ contentPage.summary }}
            </p>

            <div
                v-if="contentPage.updated_at"
                class="mt-4 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <CalendarDays class="h-4 w-4" />
                Updated {{ new Date(contentPage.updated_at).toLocaleDateString() }}
            </div>
        </header>

        <div
            v-if="contentPage.content_html"
            class="prose prose-slate mt-8 max-w-none prose-headings:text-[var(--portal-text)] prose-a:text-[var(--portal-primary)]"
            v-html="contentPage.content_html"
        />

        <section
            v-if="contentPage.page_type === 'faq' && contentPage.faq_items.length"
            class="mt-10"
            aria-labelledby="faq-list-heading"
        >
            <h2 id="faq-list-heading" class="sr-only">Questions and answers</h2>
            <PublicFaqAccordion :items="contentPage.faq_items" />
        </section>
    </article>
</template>
