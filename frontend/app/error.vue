<template>
  <div class="min-h-screen bg-background-light dark:bg-background-dark flex items-center justify-center px-4">
    <div class="max-w-2xl w-full text-center">
      <!-- Error Code -->
      <div class="mb-8">
        <h1 class="text-9xl md:text-[12rem] font-black text-primary opacity-20">
          {{ error.statusCode }}
        </h1>
      </div>

      <!-- Icon -->
      <div class="mb-6">
        <span class="material-symbols-outlined text-slate-300 dark:text-slate-600" style="font-size: 120px;">
          {{ errorIcon }}
        </span>
      </div>

      <!-- Title -->
      <h2 class="text-3xl md:text-4xl font-black text-primary-light dark:text-primary-dark mb-4">
        {{ errorTitle }}
      </h2>

      <!-- Description -->
      <p class="text-lg text-secondary-light dark:text-secondary-dark mb-8 max-w-md mx-auto">
        {{ errorDescription }}
      </p>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <button
          @click="handleError"
          class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-dark text-white font-bold py-3 px-6 rounded-lg transition-colors"
        >
          <span class="material-symbols-outlined">refresh</span>
          Try Again
        </button>
        <NuxtLink
          to="/"
          class="inline-flex items-center justify-center gap-2 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-primary-light dark:text-primary-dark font-bold py-3 px-6 rounded-lg transition-colors"
        >
          <span class="material-symbols-outlined">home</span>
          Go Home
        </NuxtLink>
      </div>

      <!-- Help Links -->
      <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
        <p class="text-sm text-secondary-light dark:text-secondary-dark mb-4">
          Need help? Try these:
        </p>
        <div class="flex flex-wrap gap-4 justify-center text-sm">
          <NuxtLink to="/tours" class="text-primary hover:underline font-semibold">
            Browse Tours
          </NuxtLink>
          <span class="text-slate-300 dark:text-slate-700">•</span>
          <NuxtLink to="/contact" class="text-primary hover:underline font-semibold">
            Contact Support
          </NuxtLink>
          <span class="text-slate-300 dark:text-slate-700">•</span>
          <NuxtLink to="/about" class="text-primary hover:underline font-semibold">
            About Us
          </NuxtLink>
        </div>
      </div>

      <!-- Error Details (Development Only) -->
      <div v-if="isDev && error.stack" class="mt-8 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-left">
        <details>
          <summary class="cursor-pointer font-bold text-red-900 dark:text-red-100 mb-2">
            Error Details (Dev Mode)
          </summary>
          <pre class="text-xs text-red-700 dark:text-red-300 overflow-auto">{{ error.stack }}</pre>
        </details>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  error: {
    statusCode: number
    statusMessage?: string
    message?: string
    stack?: string
  }
}

const props = defineProps<Props>()

const isDev = import.meta.dev

const errorIcon = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return 'search_off'
    case 403:
      return 'lock'
    case 500:
      return 'error'
    default:
      return 'warning'
  }
})

const errorTitle = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return 'Page Not Found'
    case 403:
      return 'Access Denied'
    case 500:
      return 'Internal Server Error'
    default:
      return 'Something Went Wrong'
  }
})

const errorDescription = computed(() => {
  switch (props.error.statusCode) {
    case 404:
      return "The page you're looking for doesn't exist. It might have been moved or deleted."
    case 403:
      return "You don't have permission to access this page. Please contact support if you believe this is an error."
    case 500:
      return "We're experiencing technical difficulties. Our team has been notified and is working on a fix."
    default:
      return props.error.message || 'An unexpected error occurred. Please try again later.'
  }
})

const handleError = () => clearError({ redirect: '/' })

// SEO
useHead({
  title: `${props.error.statusCode} - ${errorTitle.value}`,
  meta: [
    {
      name: 'robots',
      content: 'noindex, nofollow'
    }
  ]
})
</script>
