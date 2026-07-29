<script setup lang="ts">
import { computed, provide, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FlashMessages from '@/components/FlashMessages.vue';
import HelpPanel from '@/components/ui/HelpPanel.vue';
import ImpersonationBanner from '@/components/public-portal/ImpersonationBanner.vue';
import PublicPortalFooter from '@/components/public-portal/PublicPortalFooter.vue';
import PublicPortalHeader from '@/components/public-portal/PublicPortalHeader.vue';

const props = defineProps<{ helpKey?: string }>();
const page = usePage();
const helpOpen = ref(false);

const siteSettings = computed(() => page.props.siteSettings as {
    branding: {
        primary_color: string;
        primary_hover_color: string;
        surface_color: string;
        page_background_color: string;
        border_color: string;
        text_color: string;
    };
});

const themeStyle = computed(() => ({
    '--portal-primary': siteSettings.value.branding.primary_color,
    '--portal-primary-hover': siteSettings.value.branding.primary_hover_color,
    '--portal-primary-soft': `color-mix(in srgb, ${siteSettings.value.branding.primary_color} 10%, transparent)`,
    '--portal-surface': siteSettings.value.branding.surface_color,
    '--portal-background': siteSettings.value.branding.page_background_color,
    '--portal-border': siteSettings.value.branding.border_color,
    '--portal-text': siteSettings.value.branding.text_color,
    '--portal-text-muted': `${siteSettings.value.branding.text_color}bf`,
}));

const currentHelpKey = computed(() => {
    if (props.helpKey?.trim()) return props.helpKey;
    return (page.component ?? '').replace(/\//g, '.').toLowerCase();
});

const openHelpPanel = () => { helpOpen.value = true; };
const closeHelpPanel = () => { helpOpen.value = false; };
const toggleHelpPanel = () => { helpOpen.value = !helpOpen.value; };

provide('openHelpPanel', openHelpPanel);
provide('closeHelpPanel', closeHelpPanel);
provide('toggleHelpPanel', toggleHelpPanel);
provide('helpOpen', helpOpen);
provide('currentHelpKey', currentHelpKey);
</script>

<template>
    <div
        class="public-portal-theme flex min-h-screen w-full bg-[var(--portal-background)] text-[var(--portal-text)]"
        :style="themeStyle"
    >
        <a
            href="#main-content"
            class="sr-only z-50 rounded bg-[var(--portal-surface)] px-4 py-2 text-[var(--portal-primary)] focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
        >
            Skip to main content
        </a>

        <div class="min-w-0 flex-1">
            <ImpersonationBanner />
            <PublicPortalHeader />
            <FlashMessages />
            <main id="main-content" tabindex="-1">
                <slot />
            </main>
            <PublicPortalFooter />
        </div>

        <HelpPanel :open="helpOpen" :help-key="currentHelpKey" @close="closeHelpPanel" />
    </div>
</template>
