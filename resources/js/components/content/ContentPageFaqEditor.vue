<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type FaqEditorItem = {
    id?: number;
    question: string;
    answer: string;
    is_active: boolean;
    sort_order: number;
};

const model = defineModel<FaqEditorItem[]>({ required: true });

function addItem(): void {
    model.value.push({
        question: '',
        answer: '',
        is_active: true,
        sort_order: (model.value.length + 1) * 10,
    });
}

function removeItem(index: number): void {
    model.value.splice(index, 1);
    normalizeSortOrder();
}

function moveItem(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= model.value.length) {
        return;
    }

    const [item] = model.value.splice(index, 1);
    model.value.splice(target, 0, item);
    normalizeSortOrder();
}

function normalizeSortOrder(): void {
    model.value.forEach((item, index) => {
        item.sort_order = (index + 1) * 10;
    });
}
</script>

<template>
    <section class="space-y-4 rounded-xl border bg-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">FAQ questions</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add, remove, reorder, and temporarily hide questions without editing HTML.
                </p>
            </div>

            <Button type="button" variant="outline" @click="addItem">
                <Plus class="mr-2 h-4 w-4" />
                Add Question
            </Button>
        </div>

        <div
            v-if="model.length === 0"
            class="rounded-lg border border-dashed px-5 py-10 text-center text-sm text-muted-foreground"
        >
            No questions have been added yet.
        </div>

        <article
            v-for="(item, index) in model"
            :key="item.id ?? `faq-${index}`"
            class="rounded-xl border bg-background p-4"
        >
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="font-semibold">Question {{ index + 1 }}</div>

                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === 0"
                        aria-label="Move question up"
                        @click="moveItem(index, -1)"
                    >
                        <ArrowUp class="h-4 w-4" />
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        :disabled="index === model.length - 1"
                        aria-label="Move question down"
                        @click="moveItem(index, 1)"
                    >
                        <ArrowDown class="h-4 w-4" />
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="text-destructive hover:text-destructive"
                        aria-label="Delete question"
                        @click="removeItem(index)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label :for="`faq-question-${index}`">Question</Label>
                    <Input
                        :id="`faq-question-${index}`"
                        v-model="item.question"
                        placeholder="Enter the question"
                    />
                </div>

                <div class="space-y-2">
                    <Label :for="`faq-answer-${index}`">Answer</Label>
                    <Textarea
                        :id="`faq-answer-${index}`"
                        v-model="item.answer"
                        rows="5"
                        placeholder="Enter the answer"
                    />
                </div>

                <label class="flex items-center gap-3 text-sm">
                    <input
                        v-model="item.is_active"
                        type="checkbox"
                        class="h-4 w-4 rounded border-input"
                    />
                    Show this question on the published page
                </label>
            </div>
        </article>
    </section>
</template>
