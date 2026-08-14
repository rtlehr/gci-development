<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const settings = computed(() => page.props.siteSettings as {
    features?: { support_tickets?: boolean };
    footer: {
        copyright_name: string;
        support_label: string;
        faqs_label: string;
    };
});
const footer = computed(() => settings.value.footer);
const supportEnabled = computed(() => settings.value.features?.support_tickets ?? true);
</script>

<template>
    <footer class="border-t border-[var(--portal-border)] bg-[var(--portal-surface)]">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-8 text-sm text-[color:var(--portal-text-muted)] sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
            <p>© {{ new Date().getFullYear() }} {{ footer.copyright_name }}</p>
            <div class="flex gap-5">
                <a v-if="supportEnabled" href="/#support" class="hover:text-[var(--portal-primary)] hover:underline">{{ footer.support_label }}</a>
                <a href="/#faqs" class="hover:text-[var(--portal-primary)] hover:underline">{{ footer.faqs_label }}</a>
            </div>
        </div>
    </footer>
</template>
