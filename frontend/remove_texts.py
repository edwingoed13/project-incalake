with open('pages/tours/[slug].vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Eliminar "Free cancellation up to 24h before"
remove1 = '''
              <p class="text-center text-xs text-slate-500 mb-4 flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm">verified_user</span>
                Free cancellation up to 24h before
              </p>'''

content = content.replace(remove1, '')

# Eliminar "Reserve now, pay later" card
remove2 = '''
            <!-- Reserve Now Card -->
            <div class="bg-primary/5 dark:bg-primary/10 rounded-xl p-4 border border-primary/20 mt-4">
              <div class="flex gap-3">
                <span class="material-symbols-outlined text-primary text-2xl">electric_bolt</span>
                <div>
                  <p class="text-sm font-bold text-primary mb-1">Reserve now, pay later</p>
                  <p class="text-xs text-slate-600 dark:text-slate-400">Book your spot today and pay nothing until closer to your trip.</p>
                </div>
              </div>
            </div>'''

content = content.replace(remove2, '')

# Eliminar Trust Signals duplicados de slug.vue (ya están en BookingWidget)
remove3 = '''
              <!-- Trust Signals -->
              <div class="space-y-2 py-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                  <span class="material-symbols-outlined text-green-500 text-base">shield</span>
                  <span>Secure booking</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                  <span class="material-symbols-outlined text-yellow-500 text-base">bolt</span>
                  <span>Instant confirmation</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                  <span class="material-symbols-outlined text-blue-500 text-base">support_agent</span>
                  <span>24/7 support</span>
                </div>
              </div>'''

content = content.replace(remove3, '')

with open('pages/tours/[slug].vue', 'w', encoding='utf-8') as f:
    f.write(content)

print("Textos eliminados correctamente de [slug].vue")
