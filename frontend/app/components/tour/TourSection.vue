<template>
  <section class="bg-white rounded-2xl shadow-sm p-4 sm:p-6 md:p-8">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-2 text-left"
      :aria-expanded="open"
      :aria-controls="panelId"
      @click="open = !open"
    >
      <h2 class="text-xl md:text-2xl font-bold flex items-center gap-2" :class="titleClass">
        <slot name="icon" />
        {{ title }}
      </h2>
      <Icon
        name="material-symbols:expand-more"
        class="size-6 text-slate-400 transition-transform shrink-0"
        :class="{ '-rotate-180': open }"
        aria-hidden="true"
      />
    </button>

    <!--
      v-show, never v-if. The body has to stay in the server-rendered HTML even
      while collapsed: the tour pages are read by search engines and by AI
      crawlers that do not execute JavaScript, so a section that only exists
      after a click is a section they never see. Hiding it with CSS keeps the
      text in the document and still shortens the page for a human.
    -->
    <div v-show="open" :id="panelId" class="mt-5 md:mt-6">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, useId } from 'vue'

/**
 * The collapsible shell every content section on the tour page shares.
 *
 * These sections had drifted apart: "Información Importante" and the policies
 * folded, the description, itinerary, what's-included and the operator's own
 * custom sections did not. On a tour with a long FAQ the page turned into a
 * wall of text with no way to skim it, and two sections sitting side by side
 * behaved differently for no reason a reader could infer.
 */
const props = withDefaults(defineProps<{
  title: string
  /** Some sections use the lighter heading colour; keep that choice theirs. */
  titleClass?: string
  /**
   * Primary content (description, itinerary, what's included) opens by
   * default — folding it away would leave a visitor deciding on a $365 tour
   * looking at an empty page. Secondary and operator-authored sections start
   * closed, which is what "Información Importante" already did.
   */
  defaultOpen?: boolean
}>(), {
  titleClass: 'text-slate-800',
  defaultOpen: false,
})

const open = ref(props.defaultOpen)
const panelId = `tour-section-${useId()}`
</script>
