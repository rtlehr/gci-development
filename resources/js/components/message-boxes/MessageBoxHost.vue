<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Box = { id:number; title:string; content_html?:string; display_type:'modal'|'top_banner'|'bottom_banner'; trigger_type:'automatic'|'manual'; trigger_key?:string; show_once:boolean; dismissible:boolean; actions:{label:string;url:string}[]; form_fields:{name:string;label:string;type:string;required?:boolean}[] };
const page=usePage();
const boxes=computed(() => (page.props.messageBoxes ?? []) as Box[]);
const openIds=ref<number[]>([]); const formData=reactive<Record<number,Record<string,any>>>({});
const visible=computed(()=>boxes.value.filter(b=>openIds.value.includes(b.id)));
function csrf(){return document.querySelector<HTMLMetaElement>('meta[name=\"csrf-token\"]')?.content??'';}
function postQuiet(url:string){fetch(url,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf(),'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});}
function markSeen(b:Box){ postQuiet(`/message-boxes/${b.id}/seen`); }
function open(b:Box){ if(!openIds.value.includes(b.id)){ openIds.value.push(b.id); formData[b.id]??={}; if(b.show_once) markSeen(b); } }
function close(b:Box){ if(!b.dismissible)return; openIds.value=openIds.value.filter(id=>id!==b.id); postQuiet(`/message-boxes/${b.id}/dismiss`); }
function submit(b:Box){ router.post(`/message-boxes/${b.id}/submit`,{data:formData[b.id]??{}},{preserveScroll:true,onSuccess:()=>close(b)}); }
function trigger(e:Event){ const key=(e as CustomEvent).detail?.key; boxes.value.filter(b=>b.trigger_type==='manual'&&b.trigger_key===key).forEach(open); }
function initialize(){ boxes.value.filter(b=>b.trigger_type==='automatic').forEach(open); }
watch(boxes, initialize, {immediate:true}); onMounted(()=>window.addEventListener('insite:message-box',trigger)); onBeforeUnmount(()=>window.removeEventListener('insite:message-box',trigger));
</script>
<template>
  <Teleport to="body">
    <template v-for="box in visible" :key="box.id">
      <div v-if="box.display_type==='modal'" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" :aria-label="box.title">
        <section class="max-h-[85vh] w-full max-w-2xl overflow-auto rounded-xl border bg-background p-6 shadow-2xl">
          <div class="flex items-start justify-between gap-4"><h2 class="text-xl font-semibold">{{box.title}}</h2><Button v-if="box.dismissible" variant="ghost" size="icon" @click="close(box)" :aria-label="`Close ${box.title}`"><X class="size-5"/></Button></div>
          <div class="prose prose-sm mt-4 max-w-none dark:prose-invert" v-html="box.content_html"></div>
          <div v-if="box.form_fields.length" class="mt-5 space-y-4"><label v-for="field in box.form_fields" :key="field.name" class="block text-sm font-medium">{{field.label}}<span v-if="field.required"> *</span><Textarea v-if="field.type==='textarea'" v-model="formData[box.id][field.name]" class="mt-1"/><input v-else-if="field.type==='checkbox'" v-model="formData[box.id][field.name]" type="checkbox" class="ml-2"/><Input v-else v-model="formData[box.id][field.name]" :type="field.type" :required="field.required" class="mt-1"/></label><Button @click="submit(box)">Submit</Button></div>
          <div v-if="box.actions.length" class="mt-5 flex flex-wrap gap-2"><Button v-for="a in box.actions" :key="a.label" as-child><a :href="a.url">{{a.label}}</a></Button></div>
        </section>
      </div>
      <section v-else class="fixed left-0 right-0 z-[99] min-h-[200px] border bg-background p-6 shadow-xl" :class="box.display_type==='top_banner'?'top-0':'bottom-0'" role="status">
        <div class="mx-auto flex max-w-7xl items-start justify-between gap-6"><div><h2 class="text-xl font-semibold">{{box.title}}</h2><div class="prose prose-sm mt-3 max-w-none dark:prose-invert" v-html="box.content_html"></div><div class="mt-4 flex gap-2"><Button v-for="a in box.actions" :key="a.label" as-child><a :href="a.url">{{a.label}}</a></Button></div></div><Button v-if="box.dismissible" variant="ghost" size="icon" @click="close(box)"><X class="size-5"/></Button></div>
      </section>
    </template>
  </Teleport>
</template>
