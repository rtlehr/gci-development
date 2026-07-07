<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

withDefaults(defineProps<{
    search?: string;
    searchLabel?: string;
    searchPlaceholder?: string;
    showSearch?: boolean;
    applyLabel?: string;
    resetLabel?: string;
}>(), {
    search: '',
    searchLabel: 'Search',
    searchPlaceholder: 'Search...',
    showSearch: true,
    applyLabel: 'Apply',
    resetLabel: 'Reset',
});

const emit = defineEmits<{
    'update:search': [value: string];
    apply: [];
    reset: [];
}>();
</script>

<template>
    <div class="border rounded-xl p-4 bg-background">
        <form
            class="space-y-4"
            @submit.prevent="emit('apply')"
        >
            <div class="flex flex-col md:flex-row gap-4 md:items-end">
                <div
                    v-if="showSearch"
                    class="flex-1 space-y-2"
                >
                    <Label for="list-search">
                        {{ searchLabel }}
                    </Label>

                    <Input
                        id="list-search"
                        :model-value="search"
                        :placeholder="searchPlaceholder"
                        @update:model-value="emit('update:search', String($event))"
                    />
                </div>

                <slot name="filters" />

                <div class="flex gap-2">
                    <Button type="submit">
                        {{ applyLabel }}
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('reset')"
                    >
                        {{ resetLabel }}
                    </Button>
                </div>
            </div>

            <slot name="advanced-filters" />
        </form>
    </div>
</template>