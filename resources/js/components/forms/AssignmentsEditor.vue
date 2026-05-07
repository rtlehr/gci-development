<template>
    <Card class="rounded-xl">
        <CardHeader>
            <CardTitle>Assignments</CardTitle>
            <CardDescription>
                Assign this person to one or more groups and teams.
            </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
            <!-- Groups -->
            <div class="space-y-3">
                <div>
                    <Label>Groups</Label>
                    <p class="text-sm text-muted-foreground">
                        Select the groups this person belongs to.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label
                        v-for="group in groups"
                        :key="group.id"
                        class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer hover:bg-muted/50"
                    >
                        <input
                            v-model="selectedGroupIds"
                            :value="group.id"
                            type="checkbox"
                            class="h-4 w-4"
                        />

                        <span class="text-sm font-medium">
                            {{ group.group_name }}
                        </span>
                    </label>
                </div>

                <p v-if="errors.group_ids" class="text-sm text-red-500">
                    {{ errors.group_ids }}
                </p>
            </div>

            <!-- Teams -->
            <div class="space-y-3">
                <div>
                    <Label>Teams</Label>
                    <p class="text-sm text-muted-foreground">
                        Select the teams this person belongs to.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label
                        v-for="team in teams"
                        :key="team.id"
                        class="flex items-center gap-3 rounded-lg border p-3 cursor-pointer hover:bg-muted/50"
                    >
                        <input
                            v-model="selectedTeamIds"
                            :value="team.id"
                            type="checkbox"
                            class="h-4 w-4"
                        />

                        <span class="text-sm font-medium">
                            {{ team.team_name }}
                        </span>
                    </label>
                </div>

                <p v-if="errors.team_ids" class="text-sm text-red-500">
                    {{ errors.team_ids }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>

<script setup>
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'

const props = defineProps({
    groupIds: {
        type: Array,
        default: () => [],
    },
    teamIds: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Array,
        default: () => [],
    },
    teams: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
})

const emit = defineEmits([
    'update:groupIds',
    'update:teamIds',
])

const selectedGroupIds = computed({
    get() {
        return props.groupIds
    },
    set(value) {
        emit('update:groupIds', value)
    },
})

const selectedTeamIds = computed({
    get() {
        return props.teamIds
    },
    set(value) {
        emit('update:teamIds', value)
    },
})
</script>