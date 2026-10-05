<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
type Entry = Record<string, string>;
type Resume = {
    id: number;
    status: string;
    person_id: number | null;
    format_snapshot: { name: string; version: number };
    warnings: string[];
    entries: { section: string; data: Entry }[];
    name: string;
    location: string;
    labor_category: string;
    requirement_id: string;
    tickets: string;
    other_information: string;
};
const props = defineProps<{
    resume: Resume;
    columns: Record<string, string[]>;
    people: {
        id: number;
        person_code: string;
        first_name: string;
        last_name: string;
    }[];
    personId: number | null;
    personContext?: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
}>();
const fields = [
    'name',
    'location',
    'labor_category',
    'requirement_id',
    'tickets',
    'other_information',
] as const;
const entries: Record<string, Entry[]> = {};

for (const section of Object.keys(props.columns)) {
    entries[section] = props.resume.entries
        .filter((e) => e.section === section)
        .map((e) => ({ ...e.data }));
}

const form = useForm({
    person_id: props.resume.person_id ?? props.personId ?? '',
    name: props.resume.name ?? '',
    location: props.resume.location ?? '',
    labor_category: props.resume.labor_category ?? '',
    requirement_id: props.resume.requirement_id ?? '',
    tickets: props.resume.tickets ?? '',
    other_information: props.resume.other_information ?? '',
    entries,
});
const resumeUrl = props.personContext
    ? `/portal/people/${props.personContext.id}/resumes/${props.resume.id}`
    : `/portal/resumes/${props.resume.id}`;
const backHref = props.personContext
    ? `/portal/people/${props.personContext.id}/edit?section=resumes`
    : '/portal/resumes';
function discard() {
    if (window.confirm('Discard this draft and its uploaded DOCX?')) {
        router.delete(resumeUrl);
    }
}
function label(value: string) {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
function add(section: string) {
    form.entries[section].push(
        Object.fromEntries(props.columns[section].map((c) => [c, ''])),
    );
}
</script>
<template>
    <Head title="Resume Review" /><PageContainer class="space-y-6">
        <PageHeader
            :title="
                resume.status === 'draft'
                    ? 'Review Imported Resume'
                    : 'Edit Resume'
            "
            :back-href="backHref"
            :description="`${resume.format_snapshot.name} v${resume.format_snapshot.version}. Confirm the person and every extracted field.`"
        />
        <div
            v-if="resume.warnings?.length"
            class="rounded-lg border border-amber-400 p-4"
        >
            <h2 class="font-semibold">Import needs review</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                <li v-for="warning in resume.warnings" :key="warning">
                    {{ warning }}
                </li>
            </ul>
        </div>
        <a :href="`/portal/resumes/${resume.id}/download`" class="underline"
            >Download original DOCX for comparison</a
        >
        <Button
            v-if="resume.status === 'draft'"
            variant="outline"
            @click="discard"
            >Discard Import</Button
        >
        <form class="space-y-6" @submit.prevent="form.put(resumeUrl)">
            <label class="block max-w-xl space-y-2"
                ><span>Link to Person (required)</span
                ><select
                    :disabled="Boolean(personContext)"
                    v-model="form.person_id"
                    required
                    class="w-full rounded-md border bg-background p-2"
                >
                    <option value="" disabled>Select the correct person</option>
                    <option v-for="p in people" :key="p.id" :value="p.id">
                        {{ p.last_name }}, {{ p.first_name }} —
                        {{ p.person_code }}
                    </option>
                </select></label
            >
            <p class="text-sm text-muted-foreground">
                If the person is missing, create their People record first.
                Imported names do not change the People record.
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                <label
                    v-for="field in fields"
                    :key="field"
                    class="block space-y-2"
                    ><span>{{ label(field) }}</span
                    ><textarea
                        v-model="form[field]"
                        :required="field === 'name'"
                        rows="2"
                        class="w-full rounded-md border bg-background p-2"
                    />
                </label>
            </div>
            <section
                v-for="(cols, section) in columns"
                :key="section"
                class="space-y-4 rounded-lg border p-4"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">{{ label(section) }}</h2>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="form.entries[section].length >= 200"
                        @click="add(section)"
                        >Add Entry</Button
                    >
                </div>
                <p
                    v-if="!form.entries[section].length"
                    class="text-sm text-muted-foreground"
                >
                    No entries.
                </p>
                <div
                    v-for="(entry, index) in form.entries[section]"
                    :key="index"
                    class="grid gap-3 border-t pt-4 md:grid-cols-3"
                >
                    <label v-for="col in cols" :key="col" class="space-y-1"
                        ><span class="text-sm">{{ label(col) }}</span
                        ><input
                            v-model="entry[col]"
                            class="w-full rounded-md border bg-background p-2" /></label
                    ><Button
                        type="button"
                        variant="outline"
                        :aria-label="`Remove ${section} entry ${index + 1}`"
                        @click="form.entries[section].splice(index, 1)"
                        >Remove Entry</Button
                    >
                </div>
            </section>
            <div role="alert">
                <p
                    v-for="(error, key) in form.errors"
                    :key="key"
                    class="text-sm text-destructive"
                >
                    {{ error }}
                </p>
            </div>
            <Button type="submit" :disabled="form.processing"
                >Save Reviewed Resume</Button
            >
        </form></PageContainer
    >
</template>
