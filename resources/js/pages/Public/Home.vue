<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CircleHelp, ContactRound, FileText, LifeBuoy, UsersRound } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    isAuthenticated: boolean;
    program: {
        name: string;
        summary: string;
        contractYear: string;
        contractNumber: string;
        periodOfPerformance: string;
    };
}>();
</script>

<template>
    <Head title="Welcome" />

    <section class="border-b border-[#e3e3e3] bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.4fr_0.8fr] lg:px-8 lg:py-20">
            <div class="max-w-3xl">
                <p class="mb-3 text-sm font-bold uppercase tracking-[0.18em] text-[#005c43]">ZION Program</p>
                <h1 class="text-4xl font-bold tracking-tight text-[#3a3a3a] sm:text-5xl">Welcome to the {{ program.name }}</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-[#3a3a3a]/75">{{ program.summary }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link v-if="isAuthenticated" href="/portal/dashboard">
                        <Button class="bg-[#005c43] text-white hover:bg-[#004734]">Open my portal <ArrowRight class="ml-2 h-4 w-4" /></Button>
                    </Link>
                    <a href="#program"><Button variant="outline" class="border-[#005c43]/30">Program details</Button></a>
                </div>
            </div>

            <aside id="program" class="rounded-2xl border border-[#e3e3e3] bg-[#f7f8f7] p-6 shadow-sm">
                <h2 class="text-lg font-bold text-[#005c43]">Program details</h2>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="font-semibold">Contract year</dt><dd class="mt-1 text-[#3a3a3a]/75">{{ program.contractYear }}</dd></div>
                    <div><dt class="font-semibold">Contract number</dt><dd class="mt-1 text-[#3a3a3a]/75">{{ program.contractNumber }}</dd></div>
                    <div><dt class="font-semibold">Period of performance</dt><dd class="mt-1 text-[#3a3a3a]/75">{{ program.periodOfPerformance }}</dd></div>
                </dl>
            </aside>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><UsersRound class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">Program contacts</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Find program leadership, PMO contacts, and customer points of contact.</p></article>
            <article id="resources" class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><BookOpen class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">Resources</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Access approved program guidance, forms, policies, and reference materials.</p></article>
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><FileText class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">Requests</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Authenticated users will be able to submit and monitor program requests.</p></article>
            <article id="faqs" class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><CircleHelp class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">Frequently asked questions</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Get clear answers to common questions about the program and portal.</p></article>
            <article id="support" class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><LifeBuoy class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">Support</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Report a problem, request assistance, or follow an existing support ticket.</p></article>
            <article class="rounded-xl border border-[#e3e3e3] bg-white p-6 shadow-sm"><ContactRound class="h-7 w-7 text-[#005c43]" /><h2 class="mt-4 text-lg font-bold">PMO</h2><p class="mt-2 text-sm leading-6 text-[#3a3a3a]/70">Connect with the program management office and operational support team.</p></article>
        </div>
    </section>
</template>
