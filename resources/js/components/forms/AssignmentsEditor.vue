<template>
    <!-- Main card wrapper for group and team assignment controls -->
    <Card class="rounded-xl">
        <CardHeader>
            <CardTitle>Assignments</CardTitle>

            <!-- Short explanation of what this section controls -->
            <CardDescription>
                Assign this person to one or more groups and teams.
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
            <!-- Groups -->
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">Groups</legend>

                <!-- Helper text for the groups checkbox list -->
                <p id="groups-help" class="text-sm text-muted-foreground">
                    Select the groups this person belongs to.
                </p>

                <!-- Responsive grid of available groups -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2" aria-describedby="groups-help">
                    <label
                        v-for="group in groups"
                        :key="group.id"
                        :for="`assignment-group-${group.id}`"
                        class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer hover:bg-muted/50"
                    >
                        <!-- Adds/removes this group ID from the selectedGroupIds array -->
                        <input
                            :id="`assignment-group-${group.id}`"
                            v-model="selectedGroupIds"
                            :value="group.id"
                            type="checkbox"
                            class="h-4 w-4"
                        />

                        <!-- Display name for the group -->
                        <span class="text-sm font-medium">
                            {{ group.group_name }}
                        </span>
                    </label>
                </div>

                <!-- Validation error for group assignments -->
                <p v-if="errors.group_ids" class="text-sm text-red-500" role="alert">
                    {{ errors.group_ids }}
                </p>
            </fieldset>

            <!-- Teams -->
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">Teams</legend>

                <!-- Helper text for the teams checkbox list -->
                <p id="teams-help" class="text-sm text-muted-foreground">
                    Select the teams this person belongs to.
                </p>

                <!-- Responsive grid of available teams -->
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2" aria-describedby="teams-help">
                    <label
                        v-for="team in teams"
                        :key="team.id"
                        :for="`assignment-team-${team.id}`"
                        class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer hover:bg-muted/50"
                    >
                        <!-- Adds/removes this team ID from the selectedTeamIds array -->
                        <input
                            :id="`assignment-team-${team.id}`"
                            v-model="selectedTeamIds"
                            :value="team.id"
                            type="checkbox"
                            class="h-4 w-4"
                        />

                        <!-- Display name for the team -->
                        <span class="text-sm font-medium">
                            {{ team.team_name }}
                        </span>
                    </label>
                </div>

                <!-- Validation error for team assignments -->
                <p v-if="errors.team_ids" class="text-sm text-red-500" role="alert">
                    {{ errors.team_ids }}
                </p>
            </fieldset>
        </CardContent>
    </Card>
</template>

<script setup>
// Computed is used to create v-model-compatible wrappers around props
import { computed } from 'vue'

// Shared card UI components
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

// Shared label UI component

// Props received from the parent form/page
const props = defineProps({
    // Currently selected group IDs
    groupIds: {
        type: Array,
        default: () => [],
    },

    // Currently selected team IDs
    teamIds: {
        type: Array,
        default: () => [],
    },

    // Available groups to display as checkboxes
    groups: {
        type: Array,
        default: () => [],
    },

    // Available teams to display as checkboxes
    teams: {
        type: Array,
        default: () => [],
    },

    // Validation errors passed from the parent form
    errors: {
        type: Object,
        default: () => ({}),
    },
})

// Events emitted back to the parent for two-way binding
const emit = defineEmits([
    'update:groupIds',
    'update:teamIds',
])

// Computed wrapper for groupIds so the checkbox v-model can update the parent value
const selectedGroupIds = computed({
    get() {
        return props.groupIds
    },
    set(value) {
        emit('update:groupIds', value)
    },
})

// Computed wrapper for teamIds so the checkbox v-model can update the parent value
const selectedTeamIds = computed({
    get() {
        return props.teamIds
    },
    set(value) {
        emit('update:teamIds', value)
    },
})
</script>