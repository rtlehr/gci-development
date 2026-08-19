<script setup>
import { Check, ShieldCheck } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps({
    roles: { type: Array, default: () => [] },
    modelValue: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

function isSelected(id) {
    return props.modelValue.map(Number).includes(Number(id))
}

function toggleRole(id, checked = null) {
    const values = new Set(props.modelValue.map(Number))
    const shouldSelect = checked === null ? !values.has(Number(id)) : Boolean(checked)

    if (shouldSelect) {
        values.add(Number(id))
    } else {
        values.delete(Number(id))
    }

    emit('update:modelValue', [...values])
}

function roleLabel(role) {
    return role.label || role.name
}
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-start gap-3">
                <div class="rounded-lg border bg-muted/50 p-2">
                    <ShieldCheck class="h-5 w-5" />
                </div>
                <div>
                    <CardTitle>Roles & Access</CardTitle>
                    <CardDescription>
                        Assign application roles to the linked user account.
                    </CardDescription>
                </div>
            </div>
        </CardHeader>

        <CardContent>
            <div v-if="roles.length" class="grid gap-3 md:grid-cols-2">
                <button
                    v-for="role in roles"
                    :key="role.id"
                    type="button"
                    class="flex w-full items-start gap-3 rounded-lg border p-4 text-left transition hover:bg-muted/40"
                    :class="isSelected(role.id) ? 'border-primary bg-primary/5 ring-1 ring-primary/20' : ''"
                    :aria-pressed="isSelected(role.id)"
                    @click="toggleRole(role.id)"
                >
                    <span
                        aria-hidden="true"
                        class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border border-input"
                        :class="isSelected(role.id) ? 'border-primary bg-primary text-primary-foreground' : 'bg-background'"
                    >
                        <Check v-if="isSelected(role.id)" class="h-3 w-3" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium">{{ roleLabel(role) }}</span>
                        <span
                            v-if="role.description"
                            class="mt-1 block text-xs text-muted-foreground"
                        >
                            {{ role.description }}
                        </span>
                        <span
                            v-else-if="role.name !== roleLabel(role)"
                            class="mt-1 block text-xs text-muted-foreground"
                        >
                            {{ role.name }}
                        </span>
                    </span>
                </button>
            </div>

            <p
                v-else
                class="rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
            >
                No roles are currently configured.
            </p>

            <p v-if="errors.role_ids" class="mt-2 text-sm text-red-500">
                {{ errors.role_ids }}
            </p>
        </CardContent>
    </Card>
</template>
