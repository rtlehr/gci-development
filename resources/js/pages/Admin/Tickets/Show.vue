<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'

// Ticket record and assignable user list
// passed from the backend.
const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    assignableUsers: {
        type: Array,
        default: () => [],
    },
    currentUser: {
        type: Object,
        required: true,
    },
})

// Reactive form state used to manage
// ticket updates such as status, assignment,
// importance, and resolution notes.
const ticketForm = useForm({
    status: props.ticket.status ?? 'new',
    importance: props.ticket.importance ?? 'nice_to_have',
    assigned_to_user_id: props.ticket.assigned_to_user_id
        ? String(props.ticket.assigned_to_user_id)
        : '',
    resolution_notes: props.ticket.resolution_notes ?? '',
})

// Reactive form state for adding
// timeline/activity comments.
const commentForm = useForm({
    comment: '',
})

/**
 * Saves ticket updates to the backend.
 */
function saveTicket() {
    ticketForm.put(`/admin/tickets/${props.ticket.id}`)
}

/**
 * Assigns the ticket form to the current active user.
 * This also works with impersonation as long as the backend
 * passes the resolved current user.
 */
function assignToMe() {
    ticketForm.assigned_to_user_id = String(props.currentUser.id)

    if (ticketForm.status === 'new') {
        ticketForm.status = 'in_progress'
    }
}

/**
 * Adds a new activity comment to the ticket.
 * Clears the comment field after successful save.
 */
function addComment() {
    commentForm.post(`/admin/tickets/${props.ticket.id}/comments`, {
        preserveScroll: true,

        onSuccess: () => {
            commentForm.reset()
        },
    })
}

/**
 * Builds the display name for the user
 * who submitted the ticket.
 *
 * @param {Object} ticket
 * @returns {string}
 */
function submittedByName(ticket) {
    const first = ticket.submitted_by?.person?.first_name ?? ''
    const last = ticket.submitted_by?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.submitted_by?.name || '—'
}

/**
 * Builds the display name for the assigned user.
 * Returns "Unassigned" if no user is assigned.
 *
 * @param {Object} ticket
 * @returns {string}
 */
function assignedToName(ticket) {
    if (!ticket.assigned_to) return 'Unassigned'

    const first = ticket.assigned_to?.person?.first_name ?? ''
    const last = ticket.assigned_to?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || ticket.assigned_to?.name || '—'
}

/**
 * Builds the display name for the user
 * who performed a ticket activity.
 *
 * @param {Object} activity
 * @returns {string}
 */
function activityUserName(activity) {
    const first = activity.changed_by?.person?.first_name ?? ''
    const last = activity.changed_by?.person?.last_name ?? ''
    const name = `${first} ${last}`.trim()

    return name || activity.changed_by?.name || 'System'
}

/**
 * Converts stored status values
 * into user-friendly display text.
 *
 * @param {string} status
 * @returns {string}
 */
function formatStatus(status) {
    if (status === 'new') return 'New'
    if (status === 'in_progress') return 'In Progress'
    if (status === 'on_hold') return 'On Hold'
    if (status === 'complete') return 'Complete'
    if (status === 'canceled') return 'Canceled'

    return status || '—'
}

/**
 * Converts stored importance values
 * into user-friendly display text.
 *
 * @param {string} importance
 * @returns {string}
 */
function formatImportance(importance) {
    if (importance === 'show_stopper') return 'Show Stopper'
    if (importance === 'asap') return 'Needed ASAP'
    if (importance === 'nice_to_have') return 'Nice to Have'

    return importance || '—'
}

/**
 * Converts stored request type values
 * into user-friendly display text.
 *
 * @param {string} type
 * @returns {string}
 */
function formatRequestType(type) {
    if (type === 'bug') return 'Bug'
    if (type === 'improvement') return 'Improvement'

    return type || '—'
}

/**
 * Returns Tailwind badge classes
 * based on ticket status.
 *
 * @param {string} status
 * @returns {string}
 */
function statusBadgeClass(status) {
    if (status === 'new') return 'bg-gray-500 text-white hover:bg-gray-500'
    if (status === 'in_progress') return 'bg-blue-600 text-white hover:bg-blue-600'
    if (status === 'on_hold') return 'bg-yellow-500 text-black hover:bg-yellow-500'
    if (status === 'complete') return 'bg-green-600 text-white hover:bg-green-600'
    if (status === 'canceled') return 'bg-red-600 text-white hover:bg-red-600'

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}

/**
 * Returns Tailwind badge classes
 * based on ticket importance level.
 *
 * @param {string} importance
 * @returns {string}
 */
function importanceBadgeClass(importance) {
    if (importance === 'show_stopper') return 'bg-red-600 text-white hover:bg-red-600'
    if (importance === 'asap') return 'bg-orange-500 text-white hover:bg-orange-500'
    if (importance === 'nice_to_have') return 'bg-gray-500 text-white hover:bg-gray-500'

    return 'bg-gray-200 text-gray-800 hover:bg-gray-200'
}

/**
 * Builds a readable activity description
 * based on the activity event type.
 *
 * @param {Object} activity
 * @returns {string}
 */
function activityDescription(activity) {
    if (activity.event_type === 'created') {
        return 'created this ticket.'
    }

    if (activity.event_type === 'status_changed') {
        return `changed status from "${formatStatus(activity.old_value)}" to "${formatStatus(activity.new_value)}".`
    }

    if (activity.event_type === 'importance_changed') {
        return `changed importance from "${formatImportance(activity.old_value)}" to "${formatImportance(activity.new_value)}".`
    }

    if (activity.event_type === 'assignment_changed') {
        return `changed assignment from "${activity.old_value || 'Unassigned'}" to "${activity.new_value || 'Unassigned'}".`
    }

    if (activity.event_type === 'resolution_updated') {
        return 'updated the resolution notes.'
    }

    if (activity.event_type === 'comment_added') {
        return 'added a comment.'
    }

    return activity.event_type
}

/**
 * Formats a date/time value into
 * a readable localized timestamp.
 *
 * @param {string} value
 * @returns {string}
 */
function formatDateTime(value) {
    if (!value) return '—'

    const date = new Date(value)

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date)
}

// Computed source URL value used by the template.
// Returns a placeholder if no source URL exists.
const sourceUrl = computed(() => props.ticket.source_url || '—')
</script>

<template>
    <div class="p-6 max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">
                    {{ ticket.ticket_number }} — {{ ticket.title }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Manage ticket details, status, assignment, and history.
                </p>
            </div>

            <Link href="/admin/tickets">
                <Button variant="outline">Back to Tickets</Button>
            </Link>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="border rounded-xl p-6 bg-background space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Ticket Details</h2>
                        <p class="text-sm text-muted-foreground">
                            Original request information.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="font-medium">{{ formatDateTime(ticket.created_at) }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Submitted By</p>
                            <p class="font-medium">{{ submittedByName(ticket) }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Request Type</p>
                            <p class="font-medium">{{ formatRequestType(ticket.request_type) }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Status</p>
                            <Badge :class="statusBadgeClass(ticket.status)">
                                {{ formatStatus(ticket.status) }}
                            </Badge>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Importance</p>
                            <Badge :class="importanceBadgeClass(ticket.importance)">
                                {{ formatImportance(ticket.importance) }}
                            </Badge>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Assigned To</p>
                            <p class="font-medium">{{ assignedToName(ticket) }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Category</p>
                            <p class="font-medium">{{ ticket.category || '—' }}</p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Source Page</p>
                        <a
                            v-if="ticket.source_url"
                            :href="ticket.source_url"
                            class="text-sm underline break-all"
                            target="_blank"
                        >
                            {{ sourceUrl }}
                        </a>
                        <p v-else class="font-medium">—</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm text-muted-foreground">Description</p>
                        <div class="rounded-lg border p-4 whitespace-pre-wrap">
                            {{ ticket.description }}
                        </div>
                    </div>

                    <div v-if="ticket.screenshot_path" class="space-y-2">
                    <p class="text-sm text-muted-foreground">Screenshot</p>

                    <a
                        :href="`/storage/${ticket.screenshot_path}`"
                        target="_blank"
                        class="inline-block"
                    >
                        <img
                            :src="`/storage/${ticket.screenshot_path}`"
                            alt="Ticket screenshot"
                            class="max-h-[400px] rounded-lg border"
                        />
                    </a>
                </div>

                </div>

                <div class="border rounded-xl p-6 bg-background space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Activity Timeline</h2>
                        <p class="text-sm text-muted-foreground">
                            Every significant change to this ticket is logged here.
                        </p>
                    </div>

                    <div v-if="ticket.activities?.length" class="space-y-4">
                        <div
                            v-for="activity in ticket.activities"
                            :key="activity.id"
                            class="rounded-lg border p-4 space-y-2"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <p class="font-medium">
                                    {{ activityUserName(activity) }} {{ activityDescription(activity) }}
                                </p>
                                <p class="text-xs text-muted-foreground whitespace-nowrap">
                                    {{ formatDateTime(activity.created_at) }}
                                </p>
                            </div>

                            <div
                                v-if="activity.comment"
                                class="text-sm whitespace-pre-wrap rounded-md bg-muted p-3"
                            >
                                {{ activity.comment }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-sm text-muted-foreground">
                        No activity yet.
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="border rounded-xl p-6 bg-background space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Manage Ticket</h2>
                        <p class="text-sm text-muted-foreground">
                            Update ticket status, assignment, and resolution.
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm text-muted-foreground">Last Updated</p>
                        <p class="font-medium">{{ formatDateTime(ticket.updated_at) }}</p>
                    </div>

                    <form @submit.prevent="saveTicket" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="ticketForm.status"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="new">New</option>
                                <option value="in_progress">In Progress</option>
                                <option value="on_hold">On Hold</option>
                                <option value="complete">Complete</option>
                                <option value="canceled">Canceled</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="importance">Importance</Label>
                            <select
                                id="importance"
                                v-model="ticketForm.importance"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="show_stopper">Show Stopper</option>
                                <option value="asap">Needed ASAP</option>
                                <option value="nice_to_have">Nice to Have</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <Label for="assigned_to_user_id">Assigned To</Label>

                            <select
                                id="assigned_to_user_id"
                                v-model="ticketForm.assigned_to_user_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="user in assignableUsers"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }}
                                </option>
                            </select>

                            <button
                                type="button"
                                class="text-xs font-medium text-primary underline-offset-4 hover:underline"
                                @click="assignToMe"
                            >
                                Assign to me
                            </button>
                        </div>

                        <div class="space-y-2">
                            <Label for="resolution_notes">Resolution Notes</Label>
                            <Textarea
                                id="resolution_notes"
                                v-model="ticketForm.resolution_notes"
                                rows="5"
                            />
                        </div>

                        <Button type="submit" :disabled="ticketForm.processing">
                            {{ ticketForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </form>
                </div>

                <div class="border rounded-xl p-6 bg-background space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">Add Comment</h2>
                        <p class="text-sm text-muted-foreground">
                            Add an internal update or note to the activity timeline.
                        </p>
                    </div>

                    <form @submit.prevent="addComment" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="comment">Comment</Label>
                            <Textarea
                                id="comment"
                                v-model="commentForm.comment"
                                rows="5"
                                :class="commentForm.errors.comment ? 'border-red-500' : ''"
                            />
                            <p v-if="commentForm.errors.comment" class="text-sm text-red-500">
                                {{ commentForm.errors.comment }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="commentForm.processing">
                            {{ commentForm.processing ? 'Adding...' : 'Add Comment' }}
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>