<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContentPageForm from '@/components/content/ContentPageForm.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    pageTypes: Record<string, { label: string; description: string }>;
}>();
</script>

<template>
    <Head title="Create Content Page" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Content and Help"
            title="Create Content Page"
            description="Choose a template and create a public, portal, or shared informational page."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/admin/content-pages">Back to Pages</Link>
                </Button>
            </template>
        </PageHeader>

        <ContentPageForm :page-types="pageTypes" />
    </PageContainer>
</template>
