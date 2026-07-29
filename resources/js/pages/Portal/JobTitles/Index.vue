<template>
    <div class="p-6 space-y-6">
        <ListToolbar
            title="Job Titles"
            description="Manage master job titles, default skills, and default tasks."
            create-label="Create Job Title"
            create-href="/portal/job-titles/create"
            :can-create="true"
            :can-export="false"
            @open-column-settings="showColumnSettings = true"
        />

        <ColumnSettings
            v-model:open="showColumnSettings"
            :columns="columnsForSettings"
            :default-columns="defaultColumnsForSettings"
            @update:columns="updateColumnSettings"
            @save="saveColumnPreferences"
            @reset="resetColumnSettingsLocally"
            @reset-defaults="resetPreferencesOnServer"
        />

        <div class="border rounded-xl bg-background overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="col in activeColumns"
                            :key="col.key"
                        >
                            {{ col.label }}
                        </TableHead>

                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!jobTitles.length">
                        <TableCell :colspan="activeColumns.length + 1" class="text-center py-8 text-muted-foreground">
                            No job titles found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="jobTitle in jobTitles"
                        :key="jobTitle.id"
                    >
                        <TableCell
                            v-for="col in activeColumns"
                            :key="col.key"
                            :class="col.key === 'name' ? 'font-medium' : ''"
                        >
                            <template v-if="col.key === 'is_active'">
                                <Badge :variant="jobTitle.is_active ? 'default' : 'secondary'">
                                    {{ jobTitle.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </template>

                            <template v-else>
                                {{ formatCell(jobTitle, col.key) }}
                            </template>
                        </TableCell>

                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/portal/job-titles/${jobTitle.id}`">
                                    <Button variant="outline" size="sm">
                                        View
                                    </Button>
                                </Link>

                                <Link :href="`/portal/job-titles/${jobTitle.id}/edit`">
                                    <Button variant="outline" size="sm">
                                        Edit
                                    </Button>
                                </Link>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    @click="deleteJobTitle(jobTitle.id)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ColumnSettings from '@/components/Lists/ColumnSettings.vue'
import ListToolbar from '@/components/Lists/ListToolbar.vue'

import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

const props = defineProps({
    jobTitles: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        default: () => [
            { key: 'name', label: 'Name', sortable: false },
            { key: 'description', label: 'Description', sortable: false },
            { key: 'skills_count', label: 'Skills', sortable: false },
            { key: 'tasks_count', label: 'Tasks', sortable: false },
            { key: 'positions_count', label: 'Positions', sortable: false },
            { key: 'is_active', label: 'Status', sortable: false },
        ],
    },
    visibleColumns: {
        type: Array,
        default: () => [
            'name',
            'description',
            'skills_count',
            'tasks_count',
            'positions_count',
            'is_active',
        ],
    },
    columnOrder: {
        type: Array,
        default: () => [
            'name',
            'description',
            'skills_count',
            'tasks_count',
            'positions_count',
            'is_active',
        ],
    },
})

const showColumnSettings = ref(false)

const settingsForm = reactive({
    visibleColumns: [...(props.visibleColumns ?? [])],
    columnOrder: [...(props.columnOrder ?? [])],
})

const jobTitles = props.jobTitles ?? []

const activeColumns = computed(() => {
    return settingsForm.columnOrder
        .filter((key) => settingsForm.visibleColumns.includes(key))
        .map((key) => props.columns.find((col) => col.key === key))
        .filter(Boolean)
})

const columnsForSettings = computed(() => {
    return settingsForm.columnOrder
        .map((key) => {
            const column = props.columns.find((col) => col.key === key)

            if (!column) {
                return null
            }

            return {
                ...column,
                visible: settingsForm.visibleColumns.includes(key),
            }
        })
        .filter(Boolean)
})

const defaultColumnsForSettings = computed(() => {
    return props.columns.map((column) => ({
        ...column,
        visible: true,
    }))
})

function updateColumnSettings(updatedColumns) {
    settingsForm.visibleColumns = updatedColumns
        .filter((column) => column.visible !== false)
        .map((column) => column.key)

    settingsForm.columnOrder = updatedColumns.map((column) => column.key)
}

function saveColumnPreferences(updatedColumns = columnsForSettings.value) {
    updateColumnSettings(updatedColumns)

    router.post('/portal/job-titles/preferences', {
        visible_columns: settingsForm.visibleColumns,
        column_order: settingsForm.columnOrder,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showColumnSettings.value = false
        },
    })
}

function resetColumnSettingsLocally() {
    settingsForm.visibleColumns = [...(props.visibleColumns ?? [])]
    settingsForm.columnOrder = [...(props.columnOrder ?? [])]
}

function resetPreferencesOnServer() {
    router.delete('/portal/job-titles/preferences', {
        preserveScroll: true,
    })
}

function formatCell(jobTitle, key) {
    if (key === 'skills_count') {
        return jobTitle.skills_count ?? 0
    }

    if (key === 'tasks_count') {
        return jobTitle.tasks_count ?? 0
    }

    if (key === 'positions_count') {
        return jobTitle.positions_count ?? 0
    }

    const value = jobTitle[key]

    if (value === null || value === undefined || value === '') {
        return '—'
    }

    return value
}

function deleteJobTitle(id) {
    if (!confirm('Delete this Job Title?')) {
        return
    }

    router.delete(`/portal/job-titles/${id}`, {
        preserveScroll: true,
    })
}
</script>
