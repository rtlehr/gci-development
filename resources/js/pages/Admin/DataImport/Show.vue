<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import PageHelpButton from '@/components/ui/PageHelpButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { useAuth } from '@/composables/useAuth';
import { Permissions } from '@/constants/permissions';

type DestinationItem = {
    key: string;
    label: string;
    type: string;
    meta?: Record<string, unknown>;
};

type DestinationGroup = {
    key: string;
    label: string;
    items: DestinationItem[];
};

type MappingIssue = {
    type: string;
    message: string;
    source_header?: string;
    destination_key?: string;
};

const props = defineProps<{
    import: any;
    workflows: Array<{ id: number; name: string; code: string; is_primary: boolean }>;
    selectedWorkflowId: number | null;
    destinationGroups: DestinationGroup[];
    initialMappings: Record<string, string>;
    mappingIssues: MappingIssue[];
    templates: Array<{ id: number; name: string; description?: string | null; workflow_code?: string | null }>;
    selectedTemplateId: number | null;
    validationSummary: { total: number; ready: number; review: number; error: number; ignored: number } | null;
    validationRows: Array<any>;
    translationOptions: Record<string, string[]>;
    valueTranslations: Record<string, Record<string, string>>;
}>();

const { can } = useAuth();
const selectedWorksheet = ref<number>(props.import.worksheet_index ?? props.import.workbook_metadata?.sheets?.[0]?.index ?? 0);
const selectedWorkflow = ref<number | null>(props.selectedWorkflowId ?? null);
const selectedTemplate = ref<number | null>(props.selectedTemplateId ?? null);
const mappings = ref<Record<string, string>>({ ...props.initialMappings });
const templateName = ref('');
const templateDescription = ref('');
const showTemplateForm = ref(false);
const resolutionSelections = ref<Record<string, string>>({});
const translationSelections = ref<Record<string, string>>({});
const executeDialogOpen = ref(false);
const executing = ref(false);
const rollbackDialogOpen = ref(false);
const rollingBack = ref(false);

watch(() => props.initialMappings, (value) => {
    mappings.value = { ...value };
}, { deep: true });

watch(() => props.selectedWorkflowId, (value) => {
    selectedWorkflow.value = value ?? null;
});

watch(() => props.selectedTemplateId, (value) => {
    selectedTemplate.value = value ?? null;
});

watch(() => props.validationRows, (rows) => {
    const selections: Record<string, string> = {};
    (rows ?? []).forEach((row: any) => {
        const resolutions = row.review?.resolutions ?? row.result?.resolutions ?? {};
        ['person', 'position', 'candidate'].forEach((entity) => {
            if (resolutions[entity]) selections[`${row.id}:${entity}`] = resolutions[entity];
        });
    });
    resolutionSelections.value = selections;
}, { deep: true, immediate: true });

const selectedSheet = computed(() => props.import.workbook_metadata?.sheets?.find((sheet: any) => sheet.index === selectedWorksheet.value));
const headers = computed<string[]>(() => props.import.source_headers ?? []);
const mappingReady = computed(() => headers.value.length > 0);
const mappedCount = computed(() => headers.value.filter((_, index) => Boolean(mappings.value[String(index)])).length);
const ignoredCount = computed(() => Object.values(mappings.value).filter((value) => value === 'ignore').length);
const validationRows = computed(() => props.validationRows ?? []);
const hasSavedMapping = computed(() => ['mapped', 'validated', 'validated_with_issues', 'completed', 'completed_with_errors', 'rolled_back', 'rolled_back_with_conflicts'].includes(props.import.status));
const importCompleted = computed(() => ['completed', 'completed_with_errors', 'rolled_back', 'rolled_back_with_conflicts'].includes(props.import.status));
const importRolledBack = computed(() => ['rolled_back', 'rolled_back_with_conflicts'].includes(props.import.status));
const canRollbackImport = computed(() => Boolean(
    ['completed', 'completed_with_errors'].includes(props.import.status)
    && (props.import.change_count ?? 0) > 0
    && !props.import.rolled_back_at
));
const canExecuteImport = computed(() => Boolean(
    props.validationSummary
    && props.validationSummary.error === 0
    && props.validationSummary.review === 0
    && !importCompleted.value
));
const duplicateDestinations = computed(() => {
    const counts = new Map<string, number>();
    Object.values(mappings.value).forEach((destination) => {
        if (!destination || destination === 'ignore') return;
        counts.set(destination, (counts.get(destination) ?? 0) + 1);
    });
    return new Set([...counts.entries()].filter(([, count]) => count > 1).map(([destination]) => destination));
});

function saveWorksheet(): void {
    router.put(`/admin/data-imports/${props.import.id}/worksheet`, { worksheet_index: selectedWorksheet.value }, { preserveScroll: true });
}

function changeWorkflow(): void {
    router.get(`/admin/data-imports/${props.import.id}`, {
        workflow_id: selectedWorkflow.value ?? undefined,
    }, {
        preserveState: false,
        preserveScroll: true,
        replace: true,
    });
}

function loadTemplate(): void {
    if (!selectedTemplate.value) return;

    router.get(`/admin/data-imports/${props.import.id}`, {
        template_id: selectedTemplate.value,
    }, {
        preserveState: false,
        preserveScroll: true,
        replace: true,
    });
}

function fillUnmappedWithIgnore(): void {
    headers.value.forEach((_, index) => {
        if (!mappings.value[String(index)]) {
            mappings.value[String(index)] = 'ignore';
        }
    });
}

function mappingPayload() {
    return headers.value.map((header, index) => ({
        source_index: index,
        source_header: header,
        destination_key: mappings.value[String(index)] ?? '',
    }));
}

function saveMapping(): void {
    router.put(`/admin/data-imports/${props.import.id}/mapping`, {
        workflow_id: selectedWorkflow.value,
        mappings: mappingPayload(),
    }, { preserveScroll: true });
}

function validateRows(): void {
    router.post(`/admin/data-imports/${props.import.id}/validate`, {}, { preserveScroll: true });
}

function saveTemplate(): void {
    router.post(`/admin/data-imports/${props.import.id}/mapping-template`, {
        workflow_id: selectedWorkflow.value,
        mappings: mappingPayload(),
        template_name: templateName.value,
        template_description: templateDescription.value || null,
    }, { preserveScroll: true });
}

function saveRowResolution(row: any): void {
    const payload: Record<string, string> = { row_action: 'continue' };
    ['person', 'position', 'candidate'].forEach((entity) => {
        const key = `${row.id}:${entity}`;
        if (resolutionSelections.value[key]) payload[`${entity}_action`] = resolutionSelections.value[key];
    });

    router.put(`/admin/data-imports/${props.import.id}/rows/${row.id}/resolution`, payload, { preserveScroll: true });
}

function skipRow(row: any): void {
    router.put(`/admin/data-imports/${props.import.id}/rows/${row.id}/resolution`, {
        row_action: 'skip',
    }, { preserveScroll: true });
}

function canTranslate(issue: any): boolean {
    return Boolean(issue?.destination_key && issue?.source_value !== undefined && (props.translationOptions?.[issue.destination_key]?.length ?? 0) > 0);
}

function saveTranslation(row: any, issue: any, issueIndex: number): void {
    const key = `${row.id}:${issueIndex}`;
    const target = translationSelections.value[key];
    if (!target) return;

    router.post(`/admin/data-imports/${props.import.id}/value-translations`, {
        destination_key: issue.destination_key,
        source_value: String(issue.source_value),
        target_value: target,
    }, { preserveScroll: true });
}

function executeImport(): void {
    executeDialogOpen.value = false;
    executing.value = true;
    router.post(`/admin/data-imports/${props.import.id}/execute`, {}, {
        preserveScroll: true,
        onFinish: () => { executing.value = false; },
    });
}

function rollbackImport(): void {
    rollbackDialogOpen.value = false;
    rollingBack.value = true;
    router.post(`/admin/data-imports/${props.import.id}/rollback`, {}, {
        preserveScroll: true,
        onFinish: () => { rollingBack.value = false; },
    });
}

function entityLabel(entity: string): string {
    if (entity === 'person') return 'Person';
    if (entity === 'position') return 'Position';
    if (entity === 'candidate') return 'Candidate';
    return entity;
}

function hasReviewEntities(row: any): boolean {
    return (row.review?.differences?.length ?? 0) > 0;
}

function sampleValues(index: number): string {
    const values = selectedSheet.value?.sample_rows
        ?.map((row: any[]) => row[index])
        .filter((value: unknown) => value !== null && value !== undefined && String(value).trim() !== '')
        .slice(0, 3) ?? [];

    return values.join(' · ') || '—';
}
</script>

<template>
    <Head :title="`Data Import - ${props.import.original_filename}`" />

    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="Data Import"
            :title="props.import.original_filename"
            description="Select the worksheet, then map each Excel column to an Insight field. Mapping does not modify staffing data."
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link href="/admin/data-imports">Import History</Link>
                    </Button>
                    <PageHelpButton help-key="admin.data-imports.show" />
                </div>
            </template>
        </PageHeader>

        <section class="max-w-4xl space-y-4 rounded-xl border bg-background p-5 shadow-sm">
            <div class="space-y-2">
                <Label for="worksheet">Worksheet</Label>
                <select id="worksheet" v-model="selectedWorksheet" class="h-10 w-full max-w-md rounded-md border bg-background px-3">
                    <option v-for="sheet in props.import.workbook_metadata?.sheets ?? []" :key="sheet.index" :value="sheet.index">
                        {{ sheet.name }} — {{ sheet.row_count }} data rows, {{ sheet.column_count }} columns
                    </option>
                </select>
            </div>
            <Button v-if="can(Permissions.DATA_IMPORT_MANAGE)" type="button" @click="saveWorksheet">Continue to Column Mapping</Button>
        </section>

        <section v-if="selectedSheet && !mappingReady" class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Detected Row 1 Headers</h2>
                <p class="text-sm text-muted-foreground">Whitespace and line breaks are normalized for display and future matching; the original header text remains in workbook metadata.</p>
            </div>
            <div class="overflow-hidden rounded-xl border bg-background shadow-sm">
                <Table class="table-fixed">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-20">Column</TableHead>
                            <TableHead class="w-64">Header</TableHead>
                            <TableHead>Sample values</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(header, index) in selectedSheet.headers" :key="index">
                            <TableCell>{{ index + 1 }}</TableCell>
                            <TableCell class="font-medium">{{ header || '(blank)' }}</TableCell>
                            <TableCell class="max-w-0 align-top text-muted-foreground"><div class="line-clamp-2 whitespace-normal break-words" :title="sampleValues(index)">{{ sampleValues(index) }}</div></TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </section>

        <template v-if="mappingReady">
            <section class="space-y-5 rounded-xl border bg-background p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold">Column Mapping</h2>
                    <p class="text-sm text-muted-foreground">
                        Suggested mappings use exact, unambiguous header matches only. Review every destination before saving.
                    </p>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="candidate-workflow">Candidate Workflow</Label>
                        <select
                            id="candidate-workflow"
                            v-model="selectedWorkflow"
                            class="h-10 w-full rounded-md border bg-background px-3"
                            @change="changeWorkflow"
                        >
                            <option :value="null">No Candidate Workflow</option>
                            <option v-for="workflow in props.workflows" :key="workflow.id" :value="workflow.id">
                                {{ workflow.name }}{{ workflow.is_primary ? ' (Primary)' : '' }}
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">Workflow mapping choices are generated from the selected workflow's active steps and allowed properties.</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="mapping-template">Saved Mapping Template</Label>
                        <div class="flex gap-2">
                            <select id="mapping-template" v-model="selectedTemplate" class="h-10 min-w-0 flex-1 rounded-md border bg-background px-3">
                                <option :value="null">Select a template</option>
                                <option v-for="template in props.templates" :key="template.id" :value="template.id">
                                    {{ template.name }}
                                </option>
                            </select>
                            <Button type="button" variant="outline" :disabled="!selectedTemplate" @click="loadTemplate">Load</Button>
                        </div>
                        <p class="text-xs text-muted-foreground">Templates are matched to this worksheet by normalized header name, not column position.</p>
                    </div>
                </div>

                <div v-if="props.mappingIssues.length" class="space-y-2 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    <div class="font-semibold">This mapping needs review</div>
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-for="(issue, index) in props.mappingIssues" :key="`${issue.type}-${index}`">{{ issue.message }}</li>
                    </ul>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div class="text-muted-foreground">
                        {{ mappedCount }} of {{ headers.length }} columns mapped · {{ ignoredCount }} ignored
                    </div>
                    <Button v-if="can(Permissions.DATA_IMPORT_MANAGE)" type="button" variant="outline" @click="fillUnmappedWithIgnore">
                        Set Unmapped to Do Not Import
                    </Button>
                </div>

                <div class="overflow-hidden rounded-xl border">
                    <Table class="table-fixed">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">Column</TableHead>
                                <TableHead class="w-56">Excel Header</TableHead>
                                <TableHead class="w-[30%]">Sample Values</TableHead>
                                <TableHead>Import Into</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(header, index) in headers" :key="index">
                                <TableCell>{{ index + 1 }}</TableCell>
                                <TableCell class="font-medium">{{ header || '(blank)' }}</TableCell>
                                <TableCell class="max-w-0 align-top text-muted-foreground"><div class="line-clamp-2 whitespace-normal break-words" :title="sampleValues(index)">{{ sampleValues(index) }}</div></TableCell>
                                <TableCell>
                                    <select
                                        v-model="mappings[String(index)]"
                                        class="h-10 w-full rounded-md border bg-background px-3"
                                        :class="duplicateDestinations.has(mappings[String(index)]) ? 'border-destructive' : ''"
                                        :disabled="!can(Permissions.DATA_IMPORT_MANAGE)"
                                        :aria-label="`Map ${header || `column ${index + 1}`}`"
                                    >
                                        <option value="">Select destination</option>
                                        <optgroup v-for="group in props.destinationGroups" :key="group.key" :label="group.label">
                                            <option v-for="destination in group.items" :key="destination.key" :value="destination.key">
                                                {{ destination.label }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <p v-if="duplicateDestinations.has(mappings[String(index)])" class="mt-1 text-xs text-destructive">
                                        This destination is mapped from more than one column.
                                    </p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="can(Permissions.DATA_IMPORT_MANAGE)" class="flex flex-wrap gap-3">
                    <Button type="button" :disabled="mappedCount !== headers.length || duplicateDestinations.size > 0" @click="saveMapping">
                        Save Mapping
                    </Button>
                    <Button type="button" variant="outline" :disabled="mappedCount !== headers.length || duplicateDestinations.size > 0" @click="showTemplateForm = !showTemplateForm">
                        Save as Template
                    </Button>
                    <Button type="button" variant="secondary" :disabled="!hasSavedMapping" @click="validateRows">
                        Validate Rows
                    </Button>
                </div>

                <div v-if="showTemplateForm && can(Permissions.DATA_IMPORT_MANAGE)" class="max-w-2xl space-y-4 rounded-lg border bg-muted/20 p-4">
                    <div class="space-y-2">
                        <Label for="template-name">Template Name</Label>
                        <Input id="template-name" v-model="templateName" placeholder="Staffing Matrix" maxlength="150" />
                    </div>
                    <div class="space-y-2">
                        <Label for="template-description">Description (optional)</Label>
                        <textarea
                            id="template-description"
                            v-model="templateDescription"
                            rows="3"
                            maxlength="1000"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Mapping used for the standard staffing matrix workbook."
                        />
                    </div>
                    <div class="flex gap-2">
                        <Button type="button" :disabled="!templateName.trim()" @click="saveTemplate">Save Template</Button>
                        <Button type="button" variant="outline" @click="showTemplateForm = false">Cancel</Button>
                    </div>
                </div>

                <p class="text-xs text-muted-foreground">
                    Validation is read-only for staffing data. It records validation results and matches only; Position, Person, Candidate, workflow, assignment, and custom-field data are not changed.
                </p>
            </section>

            <section v-if="props.validationSummary" class="space-y-5 rounded-xl border bg-background p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Validation Results</h2>
                        <p class="text-sm text-muted-foreground">Existing records are flagged for review. Errors must be resolved before the import execution stage.</p>
                    </div>
                    <Button v-if="can(Permissions.DATA_IMPORT_MANAGE)" type="button" variant="outline" @click="validateRows">Run Validation Again</Button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-lg border p-3"><div class="text-xs text-muted-foreground">Rows</div><div class="text-2xl font-semibold">{{ props.validationSummary.total }}</div></div>
                    <div class="rounded-lg border p-3"><div class="text-xs text-muted-foreground">Ready</div><div class="text-2xl font-semibold">{{ props.validationSummary.ready }}</div></div>
                    <div class="rounded-lg border p-3"><div class="text-xs text-muted-foreground">Review</div><div class="text-2xl font-semibold">{{ props.validationSummary.review }}</div></div>
                    <div class="rounded-lg border p-3"><div class="text-xs text-muted-foreground">Errors</div><div class="text-2xl font-semibold">{{ props.validationSummary.error }}</div></div>
                    <div class="rounded-lg border p-3"><div class="text-xs text-muted-foreground">Ignored</div><div class="text-2xl font-semibold">{{ props.validationSummary.ignored }}</div></div>
                </div>

                <div v-if="importCompleted" class="space-y-4 rounded-lg border bg-muted/30 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold">{{ importRolledBack ? 'Import Rollback Complete' : 'Import Execution Complete' }}</div>
                            <p v-if="importRolledBack" class="mt-1 text-sm text-muted-foreground">
                                This import has been rolled back. Any conflicts shown below were preserved rather than overwriting newer changes.
                            </p>
                        </div>
                        <Button
                            v-if="can(Permissions.DATA_IMPORT_ROLLBACK) && canRollbackImport"
                            type="button"
                            variant="destructive"
                            :disabled="rollingBack"
                            @click="rollbackDialogOpen = true"
                        >
                            {{ rollingBack ? 'Rolling Back…' : 'Rollback Import' }}
                        </Button>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div><div class="text-xs text-muted-foreground">Records Created</div><div class="text-xl font-semibold">{{ props.import.created_count ?? 0 }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Records Updated</div><div class="text-xl font-semibold">{{ props.import.updated_count ?? 0 }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Rows Skipped</div><div class="text-xl font-semibold">{{ props.import.skipped_count ?? 0 }}</div></div>
                        <div><div class="text-xs text-muted-foreground">Rows Failed</div><div class="text-xl font-semibold">{{ props.import.failed_count ?? 0 }}</div></div>
                    </div>
                    <p v-if="!importRolledBack" class="text-xs text-muted-foreground">Every created or updated record was written to the encrypted import change journal and can be safely evaluated for rollback.</p>

                    <div v-if="props.import.rollback_summary" class="rounded-md border bg-background p-4">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div><div class="text-xs text-muted-foreground">Records Restored</div><div class="text-xl font-semibold">{{ props.import.rollback_summary.restored ?? 0 }}</div></div>
                            <div><div class="text-xs text-muted-foreground">Imported Records Removed</div><div class="text-xl font-semibold">{{ props.import.rollback_summary.deleted ?? 0 }}</div></div>
                            <div><div class="text-xs text-muted-foreground">Conflicts</div><div class="text-xl font-semibold">{{ props.import.rollback_summary.conflicts ?? 0 }}</div></div>
                        </div>
                        <div v-if="props.import.rollback_summary.conflict_items?.length" class="mt-4 space-y-2">
                            <div class="text-sm font-semibold">Rollback Conflicts</div>
                            <div v-for="conflict in props.import.rollback_summary.conflict_items" :key="conflict.change_id" class="rounded-md border p-3 text-sm">
                                <div class="font-medium">{{ conflict.model_type }} #{{ conflict.model_id }}</div>
                                <div class="mt-1 text-muted-foreground">{{ conflict.message }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4">
                    <div>
                        <div class="font-semibold">Run Import</div>
                        <p v-if="canExecuteImport" class="text-sm text-muted-foreground">All review items and validation errors are resolved. This action will modify Insight staffing data.</p>
                        <p v-else class="text-sm text-muted-foreground">Resolve all review items and errors before the import can run.</p>
                    </div>
                    <Button
                        v-if="can(Permissions.DATA_IMPORT_MANAGE)"
                        type="button"
                        :disabled="!canExecuteImport || executing"
                        @click="executeDialogOpen = true"
                    >
                        {{ executing ? 'Importing…' : 'Run Import' }}
                    </Button>
                </div>

                <div class="overflow-hidden rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-20">Excel Row</TableHead>
                                <TableHead>Identifier</TableHead>
                                <TableHead class="w-28">Status</TableHead>
                                <TableHead>Validation / Matching Result</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in validationRows" :key="row.id">
                                <TableCell>{{ row.source_row_number }}</TableCell>
                                <TableCell class="font-medium">{{ row.source_identifier || '—' }}</TableCell>
                                <TableCell>
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium capitalize">{{ row.status }}</span>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-4">
                                        <div v-if="row.issues?.length" class="space-y-2">
                                            <div v-for="(issue, issueIndex) in row.issues" :key="`${row.id}-${issueIndex}`" class="rounded-md border p-3 text-sm">
                                                <div>
                                                    <span class="font-medium capitalize">{{ issue.severity }}:</span> {{ issue.message }}
                                                </div>
                                                <div v-if="canTranslate(issue)" class="mt-3 flex flex-wrap items-end gap-2">
                                                    <div class="min-w-64 flex-1 space-y-1">
                                                        <Label :for="`translation-${row.id}-${issueIndex}`" class="text-xs">Map “{{ issue.source_value }}” to</Label>
                                                        <select
                                                            :id="`translation-${row.id}-${issueIndex}`"
                                                            v-model="translationSelections[`${row.id}:${issueIndex}`]"
                                                            class="h-9 w-full rounded-md border bg-background px-3"
                                                        >
                                                            <option value="">Select current Insight value</option>
                                                            <option v-for="option in props.translationOptions[issue.destination_key]" :key="option" :value="option">{{ option }}</option>
                                                        </select>
                                                    </div>
                                                    <Button
                                                        v-if="can(Permissions.DATA_IMPORT_MANAGE)"
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        :disabled="!translationSelections[`${row.id}:${issueIndex}`]"
                                                        @click="saveTranslation(row, issue, issueIndex)"
                                                    >
                                                        Save Value Mapping
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                        <span v-else class="text-sm text-muted-foreground">Ready — no unresolved issues detected.</span>

                                        <div v-if="hasReviewEntities(row)" class="space-y-3 border-t pt-3">
                                            <div class="text-sm font-semibold">Existing Record Review</div>
                                            <div v-for="entity in row.review.differences" :key="`${row.id}-${entity.entity}`" class="rounded-lg border bg-muted/20 p-3">
                                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                                    <div class="text-sm font-medium">Existing {{ entityLabel(entity.entity) }} #{{ entity.id }}</div>
                                                    <select
                                                        v-model="resolutionSelections[`${row.id}:${entity.entity}`]"
                                                        class="h-9 rounded-md border bg-background px-3 text-sm"
                                                        :disabled="!can(Permissions.DATA_IMPORT_MANAGE)"
                                                    >
                                                        <option value="">Choose action</option>
                                                        <option value="update">Update Existing</option>
                                                        <option value="use_existing">Use Existing Without Changes</option>
                                                    </select>
                                                </div>

                                                <div v-if="entity.fields?.length" class="overflow-hidden rounded-md border bg-background">
                                                    <table class="w-full text-sm">
                                                        <thead class="bg-muted/50 text-left">
                                                            <tr><th class="px-3 py-2">Field</th><th class="px-3 py-2">Current</th><th class="px-3 py-2">Spreadsheet</th></tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="field in entity.fields" :key="field.destination_key" class="border-t" :class="field.different ? 'font-medium' : ''">
                                                                <td class="px-3 py-2">{{ field.field }}</td>
                                                                <td class="px-3 py-2 text-muted-foreground">{{ field.current ?? '—' }}</td>
                                                                <td class="px-3 py-2">{{ field.incoming ?? '—' }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div v-if="can(Permissions.DATA_IMPORT_MANAGE)" class="flex flex-wrap gap-2">
                                                <Button type="button" size="sm" @click="saveRowResolution(row)">Save Review Decisions</Button>
                                                <Button type="button" size="sm" variant="outline" @click="skipRow(row)">Skip Row</Button>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </section>
        </template>

        <AlertDialog :open="executeDialogOpen" @update:open="executeDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Run this data import?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This is the first step that changes staffing data. Insight will create or update the ready Positions, People, Candidates, workflow events, assignments, and custom-field values using the review decisions you saved. Each spreadsheet row runs in its own database transaction and all successful changes are journaled for rollback.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeImport">Run Import</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog :open="rollbackDialogOpen" @update:open="rollbackDialogOpen = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Rollback this data import?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Insight will process the encrypted change journal in reverse order. Records created by this import will be removed when they have not changed, and records updated by this import will be restored to their previous values. If a record has changed since the import, Insight will preserve the newer data and report a rollback conflict instead of overwriting it. A rollback cannot be run twice.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-destructive-foreground hover:bg-destructive/90" @click="rollbackImport">Rollback Import</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </PageContainer>
</template>
