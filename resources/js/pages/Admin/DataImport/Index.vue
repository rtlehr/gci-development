<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, FileSpreadsheet, Upload } from 'lucide-vue-next';
import PageContainer from '@/components/layout/PageContainer.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import PageHelpButton from '@/components/ui/PageHelpButton.vue';
import ListTableShell from '@/components/Lists/ListTableShell.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAuth } from '@/composables/useAuth';
import { Permissions } from '@/constants/permissions';

type WorkflowOption = {
    id: number;
    name: string;
    code: string;
    is_primary: boolean;
};

const props = defineProps<{
    imports: any;
    workflows: WorkflowOption[];
    primaryWorkflowId?: number | null;
}>();

const { can } = useAuth();
const selectedWorkflowId = ref<number | null>(props.primaryWorkflowId ?? props.workflows?.[0]?.id ?? null);

watch(
    () => props.primaryWorkflowId,
    (value) => {
        if (selectedWorkflowId.value === null && value) selectedWorkflowId.value = value;
    },
);

const canDownloadTemplate = computed(() => can(Permissions.DATA_IMPORT_MANAGE) && selectedWorkflowId.value !== null);

function goToPage(url: string | null): void {
    if (url) router.visit(url, { preserveState: true });
}

function downloadTemplate(): void {
    if (!selectedWorkflowId.value) return;
    window.location.assign(`/admin/data-imports/template/download?workflow_id=${selectedWorkflowId.value}`);
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
}

function statusLabel(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
}
</script>

<template>
    <Head title="Data Import" />
    <PageContainer class="space-y-6">
        <PageHeader
            eyebrow="People and Workforce"
            title="Data Import"
            description="Upload Excel workbooks, validate staffing data, and manage import history."
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button v-if="can(Permissions.DATA_IMPORT_MANAGE)" as-child>
                        <Link href="/admin/data-imports/create"><Upload class="mr-2 h-4 w-4" />New Import</Link>
                    </Button>
                    <PageHelpButton help-key="admin.data-imports.index" />
                </div>
            </template>
        </PageHeader>

        <div v-if="can(Permissions.DATA_IMPORT_MANAGE)" class="rounded-xl border bg-background p-5 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex max-w-3xl items-start gap-3">
                    <FileSpreadsheet class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" aria-hidden="true" />
                    <div>
                        <h2 class="font-semibold">Download an Excel import template</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Start with a workbook generated from Insight's current fields, custom fields, reference values, and Candidate Workflow configuration. The template includes instructions and current valid lookup values to reduce mapping and validation errors.
                        </p>
                    </div>
                </div>

                <div class="w-full space-y-2 lg:max-w-md">
                    <Label for="template-workflow">Candidate Workflow</Label>
                    <select
                        id="template-workflow"
                        v-model.number="selectedWorkflowId"
                        class="h-10 w-full rounded-md border bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :disabled="!props.workflows.length"
                    >
                        <option v-if="!props.workflows.length" :value="null">No active workflows available</option>
                        <option v-for="workflow in props.workflows" :key="workflow.id" :value="workflow.id">
                            {{ workflow.name }}{{ workflow.is_primary ? ' (Primary)' : '' }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Workflow columns are generated from the selected workflow's active steps and enabled properties.
                    </p>
                    <Button
                        variant="outline"
                        class="w-full sm:w-auto"
                        :disabled="!canDownloadTemplate"
                        @click="downloadTemplate"
                    >
                        <Download class="mr-2 h-4 w-4" />
                        Download Excel Template
                    </Button>
                </div>
            </div>

            <div class="mt-5 grid gap-3 border-t pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><span class="font-medium">Import Template</span><p class="text-muted-foreground">Ready-to-fill Row 1 headers.</p></div>
                <div><span class="font-medium">Instructions</span><p class="text-muted-foreground">Import rules and matching guidance.</p></div>
                <div><span class="font-medium">Reference Data</span><p class="text-muted-foreground">Current valid lookup values and options.</p></div>
                <div><span class="font-medium">Insight Metadata</span><p class="text-muted-foreground">Workflow and field identifiers for future recognition.</p></div>
            </div>
        </div>

        <ListTableShell label="Data import history">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>File</TableHead>
                        <TableHead>Worksheet</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Rows</TableHead>
                        <TableHead>Uploaded By</TableHead>
                        <TableHead>Date</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="!props.imports.data?.length">
                        <TableCell colspan="6" class="py-8 text-center text-muted-foreground">No data imports have been uploaded.</TableCell>
                    </TableRow>
                    <TableRow v-for="item in props.imports.data" :key="item.id">
                        <TableCell>
                            <Link :href="`/admin/data-imports/${item.id}`" class="font-semibold text-primary hover:underline">
                                {{ item.original_filename }}
                            </Link>
                        </TableCell>
                        <TableCell>{{ item.worksheet || 'Not selected' }}</TableCell>
                        <TableCell>{{ statusLabel(item.status) }}</TableCell>
                        <TableCell>{{ item.row_count.toLocaleString() }}</TableCell>
                        <TableCell>{{ item.uploaded_by || '—' }}</TableCell>
                        <TableCell>{{ formatDate(item.created_at) }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </ListTableShell>

        <nav v-if="props.imports.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Import history pagination">
            <p class="text-sm text-muted-foreground">Page {{ props.imports.current_page }} of {{ props.imports.last_page }}</p>
            <div class="flex gap-2">
                <Button variant="outline" :disabled="!props.imports.prev_page_url" @click="goToPage(props.imports.prev_page_url)">Previous</Button>
                <Button variant="outline" :disabled="!props.imports.next_page_url" @click="goToPage(props.imports.next_page_url)">Next</Button>
            </div>
        </nav>
    </PageContainer>
</template>
