<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import PageHelpButton from '@/components/ui/PageHelpButton.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const form = useForm<{ file: File | null }>({ file: null });
function submit(): void { form.post('/admin/data-imports', { forceFormData: true }); }
</script>

<template>
    <Head title="New Data Import" />
    <PageContainer class="space-y-6">
        <PageHeader eyebrow="Data Import" title="Upload Excel Workbook" description="Upload an .xlsx workbook. Insight will inspect its worksheets and Row 1 headers before any data is imported.">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline"><Link href="/admin/data-imports">Import History</Link></Button>
                    <PageHelpButton help-key="admin.data-imports.create" />
                </div>
            </template>
        </PageHeader>
        <form class="max-w-3xl space-y-6 rounded-xl border bg-background p-6 shadow-sm" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="data-import-file">Excel workbook</Label>
                <input id="data-import-file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="block w-full rounded-md border bg-background px-3 py-2 text-sm" @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                <p class="text-sm text-muted-foreground">Maximum file size: 20 MB. The workbook is stored in Insight's protected private storage.</p>
                <p v-if="form.errors.file" class="text-sm font-medium text-destructive">{{ form.errors.file }}</p>
            </div>
            <div class="flex gap-2"><Button type="submit" :disabled="form.processing || !form.file"><Upload class="mr-2 h-4 w-4" />Inspect Workbook</Button><Button as-child type="button" variant="outline"><Link href="/admin/data-imports">Cancel</Link></Button></div>
        </form>
    </PageContainer>
</template>
