<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { useAuth } from '@/composables/useAuth';
type Item = {
    matched_skill_count?: number;
    id: number;
    name: string;
    location: string;
    labor_category: string;
    person_id: number;
    original_filename: string;
    created_at: string;
    person: { first_name: string; last_name: string } | null;
};
const props = defineProps<{
    resumes: {
        data: Item[];
        prev_page_url: string | null;
        next_page_url: string | null;
        total: number;
    };
    positionSkills: string[];
    filters: Record<string, string | number>;
    position: {
        id: number;
        position_code: string;
        job_title: string;
        status: string;
        accepting_candidates: boolean;
    } | null;
    positions: { id: number; position_code: string; job_title: string }[];
    assignedPersonIds: number[];
    workflows: { id: number; name: string }[];
    drafts: { id: number; original_filename: string }[];
}>();
const { can } = useAuth();
const fields = [
    'location',
    'labor_category',
    'requirement_id',
    'education',
    'certifications',
    'languages',
    'technologies',
    'tickets',
    'other_information',
];
const filters = reactive<Record<string, string | number>>({
    use_position_skills: 0,
    q: '',
    position_id: '',
    ...props.filters,
});
const workflow = ref(props.workflows[0]?.id ?? '');
const assignment = useForm({
    position_id: props.position?.id ?? '',
    workflow_id: workflow.value,
});
function search() {
    assignment.clearErrors();
    router.get('/portal/resumes', filters, {
        preserveState: true,
        replace: true,
    });
}
function changePosition() {
    search();
}
watch(
    () => props.filters.position_id,
    (id) => {
        filters.position_id = id ?? '';
    },
);
function reset() {
    router.get(
        '/portal/resumes',
        props.position ? { position_id: props.position.id } : {},
    );
}
function add(resume: Item) {
    assignment.position_id = filters.position_id
        ? Number(filters.position_id)
        : '';
    assignment.workflow_id = workflow.value;
    assignment.post(`/portal/resumes/${resume.id}/candidate`);
}
function label(v: string) {
    return v.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
</script>
<template>
    <Head title="Resume Database" /><PageContainer class="space-y-6">
        <PageHeader
            title="Resume Database"
            description="Search reviewed resumes and add people to a position's candidate workflow."
            ><template #actions
                ><Button v-if="can('manage_resumes')" as-child
                    ><Link href="/portal/resumes/upload"
                        >Upload Resume</Link
                    ></Button
                ><Button
                    v-if="can('manage_resume_formats') && can('view_admin')"
                    as-child
                    variant="outline"
                    ><Link href="/admin/resume-formats"
                        >Resume Formats</Link
                    ></Button
                ></template
            ></PageHeader
        >
        <div
            v-if="drafts.length && can('manage_resumes')"
            class="space-y-2 rounded-lg border p-4"
        >
            <h2 class="font-semibold">Your imports awaiting review</h2>
            <Link
                v-for="d in drafts"
                :key="d.id"
                :href="`/portal/resumes/${d.id}/edit`"
                class="mr-4 inline-block underline"
                >{{ d.original_filename }}</Link
            >
        </div>
        <form class="space-y-4 rounded-lg border p-4" @submit.prevent="search">
            <label class="block space-y-1"
                ><span>Keywords (all terms must match)</span
                ><input
                    v-model="filters.q"
                    class="w-full rounded-md border bg-background p-2"
                    placeholder="Java Security+ Spanish"
            /></label>
            <label class="block space-y-1"
                ><span>Position</span
                ><select
                    v-model="filters.position_id"
                    @change="changePosition"
                    class="w-full rounded-md border bg-background p-2"
                >
                    <option value="">Search without a position</option>
                    <option v-for="p in positions" :key="p.id" :value="p.id">
                        {{ p.position_code }} — {{ p.job_title }}
                    </option>
                </select></label
            >
            <div v-if="position" class="space-y-2">
                <label class="flex items-center gap-2"><input v-model="filters.use_position_skills" type="checkbox" :true-value="1" :false-value="0" />Use this position's Skills to rank resumes</label>
                <p v-if="Number(filters.use_position_skills)" class="text-sm text-muted-foreground">{{ positionSkills.length ? `Skill names: ${positionSkills.join(', ')}. Resumes matching at least one skill are ranked by match count.` : 'This position has no saved skills. Showing keyword search results.' }}</p>
            </div>
            <details>
                <summary class="cursor-pointer">Advanced Filters</summary>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <label
                        v-for="field in fields"
                        :key="field"
                        class="space-y-1"
                        ><span class="text-sm">{{ label(field) }}</span
                        ><input
                            v-model="filters[field]"
                            class="w-full rounded-md border bg-background p-2"
                    /></label>
                </div>
            </details>
            <div class="flex gap-2">
                <Button type="submit">Search</Button
                ><Button type="button" variant="outline" @click="reset"
                    >Reset Search</Button
                >
            </div>
        </form>
        <div v-if="position" class="space-y-3 rounded-lg border p-4">
            <h2 class="font-semibold">
                Find candidates for {{ position.position_code }} —
                {{ position.job_title }}
            </h2>
            <label class="block max-w-lg space-y-1"
                ><span>Candidate workflow</span
                ><select
                    v-model="workflow"
                    class="w-full rounded-md border bg-background p-2"
                >
                    <option v-for="w in workflows" :key="w.id" :value="w.id">
                        {{ w.name }}
                    </option>
                </select></label
            >
            <p v-if="!workflows.length" role="alert">
                An active candidate workflow must be configured before adding a
                candidate.
            </p>
            <p class="text-sm text-muted-foreground">
                Click Add to Position beside a resume. The position and
                candidate eligibility will be checked when you submit.
            </p>
        </div>
        <p
            v-for="(error, key) in assignment.errors"
            :key="key"
            role="alert"
            class="text-destructive"
        >
            {{ error }}
        </p>
        <p class="text-sm text-muted-foreground">
            {{ resumes.total }} matching resume(s)
        </p>
        <div class="space-y-3">
            <article
                v-for="r in resumes.data"
                :key="r.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4"
            >
                <div>
                    <Link
                        :href="`/portal/resumes/${r.id}`"
                        class="font-semibold underline"
                        >{{ r.name }}</Link
                    >
                    <p v-if="r.matched_skill_count !== undefined" class="text-sm font-medium">{{ r.matched_skill_count }} / {{ positionSkills.length }} skills matched</p>
                    <p v-if="r.person" class="text-sm">
                        Person: {{ r.person.first_name }}
                        {{ r.person.last_name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ r.original_filename }} ·
                        {{ new Date(r.created_at).toLocaleDateString() }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ r.location }} · {{ r.labor_category }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button as-child variant="outline"
                        ><Link :href="`/portal/resumes/${r.id}`"
                            >View Resume</Link
                        ></Button
                    ><Button
                        v-if="
                            position &&
                            can('create_candidates') &&
                            can('portal_view_positions')
                        "
                        :disabled="
                            assignedPersonIds.includes(r.person_id) ||
                            assignment.processing ||
                            !workflows.length ||
                            Number(filters.position_id) !== position.id
                        "
                        @click="add(r)"
                        >{{
                            assignedPersonIds.includes(r.person_id)
                                ? 'Already a Candidate'
                                : 'Add to Position'
                        }}</Button
                    >
                </div>
            </article>
            <p v-if="!resumes.data.length" class="rounded-lg border p-6">
                No resumes matched. Try fewer filters or upload a resume.
            </p>
        </div>
        <div class="flex gap-3">
            <Link
                v-if="resumes.prev_page_url"
                :href="resumes.prev_page_url"
                class="underline"
                >Previous</Link
            ><Link
                v-if="resumes.next_page_url"
                :href="resumes.next_page_url"
                class="underline"
                >Next</Link
            >
        </div>
    </PageContainer>
</template>
