<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    BriefcaseBusiness,
    Download,
    ExternalLink,
    RotateCcw,
    Search,
    Settings2,
    Workflow,
} from 'lucide-vue-next'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import SortableTableHead from '@/components/Lists/SortableTableHead.vue'
import DownloadErrorAlert from '@/components/Lists/DownloadErrorAlert.vue'
import PositionStaffingSummary from '@/components/dashboard/PositionStaffingSummary.vue'
import StaffingDetailsSheet from '@/components/dashboard/StaffingDetailsSheet.vue'
import StatusBadge from '@/components/data/StatusBadge.vue'
import { useFileDownload } from '@/composables/useFileDownload'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

type StaffingState = 'vacant' | 'selected' | 'filled' | 'departing' | 'on_hold'
type StaffingSummary = {
    vacant: number
    selected: number
    filled: number
    departing: number
    onHold: number
}

type Column = {
    key: string
    label: string
    default_visible?: boolean
    default_order?: number
}

type WorkflowStep = {
    id: number
    name: string
    step_order: number
    status_code: string | null
    requested_at: string | null
    scheduled_at: string | null
    completed_at: string | null
    notes: string | null
    comments: string | null
    has_event: boolean
}

type PersonSummary = {
    id: number
    person_code: string | null
    name: string
    first_name: string | null
    alternate_first_name: string | null
    preferred_name: string | null
    last_name: string | null
    alternate_last_name: string | null
    company_name: string | null
    employment_status: string | null
}

type WorkflowCandidate = {
    candidate_id: number
    candidate_code: string | null
    candidate_status: string | null
    scheduled_start_date: string | null
    person: PersonSummary | null
    workflow_id: number | null
    workflow_name: string | null
    current_step: string
    current_step_number: number
    step_count: number
    steps: WorkflowStep[]
}

type StaffingPosition = {
    id: number
    position_code: string | null
    title: string | null
    level: number | null
    team_name: string | null
    project_team_name: string | null
    location: string | null
    building: string | null
    created_at: string | null
    closed_at: string | null
    status: string | null
    staffing_state: StaffingState
    staffing_label: string
    current_person: PersonSummary | null
    current_person_name: string | null
    employer: string | null
    actual_start_date: string | null
    departure_date: string | null
    assignment_status: string | null
    scheduled_start_date: string | null
    current_workflow_step: string
    current_workflow_name: string | null
    workflow_candidates: WorkflowCandidate[]
    workflow_link: string
    last_updated: string | null
    search_text: string
    project_manager_name?: string | null
}

const props = withDefaults(defineProps<{
    positions: StaffingPosition[]
    summary: StaffingSummary
    columns: Column[]
    visibleColumns: string[]
    columnOrder: string[]
    title?: string
    description?: string
}>(), {
    title: 'Staffing Matrix',
    description: 'Review positions and open Candidate Workflow details without leaving the dashboard.',
})

const { downloadFile, isDownloading, downloadError } = useFileDownload()

const showColumnSettings = ref(false)
const detailsOpen = ref(false)
const selectedPosition = ref<StaffingPosition | null>(null)
const activeState = ref<StaffingState | null>(null)
const searchInput = ref('')
const appliedSearch = ref('')
const sortKey = ref('position_code')
const sortDirection = ref<'asc' | 'desc'>('asc')

const settingsForm = reactive({
    visibleColumns: [...props.visibleColumns],
    columnOrder: [...props.columnOrder],
})

watch(() => props.visibleColumns, (value) => {
    settingsForm.visibleColumns = [...value]
})

watch(() => props.columnOrder, (value) => {
    settingsForm.columnOrder = [...value]
})

const columnsByKey = computed(() => new Map(props.columns.map((column) => [column.key, column])))

const activeColumns = computed(() => settingsForm.columnOrder
    .filter((key) => settingsForm.visibleColumns.includes(key))
    .map((key) => columnsByKey.value.get(key))
    .filter((column): column is Column => Boolean(column)))

const columnsForSettings = computed(() => settingsForm.columnOrder
    .map((key) => columnsByKey.value.get(key))
    .filter((column): column is Column => Boolean(column))
    .map((column) => ({
        ...column,
        visible: settingsForm.visibleColumns.includes(column.key),
    })))

const defaultColumnsForSettings = computed(() => [...props.columns]
    .sort((a, b) => (a.default_order ?? 0) - (b.default_order ?? 0))
    .map((column) => ({
        ...column,
        visible: column.default_visible !== false,
    })))

const filteredPositions = computed(() => {
    const search = appliedSearch.value.trim().toLowerCase()

    const filtered = props.positions.filter((position) => {
        if (activeState.value && position.staffing_state !== activeState.value) {
            return false
        }

        if (search && !String(position.search_text ?? '').toLowerCase().includes(search)) {
            return false
        }

        return true
    })

    return [...filtered].sort((a, b) => {
        const left = sortableValue(a, sortKey.value)
        const right = sortableValue(b, sortKey.value)
        const comparison = left.localeCompare(right, undefined, { numeric: true, sensitivity: 'base' })

        return sortDirection.value === 'asc' ? comparison : -comparison
    })
})

const hasFilters = computed(() => Boolean(activeState.value || appliedSearch.value))
const useCompactTableLayout = computed(() => activeColumns.value.length <= 10)

function updateColumnSettings(updatedColumns: Array<Column & { visible?: boolean }>) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)
    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/portal/dashboard/staffing/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showColumnSettings.value = false
        },
    })
}

function resetColumnSettingsLocally() {
    settingsForm.visibleColumns = [...props.visibleColumns]
    settingsForm.columnOrder = [...props.columnOrder]
}

function resetPreferencesOnServer() {
    const defaults = defaultColumnsForSettings.value
    updateColumnSettings(defaults)

    router.delete('/portal/dashboard/staffing/preferences', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showColumnSettings.value = false
        },
    })
}

function selectStaffingState(state: StaffingState) {
    // Clicking the active staffing card again returns the table to all statuses.
    activeState.value = activeState.value === state ? null : state
}

function applySearch() {
    appliedSearch.value = searchInput.value.trim()
}

function clearSearch() {
    searchInput.value = ''
    appliedSearch.value = ''
}

function resetTable() {
    activeState.value = null
    searchInput.value = ''
    appliedSearch.value = ''
    sortKey.value = 'position_code'
    sortDirection.value = 'asc'
}

function openWorkflow(position: StaffingPosition) {
    selectedPosition.value = position
    detailsOpen.value = true
}

function sortBy(key: string) {
    if (key === 'workflow_link') return

    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
        return
    }

    sortKey.value = key
    sortDirection.value = 'asc'
}


function sortableValue(position: StaffingPosition, key: string): string {
    if (key === 'staffing_state') return position.staffing_label ?? ''
    const value = (position as unknown as Record<string, unknown>)[key]
    return value === null || value === undefined ? '' : String(value)
}

function formatDate(value: string | null | undefined): string {
    if (!value) return '—'

    const date = new Date(value.length === 10 ? `${value}T00:00:00` : value)
    if (Number.isNaN(date.getTime())) return value

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date)
}

function formatDateTime(value: string | null | undefined): string {
    if (!value) return '—'

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date)
}

function formatCell(position: StaffingPosition, key: string): string {
    switch (key) {
        case 'created_at': return formatDate(position.created_at)
        case 'closed_at': return formatDate(position.closed_at)
        case 'scheduled_start_date': return formatDate(position.scheduled_start_date)
        case 'actual_start_date': return formatDate(position.actual_start_date)
        case 'departure_date': return formatDate(position.departure_date)
        case 'last_updated': return formatDateTime(position.last_updated)
        case 'staffing_state': return position.staffing_label
        default: {
            const value = (position as unknown as Record<string, unknown>)[key]
            return value === null || value === undefined || value === '' ? '—' : String(value)
        }
    }
}

function summaryLabel(state: StaffingState): string {
    return state === 'on_hold'
        ? 'On-Hold'
        : state.charAt(0).toUpperCase() + state.slice(1)
}

function staffingTone(state: StaffingState): 'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'dark' {
    if (state === 'filled') return 'dark'
    if (state === 'selected') return 'success'
    if (state === 'departing' || state === 'on_hold') return 'warning'
    if (state === 'vacant') return 'danger'
    return 'neutral'
}

function columnLayoutClass(key: string): string {
    const widths: Record<string, string> = {
        position_code: 'w-[10%]',
        title: 'w-[14%]',
        staffing_state: 'w-[11%]',
        level: 'w-[5%]',
        team_name: 'w-[14%]',
        location: 'w-[11%]',
        building: 'w-[8%]',
        created_at: 'w-[8%]',
        closed_at: 'w-[8%]',
        workflow_link: 'w-[11%]',
    }

    return widths[key] ?? ''
}

function columnWrapClass(key: string): string {
    if (['title', 'team_name', 'location'].includes(key)) {
        return 'whitespace-normal break-words'
    }

    return 'whitespace-nowrap'
}

function exportCsv() {
    downloadFile('/portal/dashboard/staffing/export/csv', {
        search: appliedSearch.value,
        staffing_state: activeState.value,
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, 'staffing-matrix.csv')
}
</script>

<template>
    <div class="space-y-6">
        <PositionStaffingSummary
            :summary="summary"
            :active-state="activeState"
            @select="selectStaffingState"
        />

        <DownloadErrorAlert :error="downloadError" />

        <ColumnSettings
            v-model:open="showColumnSettings"
            :columns="columnsForSettings"
            :default-columns="defaultColumnsForSettings"
            @update:columns="updateColumnSettings"
            @save="saveColumnPreferences"
            @reset="resetColumnSettingsLocally"
            @reset-defaults="resetPreferencesOnServer"
        />

        <Card>
            <CardHeader class="border-b">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg border bg-muted/40 p-2">
                            <BriefcaseBusiness class="h-5 w-5" aria-hidden="true" />
                        </div>
                        <div>
                            <CardTitle>{{ title }}</CardTitle>
                            <CardDescription class="mt-1">{{ description }}</CardDescription>
                            <p v-if="hasFilters" class="mt-2 text-sm font-medium text-primary">
                                Showing {{ filteredPositions.length }} of {{ positions.length }} positions
                                <template v-if="activeState"> · {{ summaryLabel(activeState) }}</template>
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" @click="showColumnSettings = true">
                            <Settings2 class="h-4 w-4" />
                            Column Settings
                        </Button>
                        <Button type="button" variant="outline" :disabled="isDownloading" @click="exportCsv">
                            <Download class="h-4 w-4" />
                            {{ isDownloading ? 'Exporting...' : 'Export CSV' }}
                        </Button>
                        <Button type="button" variant="outline" :disabled="!hasFilters" @click="resetTable">
                            <RotateCcw class="h-4 w-4" />
                            Reset Table
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="space-y-4 p-4">
                <form class="rounded-xl border bg-background p-4 shadow-sm" role="search" @submit.prevent="applySearch">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1 space-y-2">
                            <Label for="staffing-search">Search</Label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    id="staffing-search"
                                    v-model="searchInput"
                                    class="pl-9"
                                    placeholder="Search staffing matrix..."
                                />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button type="submit">Apply Filters</Button>
                            <Button type="button" variant="outline" @click="clearSearch">Clear</Button>
                        </div>
                    </div>
                </form>

                <div v-if="filteredPositions.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                    <BriefcaseBusiness class="mx-auto h-8 w-8 text-muted-foreground" />
                    <p class="mt-3 text-sm font-medium">No positions match the current table filters.</p>
                    <Button v-if="hasFilters" class="mt-4" variant="outline" size="sm" @click="resetTable">
                        Reset Table
                    </Button>
                </div>

                <div v-else class="overflow-x-auto rounded-xl border">
                    <Table :class="useCompactTableLayout ? 'table-fixed' : ''">
                        <TableHeader>
                            <TableRow>
                                <SortableTableHead
                                    v-for="column in activeColumns"
                                    :key="column.key"
                                    :sortable="column.key !== 'workflow_link'"
                                    :direction="sortKey === column.key ? sortDirection : null"
                                    :class="[
                                        columnLayoutClass(column.key),
                                        columnWrapClass(column.key),
                                    ].join(' ')"
                                    :aria-label="column.key !== 'workflow_link' ? `Sort by ${column.label}` : undefined"
                                    @sort="sortBy(column.key)"
                                >
                                    {{ column.label }}
                                </SortableTableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="position in filteredPositions" :key="position.id">
                                <TableCell
                                    v-for="column in activeColumns"
                                    :key="column.key"
                                    :class="[columnLayoutClass(column.key), columnWrapClass(column.key)]"
                                >
                                    <Link
                                        v-if="column.key === 'position_code'"
                                        :href="`/portal/positions/${position.id}`"
                                        class="inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ position.position_code || '—' }}
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </Link>

                                    <StatusBadge
                                        v-else-if="column.key === 'staffing_state'"
                                        :label="position.staffing_label"
                                        :tone="staffingTone(position.staffing_state)"
                                    />

                                    <Button
                                        v-else-if="column.key === 'workflow_link'"
                                        type="button"
                                        variant="link"
                                        class="h-auto px-0"
                                        @click="openWorkflow(position)"
                                    >
                                        <Workflow class="h-4 w-4" />
                                        {{ position.workflow_link || 'View Workflow' }}
                                    </Button>

                                    <span v-else>{{ formatCell(position, column.key) }}</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>

        <StaffingDetailsSheet
            v-model:open="detailsOpen"
            :position="selectedPosition"
        />
    </div>
</template>
