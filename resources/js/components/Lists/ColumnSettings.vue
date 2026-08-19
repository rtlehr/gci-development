<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import {
    ChevronDown,
    ChevronUp,
    GripVertical,
    RotateCcw,
    Save,
    Undo2,
} from 'lucide-vue-next';

type Column = {
    key: string;
    label: string;
    visible?: boolean;
    sortable?: boolean;
};

const props = defineProps<{
    open: boolean;
    columns: Column[];
    defaultColumns: Column[];
    isSaving?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'update:columns': [columns: Column[]];
    save: [columns: Column[]];
    reset: [];
    resetDefaults: [];
}>();

const localColumns = ref<Column[]>([]);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            localColumns.value = props.columns.map((column) => ({ ...column }));
        }
    },
    { immediate: true },
);

const visibleCount = computed(() =>
    localColumns.value.filter((column) => column.visible !== false).length,
);

const totalCount = computed(() => localColumns.value.length);

function close() {
    emit('update:open', false);
}

function toggleColumn(key: string, checked: boolean) {
    localColumns.value = localColumns.value.map((column) =>
        column.key === key ? { ...column, visible: checked } : column,
    );

    emit('update:columns', localColumns.value);
}

function moveColumn(index: number, direction: 'up' | 'down') {
    const targetIndex = direction === 'up' ? index - 1 : index + 1;

    if (targetIndex < 0 || targetIndex >= localColumns.value.length) {
        return;
    }

    const updated = [...localColumns.value];
    const [movedColumn] = updated.splice(index, 1);
    updated.splice(targetIndex, 0, movedColumn);

    localColumns.value = updated;
    emit('update:columns', localColumns.value);
}

function resetChanges() {
    localColumns.value = props.columns.map((column) => ({ ...column }));
    emit('reset');
}

function resetToDefaults() {
    localColumns.value = props.defaultColumns.map((column) => ({ ...column }));
    emit('update:columns', localColumns.value);
    emit('resetDefaults');
}

function save() {
    emit('save', localColumns.value);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-3xl max-h-[calc(100vh-4rem)] overflow-hidden flex flex-col p-0">
            <DialogHeader class="shrink-0 px-6 pt-6">
                <DialogTitle>Column Settings</DialogTitle>

                <DialogDescription>
                    Choose which columns are visible and adjust their order.
                </DialogDescription>

                <p class="text-sm text-muted-foreground">
                    {{ visibleCount }} of {{ totalCount }} columns visible
                </p>
            </DialogHeader>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4 space-y-3">
                <div
                    v-for="(column, index) in localColumns"
                    :key="column.key"
                    class="flex items-center justify-between rounded-md border px-3 py-2 transition-opacity"
                    :class="column.visible === false ? 'opacity-50' : ''"
                >
                    <div class="flex items-center gap-3">
                        <GripVertical class="h-4 w-4 text-muted-foreground" />

                        <span class="text-sm font-medium">
                            {{ column.label }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <Checkbox
                            :checked="column.visible !== false"
                            :disabled="visibleCount <= 1 && column.visible !== false"
                            :aria-label="`${column.visible === false ? 'Show' : 'Hide'} ${column.label} column`"
                            @update:checked="toggleColumn(column.key, Boolean($event))"
                        />

                        <div class="flex items-center gap-1">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === 0"
                                :aria-label="`Move ${column.label} column up`"
                                @click="moveColumn(index, 'up')"
                            >
                                <ChevronUp class="h-4 w-4" />
                            </Button>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                :disabled="index === localColumns.length - 1"
                                :aria-label="`Move ${column.label} column down`"
                                @click="moveColumn(index, 'down')"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="shrink-0 border-t px-6 py-4 bg-background flex flex-col gap-3 sm:flex-row sm:justify-between">
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="resetChanges">
                        <Undo2 class="mr-2 h-4 w-4" />
                        Reset
                    </Button>

                    <Button type="button" variant="outline" @click="resetToDefaults">
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Defaults
                    </Button>
                </div>

                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="close">
                        Cancel
                    </Button>

                    <Button type="button" :disabled="isSaving" @click="save">
                        <Save class="mr-2 h-4 w-4" />
                        {{ isSaving ? 'Saving...' : 'Save Preferences' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>