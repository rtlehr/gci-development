<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

defineOptions({ inheritAttrs: false });

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});
const attrs = useAttrs();
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset
        v-if="props.variant === 'sidebar'"
        v-bind="attrs"
        :class="className"
    >
        <slot />
    </SidebarInset>
    <main
        v-else
        v-bind="attrs"
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="className"
    >
        <slot />
    </main>
</template>
