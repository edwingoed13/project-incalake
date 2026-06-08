<template>
  <!-- Premium Editorial Footer (dark, separates clearly from content) -->
  <footer class="bg-slate-900 text-slate-300 pt-16 md:pt-24 pb-10 px-5 md:px-6">
    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-10 md:gap-16 mb-12 md:mb-20">
        <!-- Brand & Vision -->
        <div class="md:col-span-4 max-w-sm">
          <NuxtLink :to="localePath('/')" class="flex items-center gap-3 mb-6 group">
            <div class="size-12 bg-white text-slate-900 rounded-2xl flex items-center justify-center shadow-2xl transition-transform group-hover:rotate-12 shrink-0">
               <Icon name="material-symbols:explore-outline" class="font-bold text-3xl" />
            </div>
            <div class="flex flex-col">
              <h2 class="text-2xl font-black tracking-tighter uppercase italic leading-none text-white">Incalake</h2>
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">{{ t('footer_tagline') }}</span>
            </div>
          </NuxtLink>
          <p class="text-slate-400 mb-8 font-medium leading-relaxed text-sm">
            {{ t('footer_description') }}
          </p>
          <div class="flex gap-3">
             <a
               v-for="s in socials"
               :key="s.label"
               :href="s.href"
               target="_blank"
               rel="noopener noreferrer"
               :aria-label="s.label"
               class="size-11 rounded-full border border-slate-700 flex items-center justify-center text-slate-300 hover:text-white hover:bg-primary hover:border-primary transition-all"
             >
                <Icon :name="msIcon(s.icon)" class="text-lg" />
             </a>
          </div>
        </div>

        <!-- Links Grid -->
        <div class="md:col-span-5 grid grid-cols-2 gap-8 md:gap-12">
            <div>
              <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-white mb-5 md:mb-8 italic">{{ t('footer_company') }}</h6>
              <ul class="space-y-3.5">
                 <li v-for="link in companyLinks" :key="link.path">
                    <NuxtLink :to="localePath(link.path)" class="text-sm font-bold text-slate-400 hover:text-white transition-all flex items-center gap-2 group">
                       <span class="size-1 bg-slate-600 group-hover:bg-primary rounded-full transition-all"></span>
                       {{ t(link.key) }}
                    </NuxtLink>
                 </li>
              </ul>
            </div>
            <div>
              <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-white mb-5 md:mb-8 italic">{{ t('footer_explore') }}</h6>
              <ul class="space-y-3.5">
                 <li v-for="link in productLinks" :key="link.path">
                    <NuxtLink :to="localePath(link.path)" class="text-sm font-bold text-slate-400 hover:text-white transition-all flex items-center gap-2 group">
                       <span class="size-1 bg-slate-600 group-hover:bg-primary rounded-full transition-all"></span>
                       {{ t(link.key) }}
                    </NuxtLink>
                 </li>
              </ul>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="md:col-span-3">
           <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-white mb-5 md:mb-6 italic">{{ t('footer_newsletter_title') }}</h6>
           <p class="text-xs font-medium text-slate-400 mb-5 leading-relaxed">{{ t('footer_newsletter_desc') }}</p>
           <form class="space-y-3" @submit.prevent>
              <input
                type="email"
                :placeholder="t('footer_email_placeholder')"
                class="w-full bg-slate-800 border border-slate-700 text-white placeholder:text-slate-500 rounded-2xl py-3.5 px-5 text-sm font-medium outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary transition-all"
              />
              <button class="w-full bg-primary text-white min-h-[48px] rounded-2xl font-black uppercase tracking-widest text-[11px] hover:brightness-110 group transition-all active:scale-95 shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                 {{ t('footer_subscribe') }}
                 <Icon name="material-symbols:trending-flat" class="text-sm group-hover:translate-x-1 transition-transform" />
              </button>
           </form>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-center">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300">© {{ currentYear }} Incalake Tours</p>
        <div class="flex gap-8">
           <NuxtLink :to="localePath('/terms') + '#privacidad'" class="text-[10px] font-black uppercase tracking-widest text-slate-300 hover:text-white transition-colors">{{ t('footer_privacy') }}</NuxtLink>
           <NuxtLink :to="localePath('/terms') + '#terminos'" class="text-[10px] font-black uppercase tracking-widest text-slate-300 hover:text-white transition-colors">{{ t('footer_terms') }}</NuxtLink>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { msIcon } from '~/utils/icons'
const localePath = useLocalePath()
const { t } = useI18n()

// Computed once (stable across SSR/client — avoids calling Date() in template).
const currentYear = new Date().getFullYear()

const companyLinks = [
  { key: 'about', path: '/about' },
  { key: 'contact', path: '/contact' },
]

const productLinks = [
  { key: 'footer_all_tours', path: '/tours' },
  { key: 'footer_saved', path: '/saved' },
]

const socials = [
  { label: 'Facebook', icon: 'public', href: 'https://facebook.com' },
  { label: 'Instagram', icon: 'photo_camera', href: 'https://instagram.com' },
  { label: 'WhatsApp', icon: 'chat', href: 'https://wa.me/' },
]
</script>
