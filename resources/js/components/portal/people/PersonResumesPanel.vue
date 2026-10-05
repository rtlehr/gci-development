<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useAuth } from '@/composables/useAuth';
type ResumeItem = {
    id: number;
    name: string;
    status: string;
    original_filename: string;
    created_at: string;
    format_snapshot: { name: string; version: number };
};
const props = defineProps<{
    readOnly?: boolean;
    personId: number;
    resumes: ResumeItem[];
    formats: {
        id: number;
        name: string;
        version: number;
        description: string;
    }[];
}>();
const { can } = useAuth();
const form = useForm({
    resume_format_id: props.formats[0]?.id ?? '',
    file: null as File | null,
});
const uploadDescription = computed(
    () =>
        props.formats.find((f) => f.id === Number(form.resume_format_id))
            ?.description,
);
function selectFile(event: Event) {
    form.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function upload() {
    form.post(`/portal/people/${props.personId}/resumes/upload`, {
        forceFormData: true,
    });
}
function remove(resume: ResumeItem) {
    if (
        window.confirm(
            `Delete ${resume.original_filename} and its resume data?`,
        )
    ) {
        router.delete(`/portal/people/${props.personId}/resumes/${resume.id}`, {
            preserveScroll: true,
        });
    }
}
</script>
<template>
    <Card
        ><CardHeader
            ><CardTitle>Resumes</CardTitle
            ><CardDescription
                >{{ readOnly ? 'View and download this person’s resumes.' : 'Upload, review, and manage this person’s searchable resumes.' }}</CardDescription
            ></CardHeader
        ><CardContent class="space-y-6">
            <form
                v-if="!readOnly && can('manage_resumes')"
                class="space-y-4 rounded-lg border p-4"
                @submit.prevent="upload"
            >
                <h3 class="font-semibold">Upload Resume</h3>
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
                <p
                    v-if="uploadDescription"
                    class="text-sm text-muted-foreground"
                >
                    {{ uploadDescription }}
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
                <p class="text-sm text-muted-foreground">
                    The resume is linked to this person automatically. Review
                    the extracted data before saving it to the searchable
                    database.
                </p>
                <p v-if="!formats.length" role="alert">
                    No active resume formats are available. Activate a format in
                    Admin Resume Formats.
                </p>
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
                    aria-label="Resume upload progress"
                />
                <Button
                    type="submit"
                    :disabled="form.processing || !formats.length"
                    >{{
                        form.processing ? 'Processing…' : 'Upload and Review'
                    }}</Button
                >
            </form>
            <div class="space-y-3">
                <h3 class="font-semibold">Person Resumes</h3>
                <p v-if="!resumes.length" class="text-sm text-muted-foreground">
                    No resumes have been uploaded for this person.
                </p>
                <article
                    v-for="resume in resumes"
                    :key="resume.id"
                    class="space-y-3 rounded-lg border p-4"
                >
                    <div>
                        <h4 class="font-medium">
                            {{ resume.original_filename }}
                        </h4>
                        <p class="text-sm text-muted-foreground">
                            {{ resume.format_snapshot.name }} v{{
                                resume.format_snapshot.version
                            }}
                            ·
                            {{
                                new Date(resume.created_at).toLocaleDateString()
                            }}
                            ·
                            {{
                                resume.status === 'saved'
                                    ? 'Searchable'
                                    : 'Awaiting Review'
                            }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="resume.status === 'saved'"
                            as-child
                            variant="outline"
                            ><Link
                                :href="readOnly ? `/portal/resumes/${resume.id}` : `/portal/people/${personId}/resumes/${resume.id}`"
                                >View Resume</Link
                            ></Button
                        ><Button
                            v-if="!readOnly && can('manage_resumes')"
                            as-child
                            variant="outline"
                            ><Link
                                :href="`/portal/people/${personId}/resumes/${resume.id}/edit`"
                                >{{
                                    resume.status === 'draft'
                                        ? 'Review Import'
                                        : 'Edit Resume'
                                }}</Link
                            ></Button
                        ><Button as-child variant="outline"
                            ><a :href="`/portal/resumes/${resume.id}/download`"
                                >Download DOCX</a
                            ></Button
                        ><Button
                            v-if="!readOnly && can('manage_resumes')"
                            type="button"
                            variant="destructive"
                            @click="remove(resume)"
                            >Delete</Button
                        >
                    </div>
                </article>
            </div></CardContent
        ></Card
    >
</template>
