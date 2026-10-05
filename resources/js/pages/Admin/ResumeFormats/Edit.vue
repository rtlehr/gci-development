<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
const props = defineProps<{
    format: {
        id: number;
        name: string;
        description: string;
        is_active: boolean;
        version: number;
        mappings: Record<string, string>;
        column_orders?: Record<string, string[]>;
    } | null;
    defaults: Record<string, string>;
    columnDefaults: Record<string, string[]>;
}>();
const form = useForm({
    name: props.format?.name ?? '',
    description: props.format?.description ?? '',
    is_active: props.format?.is_active ?? true,
    mappings: { ...(props.format?.mappings ?? props.defaults) },
    column_orders: Object.fromEntries(
        Object.entries(props.format?.column_orders ?? props.columnDefaults).map(
            ([section, cols]) => [section, cols.join(', ')],
        ),
    ),
});
function save() {
    if (props.format) {
        form.put(`/admin/resume-formats/${props.format.id}`);
    } else {
        form.post('/admin/resume-formats');
    }
}
function label(v: string) {
    return v.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>
<template>
    <Head title="Resume Format Builder" /><PageContainer class="space-y-6"
        ><PageHeader
            title="Resume Format Builder"
            back-href="/admin/resume-formats"
            description="Enter the labels used by this document format. Separate alternative labels with |. Leave unused fields blank."
        />
        <div class="space-y-2 rounded-lg border p-4 text-sm">
            <p>
                Supported layout: a Word table with the label in the left cell
                and the value in the right cell. Single values may span
                paragraphs.
            </p>
            <p>
                Education: one paragraph per entry, with date, degree, major,
                institution, city, state separated by commas.
            </p>
            <p>
                Certifications: date, certification, granting institution.
                Languages: language, reading score, writing score, evaluation
                date (or Language (Reading/Writing) Evaluation Date: MM/YY on
                one line). Technologies: comma-separated values or separate
                paragraphs.
            </p>
            <p>
                Changing mappings or the format name saves a new version. Older
                versions remain available until you deactivate them. Every
                import requires review.
            </p>
        </div>
        <form class="max-w-3xl space-y-4" @submit.prevent="save">
            <label class="block space-y-1"
                ><span>Format name</span
                ><input
                    v-model="form.name"
                    required
                    class="w-full rounded-md border bg-background p-2" /></label
            ><label class="block space-y-1"
                ><span>Description</span
                ><textarea
                    v-model="form.description"
                    class="w-full rounded-md border bg-background p-2"
                /></label
            ><label class="flex gap-2"
                ><input v-model="form.is_active" type="checkbox" />Available for
                uploads</label
            >
            <div class="grid gap-4 md:grid-cols-2">
                <label
                    v-for="(_, field) in defaults"
                    :key="field"
                    class="block space-y-1"
                    ><span>{{ label(field) }} → Document label(s)</span
                    ><input
                        v-model="form.mappings[field]"
                        class="w-full rounded-md border bg-background p-2"
                /></label>
            </div>
            <fieldset class="space-y-3 rounded-lg border p-4">
                <legend class="px-2 font-semibold">
                    Column order for repeating entries
                </legend>
                <p class="text-sm text-muted-foreground">
                    Use the column names shown, once each. Put them in the order
                    used in your document's comma-separated entries.
                </p>
                <label
                    v-for="(cols, section) in columnDefaults"
                    :key="section"
                    class="block space-y-1"
                >
                    <span>{{ label(section) }} ({{ cols.join(', ') }})</span>
                    <input
                        v-model="form.column_orders[section]"
                        required
                        class="w-full rounded-md border bg-background p-2"
                    />
                </label>
            </fieldset>
            <p
                v-for="(error, key) in form.errors"
                :key="key"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ error }}
            </p>
            <Button type="submit" :disabled="form.processing"
                >Save Format</Button
            >
        </form></PageContainer
    >
</template>
