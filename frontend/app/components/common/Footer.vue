<template>
  <!-- Premium Editorial Footer -->
  <footer class="bg-white dark:bg-slate-950 pt-32 pb-12 px-6 border-t border-slate-100 dark:border-slate-900">
    <div class="max-w-7xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-16 mb-24">
        <!-- Brand & Vision -->
        <div class="md:col-span-4 max-w-sm">
          <NuxtLink to="/" class="flex items-center gap-3 mb-8 group">
            <div class="size-12 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl flex items-center justify-center shadow-2xl transition-transform group-hover:rotate-12">
               <span class="material-symbols-outlined font-bold text-3xl">explore</span>
            </div>
            <div class="flex flex-col">
              <h2 class="text-2xl font-black tracking-tighter uppercase italic leading-none">Incalake</h2>
              <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Editorial Voyager</span>
            </div>
          </NuxtLink>
          <p class="text-slate-500 dark:text-slate-400 mb-10 font-medium leading-relaxed">
            Reimaging the way we explore the world. We combine editorial storytelling with exclusive access to provide the most authentic travel experiences in the Andes and beyond.
          </p>
          <div class="flex gap-4">
             <a v-for="i in 3" :key="i" href="#" class="size-10 rounded-full border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary transition-all shadow-sm">
                <span class="material-symbols-outlined text-lg">public</span>
             </a>
          </div>
        </div>

        <!-- Links Grid -->
        <div class="md:col-span-5 grid grid-cols-2 gap-12">
            <div>
              <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-8 italic">Company</h6>
              <ul class="space-y-4">
                 <li v-for="link in companyLinks" :key="link.label">
                    <NuxtLink :to="link.path" class="text-sm font-bold text-slate-500 hover:text-primary transition-all flex items-center gap-2 group">
                       <span class="size-1 bg-slate-200 group-hover:bg-primary rounded-full transition-all"></span>
                       {{ link.label }}
                    </NuxtLink>
                 </li>
              </ul>
            </div>
            <div>
              <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-8 italic">Product</h6>
              <ul class="space-y-4">
                 <li v-for="link in productLinks" :key="link.label">
                    <NuxtLink :to="link.path" class="text-sm font-bold text-slate-500 hover:text-primary transition-all flex items-center gap-2 group">
                       <span class="size-1 bg-slate-200 group-hover:bg-primary rounded-full transition-all"></span>
                       {{ link.label }}
                    </NuxtLink>
                 </li>
              </ul>
            </div>
        </div>

        <!-- Newsletter Section -->
        <div class="md:col-span-3">
           <h6 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-8 italic">Stay Restless</h6>
           <p class="text-xs font-medium text-slate-500 mb-6 leading-relaxed">Join our inner circle for secret destinations and early boarding access to new tours.</p>
           <form class="space-y-3" @submit.prevent>
              <div class="relative group">
                 <input type="email" placeholder="Your email address" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl py-4 px-6 text-sm font-bold outline-none group-focus-within:ring-2 ring-primary/10 transition-all" />
              </div>
              <button class="w-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-primary group transition-all active:scale-95 shadow-xl shadow-black/5">
                 Join Voyager <span class="material-symbols-outlined ml-2 text-xs group-hover:translate-x-1 transition-transform">trending_flat</span>
              </button>
           </form>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="pt-10 border-t border-slate-100 dark:border-slate-900 flex flex-col md:flex-row justify-between items-center gap-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">© {{ new Date().getFullYear() }} Incalake Tours — The Editorial Voyager</p>
        <div class="flex gap-10">
           <NuxtLink to="/terms" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-900 transition-colors">Privacy Policy</NuxtLink>
           <NuxtLink to="/terms" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-900 transition-colors">Terms of Service</NuxtLink>
        </div>
        <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
           <span>Designed by Stitch</span>
           <span class="size-1 bg-primary rounded-full animate-pulse"></span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
const companyLinks = [
  { label: 'About Us', path: '/about' },
  { label: 'The Blog', path: '/blog' },
  { label: 'Careers', path: '/careers' },
  { label: 'Contact', path: '/contact' }
]

const productLinks = [
  { label: 'All Experiences', path: '/tours' },
  { label: 'Destinations', path: '/destinations' },
  { label: 'Help Center', path: '/help' },
  { label: 'Safety', path: '/trust' }
]
</script>
