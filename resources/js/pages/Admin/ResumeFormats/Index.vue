<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
defineProps<{
    formats: {
        id: number;
        name: string;
        description: string;
        version: number;
        is_active: boolean;
    }[];
}>();
</script>
<template>
    <Head title="Resume Formats" /><PageContainer class="space-y-6"
        ><PageHeader
            title="Resume Formats"
            description="Map labels in a two-column Word table to standard searchable resume fields."
            ><template #actions
                ><Button as-child
                    ><Link href="/admin/resume-formats/create"
                        >Create Format</Link
                    ></Button
                ></template
            ></PageHeader
        >
        <article
            v-for="f in formats"
            :key="f.id"
            class="flex items-center justify-between gap-4 rounded-lg border p-4"
        >
            <div>
                <h2 class="font-semibold">
                    {{ f.name }} v{{ f.version }} —
                    {{ f.is_active ? 'Active' : 'Inactive' }}
                </h2>
                <p class="text-sm text-muted-foreground">{{ f.description }}</p>
            </div>
            <Button as-child variant="outline"
                ><Link :href="`/admin/resume-formats/${f.id}/edit`"
                    >Edit</Link
                ></Button
            >
        </article></PageContainer
    >
</template>
