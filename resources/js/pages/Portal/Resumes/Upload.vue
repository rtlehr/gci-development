<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
const props = defineProps<{
    formats: {
        id: number;
        name: string;
        version: number;
        description: string;
    }[];
    personId: number | null;
}>();
const form = useForm({
    resume_format_id: props.formats[0]?.id ?? '',
    file: null as File | null,
    person_id: props.personId,
});
function selectFile(event: Event) {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
</script>
<template>
    <Head title="Upload Resume" /><PageContainer class="space-y-6">
        <PageHeader
            title="Upload Resume"
            back-href="/portal/resumes"
            description="Choose the DOCX format. Review and correct extracted values before making the resume searchable."
        />
        <form
            class="max-w-2xl space-y-5 rounded-lg border p-6"
            @submit.prevent="
                form.post('/portal/resumes/upload', { forceFormData: true })
            "
        >
            <label class="block space-y-2"
                ><span>Resume format</span
                ><select
                    v-model="form.resume_format_id"
                    required
                    class="w-full rounded-md border bg-background p-2"
                >
                    <option v-for="f in formats" :key="f.id" :value="f.id">
                        {{ f.name }} — v{{ f.version }}
                    </option>
                </select></label
            >
            <p class="text-sm text-muted-foreground">
                {{
                    formats.find((f) => f.id === Number(form.resume_format_id))
                        ?.description
                }}
            </p>
            <label class="block space-y-2"
                ><span>Word document (.docx, up to 20 MB)</span
                ><input
                    type="file"
                    accept=".docx"
                    required
                    class="block w-full rounded-md border p-2"
                    @change="selectFile"
            /></label>
            <p
                v-for="(error, key) in form.errors"
                :key="key"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ error }}
            </p>
            <progress
                v-if="form.progress"
                :value="form.progress.percentage"
                max="100"
                class="w-full"
                aria-label="Upload progress"
            />
            <p v-if="!formats.length" role="alert">
                An administrator must activate a resume format before uploading.
            </p>
            <Button
                type="submit"
                :disabled="form.processing || !formats.length"
                >{{
                    form.processing ? 'Processing…' : 'Upload and Review'
                }}</Button
            >
            <Button as-child variant="outline" class="ml-2"
                ><Link href="/portal/resumes">Cancel</Link></Button
            >
        </form></PageContainer
    >
</template>
