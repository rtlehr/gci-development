<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ContentPageForm from '@/components/content/ContentPageForm.vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    contentPage: any;
    pageTypes: Record<string, { label: string; description: string }>;
}>();
</script>

<template>
    <Head :title="`Edit ${contentPage.title}`" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Content and Help"
            :title="`Edit ${contentPage.title}`"
            description="Update the template, content, visibility, navigation, and publication timing."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/admin/content-pages">Back to Pages</Link>
                </Button>
            </template>
        </PageHeader>

        <ContentPageForm
            :page="contentPage"
            :page-types="pageTypes"
        />
    </PageContainer>
</template>
