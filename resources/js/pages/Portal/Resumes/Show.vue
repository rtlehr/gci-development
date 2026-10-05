<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/composables/useAuth';
const props = defineProps<{
    resume: {
        id: number;
        name: string;
        location: string;
        labor_category: string;
        requirement_id: string;
        tickets: string;
        other_information: string;
        person_id: number;
        person: { first_name: string; last_name: string };
        original_filename: string;
        format_snapshot: { name: string; version: number };
        entries: {
            id: number;
            section: string;
            data: Record<string, string>;
        }[];
    };
    columns: Record<string, string[]>;
    personContext?: {
        id: number;
        first_name: string;
        last_name: string;
    } | null;
}>();
const { can } = useAuth();
const fields = [
    'location',
    'labor_category',
    'requirement_id',
    'tickets',
    'other_information',
] as const;
function label(v: string) {
    return v.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
const resumeUrl = props.personContext
    ? `/portal/people/${props.personContext.id}/resumes/${props.resume.id}`
    : `/portal/resumes/${props.resume.id}`;
const backHref = props.personContext
    ? `/portal/people/${props.personContext.id}/edit?section=resumes`
    : '/portal/resumes';
function remove() {
    if (window.confirm('Delete this resume and its original DOCX?')) {
        router.delete(resumeUrl);
    }
}
</script>
<template>
    <Head :title="resume.name" /><PageContainer class="space-y-6"
        ><PageHeader
            :title="resume.name"
            :back-href="backHref"
            :description="`${resume.format_snapshot.name} v${resume.format_snapshot.version}`"
            ><template #actions
                ><Button as-child variant="outline"
                    ><a :href="`/portal/resumes/${resume.id}/download`"
                        >Download DOCX</a
                    ></Button
                ><Button v-if="can('manage_resumes')" as-child
                    ><Link :href="`${resumeUrl}/edit`"
                        >Edit Resume</Link
                    ></Button
                ><Button
                    v-if="can('manage_resumes')"
                    variant="destructive"
                    @click="remove"
                    >Delete</Button
                ></template
            ></PageHeader
        >
        <p>
            Linked person:
            <Link
                v-if="can('portal_view_directory')"
                :href="`/portal/people/${resume.person_id}`"
                class="underline"
                >{{ resume.person.first_name }}
                {{ resume.person.last_name }}</Link
            ><span v-else
                >{{ resume.person.first_name }}
                {{ resume.person.last_name }}</span
            >
        </p>
        <dl class="grid gap-4 rounded-lg border p-5 md:grid-cols-2">
            <div v-for="field in fields" :key="field">
                <dt class="text-sm text-muted-foreground">
                    {{ label(field) }}
                </dt>
                <dd class="whitespace-pre-wrap">{{ resume[field] || '—' }}</dd>
            </div>
        </dl>
        <section
            v-for="(cols, section) in columns"
            :key="section"
            class="space-y-3"
        >
            <h2 class="text-lg font-semibold">{{ label(section) }}</h2>
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th v-for="col in cols" :key="col" class="p-3">
                                {{ label(col) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="entry in resume.entries.filter(
                                (e) => e.section === section,
                            )"
                            :key="entry.id"
                            class="border-t"
                        >
                            <td v-for="col in cols" :key="col" class="p-3">
                                {{ entry.data[col] || '—' }}
                            </td>
                        </tr>
                        <tr
                            v-if="
                                !resume.entries.some(
                                    (e) => e.section === section,
                                )
                            "
                        >
                            <td :colspan="cols.length" class="p-3">
                                No entries.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section></PageContainer
    >
</template>
