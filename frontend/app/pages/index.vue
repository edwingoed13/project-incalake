<template>
  <NuxtLayout>
    <!-- Loading State -->
    <div v-if="pending" class="min-h-screen flex items-center justify-center bg-white dark:bg-background-dark">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
        <p class="mt-4 text-slate-600 dark:text-slate-400">Cargando experiencias...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error && !tours?.length" class="min-h-screen flex items-center justify-center bg-white dark:bg-background-dark">
      <div class="text-center px-4">
        <span class="material-symbols-outlined text-6xl text-slate-400 mb-4">wifi_off</span>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Conectando con el servidor...</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6">Estamos preparando las mejores experiencias para ti</p>
        <button @click="refresh()" class="px-6 py-3 bg-primary text-white rounded-xl hover:bg-primary-600 transition-colors">
          Reintentar
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="bg-white dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 min-h-screen">
      <main>
      <!-- Premium Hero Section -->
      <section class="relative w-full h-[600px] md:h-[650px] overflow-hidden flex flex-col items-center justify-center p-6 sm:p-12">
        <div class="absolute inset-0 z-0">
          <img
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuC_RYQ7qkkoEaBPmoTKZzaG0YqRCjHegCR7RyERPQkd1TtLTQg9RBjbabWhebnRMrUB20ewsrsBPSVd6DSHmHht2CDGuVapyxM2-QivgVXECSdMWlVIrUHpRWi-kYXNgGWzL5n8LrG0LDy65HR5hOFM_toPA7xM8lnDtR4JFasVk-50uf1v5cmyZfqOvKFkinf3_DBwZiEeJp-2fgM5W72REPm0RxDXSlTGjmg4V1Jfto_VIJ4AUc9TPFiZlRzbS-VIy24MMT2dYVq1"
            class="absolute w-full h-full object-cover scale-105"
            alt="Adventure Hero"
          />
          <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/20 to-black/60"></div>
        </div>

        <div class="relative z-10 text-center max-w-4xl px-4 mb-8 animate-fade-in">
          <h2 class="text-white text-4xl sm:text-5xl md:text-6xl font-black leading-[1.1] tracking-tighter mb-4 drop-shadow-2xl">
            Discover Unique Experiences
          </h2>
        </div>

        <!-- Float Search Bar Section -->
        <div class="relative z-20 w-full max-w-6xl px-6 animate-slide-up">
          <div class="bg-white dark:bg-slate-900 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.3)] rounded-3xl p-3 flex flex-col lg:flex-row items-stretch lg:items-center gap-2 border border-white/10">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
              <div class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-2xl transition-all cursor-pointer group">
                <span class="material-symbols-outlined text-primary text-2xl group-hover:scale-110 transition-transform">location_on</span>
                <div class="flex flex-col">
                  <span class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-0.5">Where are you going?</span>
                  <input class="bg-transparent border-none p-0 focus:ring-0 text-sm font-bold text-slate-800 dark:text-white placeholder:text-slate-400 w-full outline-none" placeholder="Find destinations" type="text"/>
                </div>
              </div>

              <div class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-2xl transition-all cursor-pointer group border-l md:border-l-none border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-primary text-2xl group-hover:scale-110 transition-transform">calendar_month</span>
                <div class="flex flex-col">
                  <span class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-0.5">Select Dates</span>
                  <input class="bg-transparent border-none p-0 focus:ring-0 text-sm font-bold text-slate-800 dark:text-white placeholder:text-slate-400 w-full outline-none" placeholder="Add dates" type="text"/>
                </div>
              </div>

              <div class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-2xl transition-all cursor-pointer group border-l md:border-l-none border-slate-100 dark:border-slate-800">
                <span class="material-symbols-outlined text-primary text-2xl group-hover:scale-110 transition-transform">group</span>
                <div class="flex flex-col">
                  <span class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mb-0.5">Guest Travelers</span>
                  <input class="bg-transparent border-none p-0 focus:ring-0 text-sm font-bold text-slate-800 dark:text-white placeholder:text-slate-400 w-full outline-none" placeholder="Add guests" type="text"/>
                </div>
              </div>
            </div>

            <button class="bg-primary text-white h-14 lg:h-20 lg:px-14 rounded-2xl font-black text-lg hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-3 shadow-xl shadow-primary/30">
              <span class="material-symbols-outlined font-bold">search</span>
              Find Adventures
            </button>
          </div>
        </div>
      </section>

      <!-- Trust Signals -->
      <section class="mt-12 py-4 px-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8">
             <div class="flex items-center gap-3 group">
                <div class="size-10 rounded-xl bg-orange-50 dark:bg-orange-950/20 text-orange-600 flex items-center justify-center group-hover:-translate-y-1 transition-transform">
                   <span class="material-symbols-outlined text-2xl font-bold">cancel</span>
                </div>
                <div>
                   <h5 class="text-sm font-bold text-slate-900 dark:text-white">Free Cancellation</h5>
                   <p class="text-xs text-slate-500 font-medium">Full refund 24h before</p>
                </div>
             </div>

             <div class="flex items-center gap-3 group">
                <div class="size-10 rounded-xl bg-green-50 dark:bg-green-950/20 text-green-600 flex items-center justify-center group-hover:-translate-y-1 transition-transform">
                   <span class="material-symbols-outlined text-2xl font-bold">verified_user</span>
                </div>
                <div>
                   <h5 class="text-sm font-bold text-slate-900 dark:text-white">Certified Guides</h5>
                   <p class="text-xs text-slate-500 font-medium">Experts on every tour</p>
                </div>
             </div>

             <div class="flex items-center gap-3 group">
                <div class="size-10 rounded-xl bg-blue-50 dark:bg-blue-950/20 text-blue-600 flex items-center justify-center group-hover:-translate-y-1 transition-transform">
                   <span class="material-symbols-outlined text-2xl font-bold">security</span>
                </div>
                <div>
                   <h5 class="text-sm font-bold text-slate-900 dark:text-white">Secure Payments</h5>
                   <p class="text-xs text-slate-500 font-medium">Safe & Encrypted checkout</p>
                </div>
             </div>

             <div class="flex items-center gap-3">
                 <div class="flex flex-col items-end">
                    <div class="flex gap-0.5 text-yellow-500">
                       <span class="material-symbols-outlined text-sm font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                       <span class="material-symbols-outlined text-sm font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                       <span class="material-symbols-outlined text-sm font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                       <span class="material-symbols-outlined text-sm font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                       <span class="material-symbols-outlined text-sm font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                    </div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-tighter mt-1">4.9/5 Based on 12k reviews</p>
                 </div>
             </div>
          </div>
        </div>
      </section>

      <!-- Category Explorer Section -->
      <section class="py-16 px-6 overflow-hidden">
         <div class="max-w-7xl mx-auto mb-10 border-l-4 border-primary pl-6">
            <h3 class="text-3xl font-black tracking-tighter text-slate-900 dark:text-white uppercase italic skew-x-[-4deg]">Explore by Category</h3>
         </div>
         
         <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
               <div v-for="cat in categoriesMock" :key="cat.name" class="relative group cursor-pointer h-72 rounded-[2rem] overflow-hidden border border-slate-100 dark:border-slate-800 shadow-xl shadow-black/5 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                  <img :src="cat.image" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 opacity-90 group-hover:opacity-100" />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                  <div class="absolute bottom-6 left-6 right-6">
                     <span class="material-symbols-outlined text-white/80 mb-3 group-hover:text-primary transition-colors text-3xl font-bold">{{ cat.icon }}</span>
                     <h5 class="text-white text-lg font-black tracking-tight uppercase italic">{{ cat.name }}</h5>
                  </div>
                  <!-- Glass glow effect -->
                  <div class="absolute inset-0 bg-white/0 group-hover:bg-white/5 backdrop-blur-0 group-hover:backdrop-blur-[2px] transition-all duration-300 pointer-events-none"></div>
               </div>
            </div>
         </div>
      </section>

      <!-- Featured / Top Recommended Section -->
      <section class="py-24 px-6 bg-slate-50/50 dark:bg-slate-900/30">
        <div class="max-w-7xl mx-auto">
          <div class="flex items-end justify-between mb-12">
            <div>
              <p class="text-primary font-black uppercase tracking-[0.2em] text-[10px] mb-3">Curated Experiences</p>
              <h3 class="text-4xl md:text-5xl font-black tracking-tighter text-slate-900 dark:text-white italic">Top Recommended</h3>
            </div>
            <NuxtLink to="/tours" class="group flex items-center gap-3 bg-white dark:bg-slate-800 px-6 py-3 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-primary/50 transition-all font-bold text-sm">
              Explore All <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">trending_flat</span>
            </NuxtLink>
          </div>

          <div v-if="pending" class="flex flex-col items-center justify-center py-20 gap-4">
            <div class="size-12 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
            <p class="text-sm font-bold text-slate-400 animate-pulse">Hunting for best prices...</p>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Tour Card Premium -->
            <NuxtLink
              v-for="tour in tours.slice(0, 8)"
              :key="tour.id"
              :to="`/tours/${tour.slug}`"
              class="flex flex-col group bg-white dark:bg-slate-800/40 rounded-3xl overflow-hidden border border-slate-100 dark:border-slate-800 hover:shadow-2xl hover:shadow-black/10 hover:-translate-y-2 transition-all duration-500 h-full"
            >
              <div class="relative h-64 w-full overflow-hidden">
                <NuxtImg
                  v-if="tour.featured_image"
                  :src="getImageUrl(tour.featured_image)"
                  :alt="tour.title"
                  format="webp"
                  loading="lazy"
                  class="absolute inset-0 w-full h-full object-cover scale-100 group-hover:scale-110 transition-transform duration-700"
                />
                <div v-else class="absolute inset-0 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                   <span class="material-symbols-outlined text-slate-300 text-5xl">image</span>
                </div>
                
                <div class="absolute top-4 left-4 flex gap-2">
                   <span class="bg-primary px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest text-white shadow-xl shadow-primary/40">Highly Rated</span>
                </div>

                <div class="absolute bottom-4 left-4 right-4 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 flex justify-end">
                    <div class="size-10 bg-white/90 dark:bg-slate-900/90 backdrop-blur rounded-full flex items-center justify-center text-primary shadow-lg border border-white/20">
                       <span class="material-symbols-outlined font-bold">add</span>
                    </div>
                </div>
              </div>

              <div class="p-6 flex flex-col flex-1">
                <div class="flex items-center justify-between mb-3">
                   <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg">
                      <span class="material-symbols-outlined text-xs text-yellow-500 font-bold" style="font-variation-settings: 'FILL' 1">star</span>
                      <span class="text-[11px] font-black text-slate-900 dark:text-white">5.0</span>
                   </div>
                   <div class="flex items-center gap-1.5 text-slate-400 text-xs font-bold">
                      <span class="material-symbols-outlined text-sm">schedule</span>
                      {{ tour.duration_days }}d {{ tour.duration_hours }}h
                   </div>
                </div>

                <h4 class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-4 group-hover:text-primary transition-colors flex-1 italic truncate-2-lines">{{ tour.title }}</h4>

                <div class="flex items-end justify-between pt-5 border-t border-slate-100 dark:border-slate-800">
                  <div class="flex flex-col">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Pricing From</span>
                    <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">${{ tour.min_price }}</p>
                  </div>
                  <button class="size-12 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all shadow-lg hover:scale-110 active:scale-90">
                    <span class="material-symbols-outlined text-2xl font-bold">arrow_forward</span>
                  </button>
                </div>
              </div>
            </NuxtLink>
          </div>
        </div>
      </section>

      <!-- Destination grid "Where to Next?" -->
      <section class="py-24 px-6 max-w-7xl mx-auto">
         <div class="flex items-center justify-between mb-16 px-4">
            <h3 class="text-4xl font-extrabold tracking-tighter text-slate-900 dark:text-white">Where to Next?</h3>
            <div class="flex gap-4">
               <button class="size-12 rounded-full border border-slate-100 dark:border-slate-800 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm"><span class="material-symbols-outlined">west</span></button>
               <button class="size-12 rounded-full border border-slate-100 dark:border-slate-800 flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm"><span class="material-symbols-outlined">east</span></button>
            </div>
         </div>

         <div class="grid grid-cols-2 lg:grid-cols-4 grid-rows-2 h-[800px] gap-6 px-4">
            <!-- Large City Card -->
            <div class="col-span-2 row-span-2 relative group overflow-hidden rounded-[3rem] shadow-2xl shadow-black/5 border border-slate-50 dark:border-slate-800 cursor-pointer">
               <img :src="destinations[0].image" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105" />
               <div class="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
               <div class="absolute bottom-12 left-12 right-12">
                  <span class="inline-block px-4 py-1.5 bg-primary rounded-full text-[10px] font-black uppercase tracking-widest text-white mb-6">Trending</span>
                  <h4 class="text-white text-6xl font-black tracking-tighter italic uppercase drop-shadow-lg mb-2">{{ destinations[0].name }}</h4>
                  <p class="text-white/60 text-lg font-bold tracking-tight">The heart of the Andes and Titicaca lake adventures.</p>
               </div>
            </div>

            <div v-for="dest in destinations.slice(1, 5)" :key="dest.name" class="relative group overflow-hidden rounded-[2rem] shadow-xl shadow-black/5 border border-slate-50 dark:border-slate-800 cursor-pointer h-full">
               <img :src="dest.image" class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105 grayscale-[40%] group-hover:grayscale-0" />
               <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/80 to-transparent opacity-80 group-hover:opacity-100 transition-opacity"></div>
               <div class="absolute bottom-8 left-8">
                  <h5 class="text-white text-3xl font-black tracking-tighter italic uppercase drop-shadow-md group-hover:translate-x-1 transition-transform">{{ dest.name }}</h5>
               </div>
            </div>
         </div>
      </section>

      <!-- Banner / Call to Action Section -->
      <section class="py-24 px-6 overflow-hidden">
         <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 h-[450px]">
             <div class="relative rounded-[3rem] bg-slate-900 overflow-hidden flex flex-col justify-end p-12 group cursor-pointer shadow-2xl shadow-slate-900/20">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAdYeaRiIXN68_Fs3mWq5FGHd45wTJ5T1RthVe3w-NEw23Df_Z4JgbiyGxJiyAnSqspzb0NEZiz-hHQNhqFb2vCOm2JRXLyHaYs1e5b1sO7ZBU43IkaMvJCw2fA3gz_-qlGECOwGMhnHvrz78bLAJBWwTvaWqMkTFsVQUUWE_la5YxcstAC2ZV8HE_eR7IsltRjdlRygFCSYTH5e8awy5lbP_l-5HRj9fVgrSKwC8VQRTPWbAfCt4guLGy_4h5yQ97x6VubHffqFqxZ" 
                     class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-40 transition-opacity duration-500 scale-105" />
                <div class="relative z-10">
                   <h3 class="text-white text-4xl font-black tracking-tight mb-4 italic uppercase">Not sure where to go?</h3>
                   <p class="text-white/60 font-bold mb-8 max-w-sm">Explore our curated collections by interest and traveler profile.</p>
                   <button class="bg-white text-slate-900 font-black px-8 py-3.5 rounded-2xl hover:bg-primary hover:text-white transition-all transform group-hover:translate-x-2">Take the Quiz</button>
                </div>
             </div>

             <div class="relative rounded-[3rem] bg-primary overflow-hidden flex flex-col justify-end p-12 group cursor-pointer shadow-2xl shadow-primary/20">
                <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuASHblJfrkZ1CdNU3mY6HZhu4-5xRbJTrvi07oMGQv-yxfBAipyR-LVsBYza5YnWkFbqEZzKzwlLA39acXr24oLuWIXr9yhe19dFU2n8ZwCovSB9sf6u7IsGW5rT4ec1wOmXspwtL9Ut3QlohM0Wa_0wrnvU0GEi1wNlQNtdT_NEMwxsgWqsc0KjI0Rbfidlx5EjdpQnaNwlUhLR-QTGrwi94xeP9yPWtD_bFJUXFmEFN4tO9Ayfuvw0BGUxFTw6UstoOJMYvwKPfmK" 
                     class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:opacity-40 transition-opacity duration-500 scale-105" />
                <div class="relative z-10">
                   <h3 class="text-white text-4xl font-black tracking-tight mb-4 italic uppercase">Underground Niples</h3>
                   <p class="text-white/60 font-bold mb-8 max-w-sm">Exclusive hidden experiences for the modern nomadic traveler.</p>
                   <button class="bg-white text-slate-900 font-black px-8 py-3.5 rounded-2xl hover:bg-primary hover:text-white transition-all transform group-hover:translate-x-2">Find Secret Tours</button>
                </div>
             </div>
         </div>
      </section>

      <!-- Why Choose Us / Benefits Detailed -->
      <section class="py-32 px-6">
         <div class="max-w-7xl mx-auto flex flex-col items-center">
             <h3 class="text-5xl font-black tracking-tighter text-slate-900 dark:text-white mb-20 text-center italic uppercase underline decoration-primary decoration-8 underline-offset-8">Why Travel With Us?</h3>
             <div class="grid grid-cols-1 md:grid-cols-3 gap-16 w-full">
                <div class="flex flex-col items-center text-center">
                   <div class="size-20 rounded-3xl bg-slate-950 dark:bg-slate-800 text-white flex items-center justify-center mb-10 shadow-2xl shadow-black/20 hover:rotate-6 transition-transform">
                      <span class="material-symbols-outlined text-4xl font-bold">public</span>
                   </div>
                   <h4 class="text-2xl font-black mb-4 uppercase italic">Total Studios</h4>
                   <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed leading-extra">We approach travel research with a deep passion for detail, making your adventure unique.</p>
                </div>

                <div class="flex flex-col items-center text-center">
                   <div class="size-20 rounded-3xl bg-slate-950 dark:bg-slate-800 text-white flex items-center justify-center mb-10 shadow-2xl shadow-black/20 hover:rotate-6 transition-transform">
                      <span class="material-symbols-outlined text-4xl font-bold">star</span>
                   </div>
                   <h4 class="text-2xl font-black mb-4 uppercase italic">Custom Line Experiences</h4>
                   <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed leading-extra">Tailored specifically for your needs, avoiding the crowds and embracing the local soul.</p>
                </div>

                <div class="flex flex-col items-center text-center">
                   <div class="size-20 rounded-3xl bg-slate-950 dark:bg-slate-800 text-white flex items-center justify-center mb-10 shadow-2xl shadow-black/20 hover:rotate-6 transition-transform">
                      <span class="material-symbols-outlined text-4xl font-bold">verified</span>
                   </div>
                   <h4 class="text-2xl font-black mb-4 uppercase italic">The Restless Moderns</h4>
                   <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed leading-extra">Driven by the desire for discovery, we find the gems that others miss entirely.</p>
                </div>
             </div>
         </div>
      </section>
      </main>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const { api } = useApi()
const config = useRuntimeConfig()

// SEO
useHead({
  title: 'Voyager | The Editorial Discoveries',
  meta: [
    { name: 'description', content: 'Explore a curated collection of travel discoveries and unique experiences.' }
  ]
})

// Categories Mock Data based on design
const categoriesMock = [
  { name: 'Adventure', icon: 'landscape', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC_RYQ7qkkoEaBPmoTKZzaG0YqRCjHegCR7RyERPQkd1TtLTQg9RBjbabWhebnRMrUB20ewsrsBPSVd6DSHmHht2CDGuVapyxM2-QivgVXECSdMWlVIrUHpRWi-kYXNgGWzL5n8LrG0LDy65HR5hOFM_toPA7xM8lnDtR4JFasVk-50uf1v5cmyZfqOvKFkinf3_DBwZiEeJp-2fgM5W72REPm0RxDXSlTGjmg4V1Jfto_VIJ4AUc9TPFiZlRzbS-VIy24MMT2dYVq1' },
  { name: 'Culture', icon: 'account_balance', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuASHblJfrkZ1CdNU3mY6HZhu4-5xRbJTrvi07oMGQv-yxfBAipyR-LVsBYza5YnWkFbqEZzKzwlLA39acXr24oLuWIXr9yhe19dFU2n8ZwCovSB9sf6u7IsGW5rT4ec1wOmXspwtL9Ut3QlohM0Wa_0wrnvU0GEi1wNlQNtdT_NEMwxsgWqsc0KjI0Rbfidlx5EjdpQnaNwlUhLR-QTGrwi94xeP9yPWtD_bFJUXFmEFN4tO9Ayfuvw0BGUxFTw6UstoOJMYvwKPfmK' },
  { name: 'Family', icon: 'family_restroom', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCcKHfXoVObCRHpJoNlTkQZP78NvyJJNxW9KmZTmmHmQrXUwnjf1L1_Q73O5w0c7us4mTbJB0TOgthnwCPAHAFMF7jAHZM5LHgPhLpYYO7tmaTXkr1VUsmhxz8cw36LvkOh2MTXyb_hQ2RdWzim69yBS6oVYFXS5oB1oe49uLYBErDiNp8295mfili_uyOVhVgo-wejH5_zcZxELYX2QC7av3-fLKDLAbnd3msPtxvH9zajL-HLQ2cpDWgrDxpKIeOw6ecAmDjNYeeJ' },
  { name: 'Luxury', icon: 'diamond', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHZqYG8TUABh9ZMAQQ5lO3atQtzYhy5CjJ-sGN2QgqVJgi7VwYnuel0OSZF5LPCwoXYqubp7-5qb3ZZcQ-D1DzgsdKeZt_xOeCM9_8VYchhUyGq-aj-jEnAGqYPlz7Xe2d_hPdJEqjmaNQ0DmxpOTylfrTeexG4udTlGEbtgGx9DexGrOYcyUu6D1R-cLNLm6j-HHzYW1esC0qwCPI8CuKGUS2qnWZ-zU9dj4vlX5DTztbI5_Y0wGRQ-lOJwYW5VWLVc9Z_ZB0lyp4' },
  { name: 'Nature', icon: 'eco', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA3U6fNRXZPKmedDAUmJE8--oDg3l3t5FXxfUUSOBCOrWLUa2itieujvYznNEgoxunsvfSSvdddxzhpcnjqbLXXmUg9jdG-lW-9eQCRXqwtPswzzHXXQAuUMSrZPambeh7BNM1vgE-xOG-tyno_Ope9WhgF3uW0XoJfTLF2PrrVtbAelgNu7KtAwqpAkGVcyxoBQ_ZM0qvr6ivbwqkFTJlcP8u9L6zCDrAeyWyVM9fPM9Q0WoSIAiLm2ZwZDa2JRjq_nF_2ar2Xitv2' },
]

// Mock Destinations data for Where to Next?
const destinations = [
  { name: 'Puno', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAdYeaRiIXN68_Fs3mWq5FGHd45wTJ5T1RthVe3w-NEw23Df_Z4JgbiyGxJiyAnSqspzb0NEZiz-hHQNhqFb2vCOm2JRXLyHaYs1e5b1sO7ZBU43IkaMvJCw2fA3gz_-qlGECOwGMhnHvrz78bLAJBWwTvaWqMkTFsVQUUWE_la5YxcstAC2ZV8HE_eR7IsltRjdlRygFCSYTH5e8awy5lbP_l-5HRj9fVgrSKwC8VQRTPWbAfCt4guLGy_4h5yQ97x6VubHffqFqxZ' },
  { name: 'Paris', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuASHblJfrkZ1CdNU3mY6HZhu4-5xRbJTrvi07oMGQv-yxfBAipyR-LVsBYza5YnWkFbqEZzKzwlLA39acXr24oLuWIXr9yhe19dFU2n8ZwCovSB9sf6u7IsGW5rT4ec1wOmXspwtL9Ut3QlohM0Wa_0wrnvU0GEi1wNlQNtdT_NEMwxsgWqsc0KjI0Rbfidlx5EjdpQnaNwlUhLR-QTGrwi94xeP9yPWtD_bFJUXFmEFN4tO9Ayfuvw0BGUxFTw6UstoOJMYvwKPfmK' },
  { name: 'Arequipa', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHZqYG8TUABh9ZMAQQ5lO3atQtzYhy5CjJ-sGN2QgqVJgi7VwYnuel0OSZF5LPCwoXYqubp7-5qb3ZZcQ-D1DzgsdKeZt_xOeCM9_8VYchhUyGq-aj-jEnAGqYPlz7Xe2d_hPdJEqjmaNQ0DmxpOTylfrTeexG4udTlGEbtgGx9DexGrOYcyUu6D1R-cLNLm6j-HHzYW1esC0qwCPI8CuKGUS2qnWZ-zU9dj4vlX5DTztbI5_Y0wGRQ-lOJwYW5VWLVc9Z_ZB0lyp4' },
  { name: 'Lima', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBz3S06pVa__44ov13CRysYW5p3EcOgFkbPIW-RbIFkUlSmtfp4-kwUYFz4WxKz6xHjJ3PublruIfJwTTQlv9JXpES0uEil3IojmbKHe3jkljUeOnJMFsseLTzzL2xtd1HD5jYE-vnDDMv1MjRmLHCHTZmMS_Qul4x7qYkmSlb31xx4nesrxV-kLumRK2MzfeZ5eEDofvUyOAm0qzdElDKCismlnWWGHgk9-id9cik8BmWkeZZHBeXb1qjmHDMQx3tfp6tTXwi_TT-w' },
  { name: 'Ica', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCcKHfXoVObCRHpJoNlTkQZP78NvyJJNxW9KmZTmmHmQrXUwnjf1L1_Q73O5w0c7us4mTbJB0TOgthnwCPAHAFMF7jAHZM5LHgPhLpYYO7tmaTXkr1VUsmhxz8cw36LvkOh2MTXyb_hQ2RdWzim69yBS6oVYFXS5oB1oe49uLYBErDiNp8295mfili_uyOVhVgo-wejH5_zcZxELYX2QC7av3-fLKDLAbnd3msPtxvH9zajL-HLQ2cpDWgrDxpKIeOw6ecAmDjNYeeJ' },
]

// Fetch featured tours with error handling
const { data: response, pending, error, refresh } = await useAsyncData(
  'featured-tours',
  () => api('/tours?active=1&per_page=8')
)

// Map tours from API response
const tours = computed(() => {
  if (response.value && response.value.data) {
     return response.value.data.data || []
  }
  return []
})

const getImageUrl = (path: string) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${config.public.storageBase}/${path}`
}
</script>

<style scoped>
.italic {
  font-style: italic;
}.italic-extra {
    font-weight: 900;
}

.truncate-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slide-up {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fade-in 1s ease-out forwards;
}

.animate-slide-up {
  animation: slide-up 1s ease-out 0.3s forwards;
  opacity: 0;
}
</style>
