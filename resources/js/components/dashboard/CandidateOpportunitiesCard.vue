<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BriefcaseBusiness, CheckCircle2, Clock3 } from 'lucide-vue-next';

type Opportunity = {
    candidate_id: number;
    position_id: number;
    position_code: string | null;
    position_title: string;
    position_status: string | null;
    candidate_status: string | null;
    workflow_name: string | null;
    current_stage: string;
    step_number: number;
    step_count: number;
    status_code: string | null;
    last_updated: string | null;
};

defineProps<{
    opportunities: Opportunity[];
}>();

function progressWidth(opportunity: Opportunity): string {
    if (!opportunity.step_count) return '0%';

    return `${Math.min(
        100,
        Math.max(0, (opportunity.step_number / opportunity.step_count) * 100),
    )}%`;
}

function formattedDate(value: string | null): string {
    if (!value) return 'Not available';

    return new Date(value).toLocaleDateString();
}
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-[#e3e3e3] bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-[#e3e3e3] p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <BriefcaseBusiness class="h-5 w-5 text-[#005c43]" />
                    <h2 class="text-lg font-bold">My Opportunities</h2>
                </div>
                <p class="mt-1 text-sm text-[#3a3a3a]/70">
                    Positions you have been added to and your current workflow progress.
                </p>
            </div>
        </div>

        <div v-if="opportunities.length" class="divide-y divide-[#e3e3e3]">
            <article
                v-for="opportunity in opportunities"
                :key="opportunity.candidate_id"
                class="p-5"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wide text-[#005c43]">
                                {{ opportunity.position_code || 'Position' }}
                            </span>
                            <span class="rounded-full bg-[#e3e3e3]/70 px-2.5 py-1 text-xs font-medium">
                                {{ opportunity.position_status || 'Unknown status' }}
                            </span>
                        </div>

                        <h3 class="mt-2 text-lg font-semibold">
                            {{ opportunity.position_title }}
                        </h3>

                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">
                                    Candidate status
                                </div>
                                <div class="mt-1 font-medium">
                                    {{ opportunity.candidate_status || 'Not set' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">
                                    Current stage
                                </div>
                                <div class="mt-1 flex items-center gap-1.5 font-medium">
                                    <Clock3 class="h-4 w-4 text-[#005c43]" />
                                    {{ opportunity.current_stage }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide text-[#3a3a3a]/55">
                                    Last updated
                                </div>
                                <div class="mt-1 font-medium">
                                    {{ formattedDate(opportunity.last_updated) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="mb-2 flex items-center justify-between text-xs">
                                <span class="font-medium">
                                    {{ opportunity.workflow_name || 'Workflow' }}
                                </span>
                                <span class="text-[#3a3a3a]/65">
                                    Step {{ opportunity.step_number }} of {{ opportunity.step_count }}
                                </span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-[#e3e3e3]"
                                role="progressbar"
                                :aria-valuenow="opportunity.step_number"
                                aria-valuemin="0"
                                :aria-valuemax="opportunity.step_count"
                            >
                                <div
                                    class="h-full rounded-full bg-[#005c43]"
                                    :style="{ width: progressWidth(opportunity) }"
                                />
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="`/portal/candidates/${opportunity.candidate_id}`"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-[#005c43] px-4 py-2 text-sm font-semibold text-[#005c43] transition hover:bg-[#005c43] hover:text-white"
                    >
                        View Progress
                        <ArrowRight class="h-4 w-4" />
                    </Link>
                </div>
            </article>
        </div>

        <div v-else class="p-8 text-center">
            <CheckCircle2 class="mx-auto h-8 w-8 text-[#005c43]" />
            <h3 class="mt-3 font-semibold">No active opportunities</h3>
            <p class="mt-1 text-sm text-[#3a3a3a]/70">
                Positions will appear here after you are added as a candidate.
            </p>
        </div>
    </section>
</template>
