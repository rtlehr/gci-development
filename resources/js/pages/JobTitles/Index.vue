<template>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    Job Titles
                </h1>

                <p class="text-sm text-muted-foreground mt-1">
                    Manage master job titles, default skills, and default tasks.
                </p>
            </div>

            <Link href="/job-titles/create">
                <Button>
                    Create Job Title
                </Button>
            </Link>
        </div>

        <div class="border rounded-xl bg-background overflow-hidden">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Skills</TableHead>
                        <TableHead>Tasks</TableHead>
                        <TableHead>Positions</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="!jobTitles.length">
                        <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                            No job titles found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="jobTitle in jobTitles"
                        :key="jobTitle.id"
                    >
                        <TableCell class="font-medium">
                            {{ jobTitle.name }}
                        </TableCell>

                        <TableCell>
                            {{ jobTitle.description || '—' }}
                        </TableCell>

                        <TableCell>
                            {{ jobTitle.skills_count ?? 0 }}
                        </TableCell>

                        <TableCell>
                            {{ jobTitle.tasks_count ?? 0 }}
                        </TableCell>

                        <TableCell>
                            {{ jobTitle.positions_count ?? 0 }}
                        </TableCell>

                        <TableCell>
                            <Badge :variant="jobTitle.is_active ? 'default' : 'secondary'">
                                {{ jobTitle.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>

                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/job-titles/${jobTitle.id}`">
                                    <Button variant="outline" size="sm">
                                        View
                                    </Button>
                                </Link>

                                <Link :href="`/job-titles/${jobTitle.id}/edit`">
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
import { Link, router } from '@inertiajs/vue3'

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

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    jobTitles: {
        type: Array,
        default: () => [],
    },
})

/*
|--------------------------------------------------------------------------
| Template Data
|--------------------------------------------------------------------------
*/

const jobTitles = props.jobTitles ?? []

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

function deleteJobTitle(id) {
    if (!confirm('Delete this Job Title?')) {
        return
    }

    router.delete(`/job-titles/${id}`, {
        preserveScroll: true,
    })
}
</script>