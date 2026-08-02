<script setup lang="ts">
// Renderer for useConfirmDialog() — mounted once in app.vue.
const { state, settle } = useConfirmDialog()
const { t } = useI18n()
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="state.open"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
        :aria-label="state.title"
        @keydown.esc="settle(false)"
      >
        <div class="absolute inset-0 bg-black/50" @click="settle(false)"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
          <div class="flex items-start gap-3 mb-4">
            <div
              class="size-10 rounded-full flex items-center justify-center shrink-0"
              :class="state.danger ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-500'"
            >
              <Icon name="material-symbols:warning-outline" class="text-xl" />
            </div>
            <div class="min-w-0">
              <h3 class="text-base font-bold text-slate-800 leading-snug">{{ state.title }}</h3>
              <p v-if="state.description" class="text-sm text-slate-500 mt-1 leading-snug">{{ state.description }}</p>
            </div>
          </div>
          <div class="flex gap-2">
            <button
              class="flex-1 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
              @click="settle(false)"
            >
              {{ state.cancelLabel || t('cancel') }}
            </button>
            <button
              class="flex-1 py-2.5 text-sm font-bold text-white rounded-xl transition-colors"
              :class="state.danger ? 'bg-red-500 hover:bg-red-600' : 'bg-primary hover:bg-primary/90'"
              @click="settle(true)"
            >
              {{ state.confirmLabel || t('confirm') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
