<template>
  <div class="min-h-screen bg-slate-50 pt-20 md:pt-24 pb-8">
    <div class="max-w-3xl mx-auto px-3 sm:px-4">

      <!-- Error -->
      <div v-if="error" class="bg-white rounded-2xl shadow-sm p-6 text-center mt-4">
        <Icon name="material-symbols:error-outline" class="text-red-400 text-4xl mb-3 block" />
        <h2 class="text-base font-bold text-slate-800 mb-1">{{ t('booking_not_found') }}</h2>
        <p class="text-sm text-slate-500">{{ errorMessage }}</p>
      </div>

      <!-- Loading -->
      <div v-else-if="pending" class="flex justify-center py-20">
        <div class="size-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin"></div>
      </div>

      <!-- Content -->
      <div v-else-if="booking">
        <!-- Success Header -->
        <div class="text-center mb-5 md:mb-8">
          <div class="inline-flex items-center justify-center size-12 md:size-16 bg-green-100 rounded-full mb-2">
            <Icon name="material-symbols:check-circle-outline" class="text-green-600 text-3xl md:text-4xl" />
          </div>
          <h1 class="text-lg md:text-2xl font-black text-slate-800">{{ t('booking_confirmed') }}</h1>
          <p class="text-xs text-slate-500 mt-0.5">{{ t('code') }}: <span class="font-mono font-bold text-primary">{{ booking.booking_code }}</span></p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-between mb-5 md:mb-8 px-2">
          <template v-for="(s, idx) in steps" :key="idx">
            <button
              @click="currentStep = idx"
              class="flex flex-col items-center gap-1 group"
            >
              <div
                class="size-8 md:size-9 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="idx === currentStep
                  ? 'bg-primary text-white shadow-md'
                  : completedSteps.has(idx)
                    ? 'bg-green-100 text-green-700'
                    : 'bg-slate-100 text-slate-400'"
              >
                <Icon name="material-symbols:check" v-if="completedSteps.has(idx)" class="text-sm" />
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <span
                class="text-[10px] font-semibold leading-tight text-center"
                :class="idx === currentStep ? 'text-primary' : completedSteps.has(idx) ? 'text-green-600' : 'text-slate-400'"
              >{{ s.label }}</span>
            </button>
            <!-- Connector line -->
            <div
              v-if="idx < steps.length - 1"
              class="flex-1 h-px mb-4"
              :class="completedSteps.has(idx) ? 'bg-green-300' : 'bg-slate-200'"
            ></div>
          </template>
        </div>

        <!-- Step 0: Booking Summary -->
        <div v-if="currentStep === 0" class="space-y-3">
          <!-- Tour(s) of this purchase — one card per tour, single code -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div v-if="isMultiTour" class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
              <Icon name="material-symbols:confirmation-number-outline" class="text-primary text-base" />
              <span class="text-xs font-bold text-slate-700">{{ purchaseTours.length }} tours en esta compra</span>
            </div>

            <div
              v-for="(tr, i) in purchaseTours"
              :key="tr.booking_code + i"
              :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : 'border-b border-slate-50'"
            >
              <div class="flex items-center gap-3 p-3 md:p-4">
                <div class="size-14 md:size-16 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 flex items-center justify-center">
                  <img
                    v-if="tr.tour_image"
                    :src="getImageUrl(tr.tour_image)"
                    :alt="tr.tour_title"
                    class="w-full h-full object-cover"
                  />
                  <Icon name="material-symbols:image-outline" v-else class="text-slate-300 text-2xl" />
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm font-bold text-slate-800 leading-tight line-clamp-2">{{ tr.tour_title }}</h3>
                  <p class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="inline-flex items-center gap-1">
                      <Icon name="material-symbols:calendar-today-outline" class="text-xs" />
                      {{ formatDate(tr.tour_date) }}
                    </span>
                    <span v-if="formatTime(tr.tour_time)" class="inline-flex items-center gap-1">
                      <Icon name="material-symbols:schedule-outline" class="text-xs" />
                      {{ formatTime(tr.tour_time) }}
                    </span>
                    <span class="inline-flex items-center gap-1">
                      <Icon name="material-symbols:group-outline" class="text-xs" />
                      {{ (tr.adults || 0) + (tr.children || 0) }}
                    </span>
                  </p>
                </div>
                <div class="text-right shrink-0">
                  <p class="text-sm font-bold text-primary">{{ currencyStore.formatConverted(tr.total || 0) }}</p>
                </div>
              </div>

              <!-- What's included for THIS tour (grouped with it) -->
              <details
                v-if="tourIncludesList(tr.what_includes).length || tourExcludesList(tr.what_includes, tr.what_not_includes).length"
                class="group border-t border-dashed border-slate-100"
              >
                <summary class="flex items-center justify-between px-3 md:px-4 py-2 cursor-pointer list-none">
                  <span class="text-xs font-semibold text-primary inline-flex items-center gap-1.5">
                    <Icon name="material-symbols:fact-check-outline" class="text-sm" />
                    Qué incluye
                  </span>
                  <Icon name="material-symbols:expand-more" class="text-slate-400 text-base transition-transform group-open:rotate-180" />
                </summary>
                <div class="px-3 md:px-4 pb-3 space-y-2.5">
                  <div v-if="tourIncludesList(tr.what_includes).length">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-green-600 mb-1">Incluye</p>
                    <ul class="space-y-1">
                      <li v-for="(it, k) in tourIncludesList(tr.what_includes)" :key="'wi' + k" class="flex items-start gap-2 text-[13px] text-slate-700">
                        <Icon name="material-symbols:check-circle-outline" class="text-green-500 text-sm shrink-0 mt-0.5" />
                        <span>{{ it }}</span>
                      </li>
                    </ul>
                  </div>
                  <div v-if="tourExcludesList(tr.what_includes, tr.what_not_includes).length">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-500 mb-1">No incluye</p>
                    <ul class="space-y-1">
                      <li v-for="(it, k) in tourExcludesList(tr.what_includes, tr.what_not_includes)" :key="'wni' + k" class="flex items-start gap-2 text-[13px] text-slate-500">
                        <Icon name="material-symbols:cancel-outline" class="text-red-400 text-sm shrink-0 mt-0.5" />
                        <span>{{ it }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </details>
            </div>

            <!-- Purchase totals -->
            <div class="grid grid-cols-3 divide-x divide-slate-100">
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ isMultiTour ? 'Tours' : t('travelers') }}</p>
                <p class="text-sm font-bold text-slate-800 mt-0.5">{{ isMultiTour ? purchaseTours.length : (booking.participants?.adults || 0) }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">{{ t('total') }}</p>
                <p class="text-sm font-bold text-primary mt-0.5">{{ currencyStore.formatConverted(grandTotal) }}</p>
              </div>
              <div class="p-3 text-center">
                <p class="text-[10px] text-slate-400 font-semibold uppercase">Estado</p>
                <p class="text-sm font-bold mt-0.5" :class="paymentSummary?.is_partial ? 'text-amber-600' : 'text-green-600'">
                  {{ paymentSummary?.is_partial ? 'Adelanto pagado' : t('status_paid') }}
                </p>
              </div>
            </div>

            <!-- Partial payment breakdown -->
            <div v-if="paymentSummary?.is_partial" class="border-t border-slate-100 px-3 py-2.5 bg-amber-50/60 flex items-center justify-between gap-2 flex-wrap">
              <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                <Icon name="material-symbols:payments-outline" class="text-amber-600 text-sm" />
                Pagaste <span class="font-bold text-slate-800">{{ currencyStore.formatConverted(paymentSummary.paid_now) }}</span>
              </span>
              <span class="text-xs text-right">
                <span class="text-slate-500">Saldo el día del tour:</span>
                <span class="font-bold text-amber-700 ml-1">{{ currencyStore.formatConverted(paymentSummary.balance_due) }}</span>
              </span>
            </div>
          </div>

          <!-- Customer info (collapsible on mobile) -->
          <details class="bg-white rounded-xl border border-slate-100 shadow-sm group">
            <summary class="flex items-center justify-between p-3 md:p-4 cursor-pointer list-none">
              <div class="flex items-center gap-2">
                <Icon name="material-symbols:person-outline" class="text-primary text-lg" />
                <span class="text-sm font-bold text-slate-800">{{ t('customer_info') }}</span>
              </div>
              <Icon name="material-symbols:expand-more" class="text-slate-400 text-lg transition-transform group-open:rotate-180" />
            </summary>
            <div class="px-3 md:px-4 pb-3 md:pb-4 space-y-2 text-sm border-t border-slate-50 pt-3">
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('name') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">{{ t('email') }}</span>
                <span class="font-semibold text-slate-800 text-xs break-all">{{ booking.customer?.email }}</span>
              </div>
              <div v-if="booking.customer?.phone" class="flex justify-between">
                <span class="text-slate-500">{{ t('phone') }}</span>
                <span class="font-semibold text-slate-800">{{ booking.customer?.phone }}</span>
              </div>
            </div>
          </details>

          <!-- Voucher: prints / saves the confirmation as PDF (works on mobile
               via the browser's print dialog; no backend PDF needed). -->
          <button @click="downloadVoucher" class="w-full flex items-center justify-center gap-2 p-3 bg-white border border-slate-200 rounded-xl text-slate-700 font-semibold text-sm active:bg-slate-50 transition-colors">
            <Icon name="material-symbols:download" class="text-lg" />
            {{ t('voucher') }}
          </button>

          <button @click="currentStep = 1" class="w-full bg-primary active:bg-primary/80 text-white py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 active:scale-[0.98]">
            {{ t('continue_pickup') }}
            <Icon name="material-symbols:arrow-forward" class="text-lg" />
          </button>
        </div>

        <!-- Step 1: Pickup Configuration -->
        <div v-else-if="currentStep === 1">
          <!-- MULTI-TOUR: one pickup per tour (list + modal) -->
          <template v-if="isMultiTour">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
              <div class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
                <Icon name="material-symbols:directions-bus-outline" class="text-primary text-base" />
                <span class="text-xs font-bold text-slate-700">Punto de recojo por tour ({{ configuredCount }}/{{ purchaseTours.length }})</span>
              </div>
              <div
                v-for="(tr, i) in purchaseTours"
                :key="tr.booking_code + i"
                class="flex items-center gap-3 p-3 md:p-4"
                :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : ''"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate">{{ tr.tour_title }}</p>
                  <p class="text-[11px] text-slate-500 mt-0.5">
                    {{ formatDate(tr.tour_date) }}<span v-if="formatTime(tr.tour_time)"> · {{ formatTime(tr.tour_time) }}</span>
                  </p>
                  <p v-if="isTourPickupDone(tr)" class="text-[11px] text-green-600 font-semibold mt-0.5 inline-flex items-center gap-1">
                    <Icon name="material-symbols:check-circle-outline" class="text-xs" />
                    {{ tr.pickup_type === 'hotel_pickup' ? `Recojo en hotel${tr.pickup_hotel ? ': ' + tr.pickup_hotel : ''}` : 'Punto de encuentro confirmado' }}
                  </p>
                  <p v-else class="text-[11px] text-amber-600 font-semibold mt-0.5">Pendiente de configurar</p>
                </div>
                <button
                  @click="openPickupModal(tr)"
                  class="shrink-0 px-3 py-2 rounded-lg text-xs font-bold transition-colors"
                  :class="isTourPickupDone(tr) ? 'bg-slate-100 text-slate-600 active:bg-slate-200' : 'bg-primary text-white active:bg-primary/80'"
                >
                  {{ isTourPickupDone(tr) ? 'Editar' : 'Configurar' }}
                </button>
              </div>
            </div>

            <div class="flex gap-2 mt-3">
              <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
              <button
                @click="onPickupCompleted({})"
                :disabled="configuredCount < purchaseTours.length"
                class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 active:bg-primary/80 transition-colors"
              >
                {{ configuredCount < purchaseTours.length ? `Faltan ${purchaseTours.length - configuredCount}` : t('save_continue') }}
              </button>
            </div>

            <!-- Per-tour pickup modal -->
            <Teleport to="body">
              <div v-if="activePickupTour" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
                <div class="absolute inset-0 bg-black/50" @click="activePickupTour = null"></div>
                <div class="relative bg-slate-50 w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
                  <div class="sticky top-0 bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between z-10">
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-slate-800 truncate">{{ activePickupTour.tour_title }}</p>
                      <p class="text-[11px] text-slate-500">{{ formatDate(activePickupTour.tour_date) }}</p>
                    </div>
                    <button @click="activePickupTour = null" class="p-1.5 text-slate-400 active:text-slate-700">
                      <Icon name="material-symbols:close" class="text-2xl" />
                    </button>
                  </div>
                  <div class="p-4">
                    <BookingPickupConfiguration
                      :key="activePickupTour.id"
                      :booking-id="activePickupTour.id"
                      @completed="onTourPickupSaved"
                      @error="(msg: string) => console.error(msg)"
                    />
                  </div>
                </div>
              </div>
            </Teleport>
          </template>

          <!-- SINGLE TOUR: unchanged inline flow -->
          <template v-else>
            <BookingPickupConfiguration
              :booking-id="booking.id"
              @completed="onPickupCompleted"
              @error="(msg: string) => console.error(msg)"
            />
            <div class="flex gap-2 mt-3">
              <button @click="currentStep = 0" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
              <button @click="skipStep(1)" class="flex-1 py-2.5 bg-slate-100 rounded-xl text-sm font-semibold text-slate-500 active:bg-slate-200">{{ t('skip_for_now') }}</button>
            </div>
          </template>
        </div>

        <!-- Step 2: Traveler Details -->
        <div v-else-if="currentStep === 2">
          <!-- Autosave + deadline notice: data persists as you type and the
               same link lets you come back to finish later. -->
          <div class="mb-3 flex items-start gap-2 p-2.5 rounded-xl bg-blue-50 border border-blue-100">
            <Icon name="material-symbols:cloud-done-outline" class="text-blue-500 text-base mt-0.5 shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-[11px] text-blue-800 leading-snug">
                Tus datos se guardan automáticamente. Puedes cerrar y volver a este enlace para completarlos
                <template v-if="bookingDeadline"> — idealmente antes del <span class="font-semibold">{{ formatDate(bookingDeadline) }}</span></template>.
              </p>
            </div>
            <span class="shrink-0 text-[10px] font-semibold inline-flex items-center gap-1"
              :class="autoSaveState === 'saved' ? 'text-green-600' : autoSaveState === 'saving' ? 'text-slate-400' : 'text-transparent'">
              <Icon :name="autoSaveState === 'saving' ? 'material-symbols:progress-activity' : 'material-symbols:check'"
                :class="autoSaveState === 'saving' ? 'animate-spin' : ''" class="text-xs" />
              {{ autoSaveState === 'saving' ? 'Guardando…' : autoSaveState === 'saved' ? 'Guardado' : '' }}
            </span>
          </div>

          <!-- MULTI-TOUR: travelers per tour (each capped at its own pax) -->
          <template v-if="isMultiTour">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
              <div class="px-3 md:px-4 py-2.5 bg-primary/5 border-b border-slate-50 flex items-center gap-1.5">
                <Icon name="material-symbols:group-outline" class="text-primary text-base" />
                <span class="text-xs font-bold text-slate-700">Viajeros por tour ({{ toursTravelersDone }}/{{ purchaseTours.length }})</span>
              </div>
              <div
                v-for="(tr, i) in purchaseTours"
                :key="tr.id"
                :id="'traveler-tour-' + tr.id"
                class="scroll-mt-24"
                :class="i < purchaseTours.length - 1 ? 'border-b border-slate-100' : ''"
              >
                <button
                  @click="toggleOpenTour(tr.id)"
                  class="w-full flex items-center justify-between gap-2 p-3 md:p-4 text-left"
                >
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ tr.tour_title }}</p>
                    <p class="text-[11px] mt-0.5 font-semibold inline-flex items-center gap-1" :class="isTourComplete(tr) ? 'text-green-600' : 'text-amber-600'">
                      <Icon :name="isTourComplete(tr) ? 'material-symbols:check-circle-outline' : 'material-symbols:group-outline'" class="text-xs" />
                      <template v-if="isTourComplete(tr)">Responsable completo</template>
                      <template v-else>{{ filledCount(tr.id) }}/{{ tourMax(tr) }} viajeros</template>
                    </p>
                  </div>
                  <Icon name="material-symbols:expand-more" :class="isOpenTour(tr.id) ? 'rotate-180' : ''" class="text-slate-400 transition-transform shrink-0 text-2xl" />
                </button>
                <div v-show="isOpenTour(tr.id)" class="px-3 md:px-4 pb-4 border-t border-slate-50 pt-3">
                  <div v-if="purchaseTours.length > 1" class="flex flex-wrap gap-x-4 gap-y-2 mb-3">
                    <button
                      type="button"
                      @click="applyTravelersToAllTours(tr.id)"
                      :disabled="!(travelersByTour[tr.id]?.[0]?.full_name || '').trim()"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-primary active:text-primary/70 disabled:opacity-40 disabled:cursor-not-allowed"
                      title="Sobrescribe los viajeros de los otros tours con los de este"
                    >
                      <Icon name="material-symbols:groups-outline" class="text-sm" />
                      Aplicar estos viajeros a los demás tours
                    </button>
                    <button
                      type="button"
                      @click="applyLeaderToAll(tr.id)"
                      :disabled="!(travelersByTour[tr.id]?.[0]?.full_name || '').trim()"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-primary active:text-primary/70 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                      <Icon name="material-symbols:person-outline" class="text-sm" />
                      Solo el responsable a los demás
                    </button>
                    <button
                      v-if="i > 0"
                      type="button"
                      @click="copyFromPrevious(i)"
                      class="inline-flex items-center gap-1 text-xs font-semibold text-primary active:text-primary/70"
                    >
                      <Icon name="material-symbols:content-copy-outline" class="text-sm" />
                      Copiar viajeros del tour anterior
                    </button>
                  </div>
                  <BookingTravelersForm
                    v-model="travelersByTour[tr.id]"
                    :max-travelers="tourMax(tr)"
                    :customer-name="booking.customer?.name"
                    :customer-email="booking.customer?.email"
                    :customer-phone="booking.customer?.phone"
                    :required-fields="travelerFieldsFor(tr)"
                    :apply-to-all-pax="travelerApplyAll(tr)"
                    :show-errors="showErrors"
                  />
                </div>
              </div>
            </div>
          </template>

          <!-- SINGLE TOUR -->
          <template v-else>
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm">
              <div class="flex items-center justify-between p-3 md:p-4 border-b border-slate-50">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <Icon name="material-symbols:group-outline" class="text-primary text-lg" />
                  {{ t('step_travelers') }}
                  <span class="text-xs font-normal text-slate-400">({{ travelers.length }}/{{ maxTravelers }})</span>
                </h3>
              </div>
              <div class="p-3 md:p-4">
                <BookingTravelersForm
                  v-model="travelers"
                  :max-travelers="maxTravelers"
                  :customer-name="booking.customer?.name"
                  :customer-email="booking.customer?.email"
                  :customer-phone="booking.customer?.phone"
                  :required-fields="travelerFieldsFor(booking.tour)"
                  :apply-to-all-pax="travelerApplyAll(booking.tour)"
                  :show-errors="showErrors"
                />
              </div>
            </div>
          </template>

          <div v-if="travelerError" class="mt-2 bg-red-50 border border-red-200 rounded-xl p-3">
            <p class="text-red-700 text-sm">{{ travelerError }}</p>
          </div>

          <!-- Sticky on mobile so the primary action stays reachable on long
               multi-tour forms; normal flow from sm: up. -->
          <div class="flex gap-2 mt-3 sticky bottom-0 z-30 -mx-3 px-3 py-3 bg-slate-50/95 backdrop-blur border-t border-slate-200 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:static sm:mx-0 sm:px-0 sm:py-0 sm:bg-transparent sm:border-0 sm:pb-0 sm:backdrop-blur-none">
            <button @click="currentStep = 1" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">{{ t('back') }}</button>
            <button @click="saveTravelers" :disabled="savingTravelers" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 flex items-center justify-center gap-2 active:bg-primary/80 active:scale-[0.98] transition-transform">
              <Icon name="material-symbols:progress-activity" v-if="savingTravelers" class="animate-spin text-base" />
              {{ savingTravelers ? t('saving') : t('save_continue') }}
            </button>
          </div>
        </div>

        <!-- Step 3: Complete -->
        <div v-else-if="currentStep === 3" class="text-center">
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 md:p-8">
            <div class="inline-flex items-center justify-center size-14 bg-green-100 rounded-full mb-3">
              <Icon name="material-symbols:celebration-outline" class="text-green-600 text-3xl" />
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">{{ t('all_set') }}</h3>
            <p class="text-xs text-slate-500 mb-5">We'll send you a confirmation email with all the details.<br />See you on {{ formatDate(booking.tour_date) }}!</p>

            <div class="flex flex-col gap-2">
              <a
                :href="`https://wa.me/51982769453?text=Hi! My booking code is ${booking.booking_code}`"
                target="_blank"
                class="flex items-center justify-center gap-2 py-3 bg-green-500 text-white rounded-xl font-bold text-sm active:bg-green-600 transition-colors"
              >
                <Icon name="material-symbols:chat-outline" class="text-lg" />
                {{ t('whatsapp_contact') }}
              </a>
              <NuxtLink to="/" class="flex items-center justify-center gap-2 py-3 bg-slate-100 rounded-xl font-semibold text-sm text-slate-600 active:bg-slate-200 transition-colors">
                <Icon name="material-symbols:home-outline" class="text-lg" />
                {{ t('back_home') }}
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Pre-submit review (final confirm before locking in traveler data) -->
        <Teleport to="body">
          <div v-if="reviewOpen" class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="reviewOpen = false"></div>
            <div class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl max-h-[85vh] overflow-y-auto shadow-2xl">
              <div class="sticky top-0 bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between">
                <p class="text-sm font-bold text-slate-800">Revisa los datos</p>
                <button @click="reviewOpen = false" class="p-1.5 text-slate-400 active:text-slate-700">
                  <Icon name="material-symbols:close" class="text-2xl" />
                </button>
              </div>
              <div class="p-4 space-y-3">
                <div v-for="(g, gi) in reviewGroups" :key="gi" class="rounded-xl border border-slate-100 p-3">
                  <p class="text-xs font-bold text-slate-700 truncate mb-1.5">{{ g.title }}</p>
                  <div class="space-y-1">
                    <p v-for="(p, pi) in g.travelers" :key="pi" class="text-sm text-slate-700 flex items-center gap-1.5">
                      <Icon name="material-symbols:person-outline" class="text-slate-400 text-sm" />
                      {{ p.name }}
                      <span v-if="p.isLeader" class="text-[9px] bg-primary/10 text-primary px-1.5 py-0.5 rounded font-bold">RESPONSABLE</span>
                    </p>
                  </div>
                </div>
                <p class="text-[11px] text-slate-400">Puedes volver a este enlace para editarlos más adelante.</p>
              </div>
              <div class="sticky bottom-0 bg-white border-t border-slate-100 px-4 py-3 flex gap-2 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <button @click="reviewOpen = false" class="flex-1 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 active:bg-slate-50">Editar</button>
                <button @click="commitTravelers" :disabled="savingTravelers" class="flex-1 py-2.5 bg-primary text-white rounded-xl text-sm font-bold disabled:opacity-50 flex items-center justify-center gap-2 active:bg-primary/80">
                  <Icon name="material-symbols:progress-activity" v-if="savingTravelers" class="animate-spin text-base" />
                  {{ savingTravelers ? t('saving') : 'Confirmar' }}
                </button>
              </div>
            </div>
          </div>
        </Teleport>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const { t, locale } = useI18n()
const currencyStore = useCurrencyStore()
const route = useRoute()
const { api } = useApi()
const config = useRuntimeConfig()

const bookingCode = route.params.bookingCode as string
const email = route.query.email as string
const token = route.query.token as string

const currentStep = ref(0)
const completedSteps = ref(new Set<number>())

const steps = computed(() => [
  { label: t('step_summary') },
  { label: t('step_pickup') },
  { label: t('step_travelers') },
  { label: t('step_complete') },
])

// Fetch booking
const { data: response, pending, error } = await useAsyncData(
  `booking-${bookingCode}`,
  () => {
    const lang = (locale.value || 'es').toUpperCase()
    if (token) {
      return api(`/bookings/token/${token}?language=${lang}`)
    }
    const params = email ? `?email=${encodeURIComponent(email)}&language=${lang}` : `?language=${lang}`
    return api(`/bookings/${bookingCode}${params}`)
  }
)

const booking = computed(() => (response.value as any)?.booking || null)
const errorMessage = computed(() => (error.value as any)?.data?.message || 'Invalid or expired link')

// Every tour of the purchase (multi-tour cart paid in one charge). The
// backend resolves the group from the payment record, so this works from the
// email link too — one code in the URL, all tours shown here.
const purchaseTours = computed(() => {
  const grp = (response.value as any)?.group
  if (Array.isArray(grp) && grp.length > 1) return grp
  const b = booking.value
  if (!b) return []
  return [{
    booking_code: b.booking_code,
    tour_title: b.tour?.title || b.tour_title,
    tour_image: b.tour?.featured_image,
    tour_date: b.tour_date,
    tour_time: b.tour_time,
    adults: b.participants?.adults ?? b.adults ?? 0,
    children: b.participants?.children ?? b.children ?? 0,
    currency: b.pricing?.currency || b.currency,
    total: b.pricing?.total ?? b.total ?? 0,
    what_includes: b.tour?.what_includes ?? [],
    what_not_includes: b.tour?.what_not_includes ?? [],
  }]
})
const isMultiTour = computed(() => purchaseTours.value.length > 1)

// Payment summary from the API: how much was charged now vs the balance due
// (for tours paid with a deposit/advance).
const paymentSummary = computed(() => (response.value as any)?.payment_summary || null)
const grandTotal = computed(() =>
  paymentSummary.value?.grand_total ?? purchaseTours.value.reduce((s, x) => s + (x.total || 0), 0)
)

// Per-tour pickup (multi-tour). Seed from backend `pickup_configured` flags;
// add ids as the customer saves each one via the modal.
const configuredPickupIds = ref<Set<number>>(new Set())
const activePickupTour = ref<any | null>(null)

watch(purchaseTours, (tours) => {
  for (const tr of tours) {
    if (tr?.id && tr.pickup_configured) configuredPickupIds.value.add(tr.id)
  }
}, { immediate: true })

const isTourPickupDone = (tr: any) =>
  !!tr && (tr.pickup_configured || configuredPickupIds.value.has(tr.id))

const configuredCount = computed(() =>
  purchaseTours.value.filter((tr: any) => isTourPickupDone(tr)).length
)

function openPickupModal(tr: any) {
  activePickupTour.value = tr
}

function onTourPickupSaved() {
  if (activePickupTour.value?.id) {
    configuredPickupIds.value.add(activePickupTour.value.id)
    // keep the row's status label in sync without a refetch
    activePickupTour.value.pickup_configured = true
  }
  activePickupTour.value = null
}

// Travelers form
const travelers = ref<any[]>([])                       // single-tour purchase
const travelersByTour = ref<Record<number, any[]>>({}) // multi-tour: keyed by booking id
// Tours expandidos en el paso de viajeros. Set de IDs para permitir varios
// abiertos a la vez (antes era single-selection, lo que obligaba a cerrar
// uno para abrir otro y dificultaba comparar/copiar datos entre tours).
const openTourIds = ref<number[]>([])
const isOpenTour = (id: number) => openTourIds.value.includes(id)
function toggleOpenTour(id: number) {
  const i = openTourIds.value.indexOf(id)
  if (i >= 0) openTourIds.value.splice(i, 1)
  else openTourIds.value.push(id)
}
const savingTravelers = ref(false)
const travelerError = ref<string | null>(null)
const showErrors = ref(false)                              // flip on after a failed submit → inline field highlights
const reviewOpen = ref(false)                              // pre-submit review modal
const autoSaveState = ref<'idle' | 'saving' | 'saved'>('idle')

// Deadline shown to the traveler: data should be completed before the (earliest) tour starts.
const bookingDeadline = computed(() => {
  const dates = purchaseTours.value.map((t: any) => t.tour_date).filter(Boolean).sort()
  return dates[0] || booking.value?.tour_date || ''
})

// Cap travelers at the participants paid for (adults + children): a 1-pax tour
// shows only the leader (can't add anyone); a 2-pax tour allows just 1 extra.
const maxTravelers = computed(() => {
  const b: any = booking.value
  const a = b?.participants?.adults ?? b?.adults ?? 0
  const c = b?.participants?.children ?? b?.children ?? 0
  return Math.max(1, a + c)
})

// Per-tour cap + status (multi-tour): each tour is capped at its OWN pax, so a
// purchase mixing a 1-pax tour and a 3-pax tour collects the right travelers.
const tourMax = (tr: any) => Math.max(1, (tr?.adults || 0) + (tr?.children || 0))
const filledCount = (id: number) => (travelersByTour.value[id] || []).filter((x: any) => x.full_name?.trim()).length
const isTourTravelersDone = (tr: any) => filledCount(tr.id) >= 1
const toursTravelersDone = computed(() => purchaseTours.value.filter((tr: any) => isTourTravelersDone(tr)).length)
// "Complete" = the lead traveler has a name AND all admin-required fields filled.
const isTourComplete = (tr: any) => {
  const leader = (travelersByTour.value[tr.id] || [])[0]
  return !!leader?.full_name?.trim() && firstMissingLeaderExtra(leader, tr) === null
}

// Compact summary for the pre-submit review modal.
const reviewGroups = computed(() => {
  if (isMultiTour.value) {
    return purchaseTours.value.map((tr: any) => ({
      title: tr.tour_title,
      travelers: (travelersByTour.value[tr.id] || [])
        .filter((x: any) => x.full_name?.trim())
        .map((x: any, i: number) => ({ name: x.full_name, isLeader: i === 0 })),
    }))
  }
  return [{
    title: booking.value?.tour?.title || booking.value?.tour_title || '',
    travelers: travelers.value
      .filter((x: any) => x.full_name?.trim())
      .map((x: any, i: number) => ({ name: x.full_name, isLeader: i === 0 })),
  }]
})

function seedTravelers(adults: number, children: number, leaderName?: string) {
  const a = adults || 0, c = children || 0
  const total = Math.max(1, a + c)
  return Array.from({ length: total }, (_, i) => ({
    full_name: i === 0 ? (leaderName || '') : '',
    nationality: '', doc_type: 'passport', doc_number: '',
    age_group: i < a ? 'adult' : 'child', special_needs: '', extra_data: {}, is_leader: i === 0,
  }))
}

function mapTraveler(tr: any) {
  return {
    full_name: tr.full_name || '', nationality: tr.nationality || '',
    doc_type: tr.doc_type || 'passport', doc_number: tr.doc_number || '',
    age_group: tr.age_group || 'adult', special_needs: tr.special_needs || '',
    extra_data: (tr.extra_data && typeof tr.extra_data === 'object') ? { ...tr.extra_data } : {},
    is_leader: tr.is_leader || false,
  }
}

// Reuse the travelers of the previous tour (same people on multiple tours),
// trimmed to this tour's capacity.
function copyFromPrevious(i: number) {
  const prev = purchaseTours.value[i - 1]
  const cur = purchaseTours.value[i]
  if (!prev || !cur) return
  const src = travelersByTour.value[prev.id] || []
  const copy = src.slice(0, tourMax(cur)).map((x: any, idx: number) => ({ ...x, is_leader: idx === 0 }))
  if (copy.length) travelersByTour.value[cur.id] = copy
}

// Same lead traveler across the whole purchase: take the leader filled in one
// tour and write it into the first (leader) slot of every other tour, keeping
// each tour's remaining travelers untouched.
function applyLeaderToAll(sourceTourId: number) {
  const src = (travelersByTour.value[sourceTourId] || [])[0]
  if (!src?.full_name?.trim()) return
  for (const tr of purchaseTours.value) {
    if (tr.id === sourceTourId) continue
    const list = travelersByTour.value[tr.id]
    if (!list?.length) continue
    const merged = {
      ...list[0],
      full_name: src.full_name,
      nationality: src.nationality,
      doc_type: src.doc_type,
      doc_number: src.doc_number,
      extra_data: { ...(src.extra_data || {}) },
      is_leader: true,
    }
    travelersByTour.value[tr.id] = [merged, ...list.slice(1)]
  }
}

// Copy EVERY traveler (not just the leader) from the source tour into every
// other tour, slot by slot, trimmed to each tour's own pax cap. Useful when
// the same group is doing multiple tours of the purchase.
function applyTravelersToAllTours(sourceTourId: number) {
  const src = travelersByTour.value[sourceTourId] || []
  if (!src.length || !src[0]?.full_name?.trim()) return
  if (typeof window !== 'undefined' && !window.confirm('¿Copiar todos los viajeros de este tour a los demás? Sobrescribirá los datos ya cargados en los otros.')) return
  for (const tr of purchaseTours.value) {
    if (tr.id === sourceTourId) continue
    const cap = tourMax(tr)
    const copy = src.slice(0, cap).map((x: any, idx: number) => ({
      ...x,
      extra_data: { ...(x.extra_data || {}) },
      is_leader: idx === 0,
    }))
    if (copy.length) travelersByTour.value[tr.id] = copy
  }
}

// Lead-traveler validation: the admin-configured fields are required only for
// the leader (other pax stay optional). Returns the first missing field's
// label, or null when complete.
function firstMissingLeaderExtra(leader: any, configSrc: any): string | null {
  for (const key of travelerFieldsFor(configSrc)) {
    if (!travelerFieldValue(leader, key).trim()) return TRAVELER_FIELD_DEFS[key].label
  }
  return null
}

// Load existing data when booking is available
watch(booking, async (b) => {
  if (!b) return

  // MULTI-TOUR: seed every tour synchronously (so v-model keys exist), then load
  // any already-saved travelers per tour.
  if (isMultiTour.value) {
    for (const tr of purchaseTours.value) {
      travelersByTour.value[tr.id] = seedTravelers(tr.adults, tr.children, b.customer?.name)
    }
    // Open all tours by default in multi-tour: customers tend to fill them
    // in parallel rather than one-by-one, and they can collapse what they
    // don't need.
    openTourIds.value = purchaseTours.value.map((tr: any) => tr.id).filter(Boolean)
    let anySaved = false
    await Promise.all(purchaseTours.value.map(async (tr: any) => {
      try {
        const res: any = await api(`/bookings/${tr.id}/travelers`)
        const existing = res?.data || []
        if (existing.length) {
          travelersByTour.value[tr.id] = existing.map(mapTraveler)
          anySaved = true
        }
      } catch {}
    }))
    if (anySaved) completedSteps.value.add(2)
    return
  }

  // SINGLE TOUR: full details (travelers + pickup)
  try {
    const details = await api(`/bookings/${b.id}/full-details`)
    const data = (details as any)?.data || details

    if (data?.travelers?.length) {
      travelers.value = data.travelers.map(mapTraveler)
      completedSteps.value.add(2)
    } else {
      travelers.value = seedTravelers(b.participants?.adults || 1, b.participants?.children || 0, b.customer?.name)
    }

    if (data?.pickup_detail) completedSteps.value.add(1)
    if (completedSteps.value.has(1) && completedSteps.value.has(2)) currentStep.value = 3
  } catch (e) {
    travelers.value = seedTravelers(b.participants?.adults || 1, b.participants?.children || 0, b.customer?.name)
  }
}, { immediate: true })

useHead({
  title: 'Booking Confirmation',
  meta: [{ name: 'robots', content: 'noindex, nofollow' }]
})

function getImageUrl(path: string) {
  if (!path) return ''
  if (path.startsWith('http')) return path
  // Derive the storage origin from the API base so a missing/wrong
  // NUXT_PUBLIC_STORAGE_BASE (which defaults to localhost) can't break images.
  const origin = String(config.public.apiBase || '').replace(/\/api\/?$/, '')
  const clean = String(path).replace(/^\/+/, '').replace(/^storage\//, '')
  return `${origin}/storage/${clean}`
}

function formatDate(dateString: string) {
  if (!dateString) return ''
  // Tolerate "YYYY-MM-DD", "YYYY-MM-DD HH:mm:ss" and ISO "....THH:mm:ssZ".
  const datePart = String(dateString).split('T')[0].split(' ')[0]
  const [y, m, d] = datePart.split('-').map(Number)
  if (!y || !m || !d) return ''
  const dt = new Date(y, m - 1, d)
  if (isNaN(dt.getTime())) return ''
  const lang = (locale?.value as string) || 'es'
  return dt.toLocaleDateString(lang, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
}

function formatTime(t?: string) {
  if (!t) return ''
  const [hh, mm] = String(t).split(':')
  const h = parseInt(hh, 10)
  if (isNaN(h)) return ''
  const ampm = h >= 12 ? 'PM' : 'AM'
  return `${h % 12 || 12}:${mm ?? '00'} ${ampm}`
}

function onPickupCompleted(data: any) {
  completedSteps.value.add(1)
  currentStep.value = 2
}

function skipStep(step: number) {
  currentStep.value = step + 1
}

// Open + scroll to the tour that failed validation, and turn on inline errors.
function failTour(id: number) {
  showErrors.value = true
  // Ensure the failed tour is open (don't close others if they were already).
  if (!openTourIds.value.includes(id)) openTourIds.value.push(id)
  if (import.meta.client) {
    nextTick(() => document.getElementById('traveler-tour-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
  }
}

// Lead-traveler-only validation. On failure sets the message + inline highlights
// and returns false; returns true when every required field is complete.
function validateTravelers(): boolean {
  travelerError.value = null

  if (isMultiTour.value) {
    for (const tr of purchaseTours.value) {
      const list = travelersByTour.value[tr.id] || []
      if (!list[0]?.full_name?.trim()) {
        travelerError.value = `Falta el responsable en "${tr.tour_title}"`
        failTour(tr.id)
        return false
      }
      const missing = firstMissingLeaderExtra(list[0], tr)
      if (missing) {
        travelerError.value = `Completa "${missing}" del responsable en "${tr.tour_title}"`
        failTour(tr.id)
        return false
      }
    }
    return true
  }

  // SINGLE TOUR
  if (!travelers.value[0]?.full_name?.trim()) {
    travelerError.value = t('leader_required')
    showErrors.value = true
    return false
  }
  const missingSingle = firstMissingLeaderExtra(travelers.value[0], booking.value?.tour)
  if (missingSingle) {
    travelerError.value = `Completa "${missingSingle}" del responsable`
    showErrors.value = true
    return false
  }
  if (travelers.value.filter(tr => tr.full_name?.trim()).length === 0) {
    travelerError.value = t('traveler_required')
    showErrors.value = true
    return false
  }
  return true
}

// Button handler: validate, then show the review modal as a final confirm.
function saveTravelers() {
  if (validateTravelers()) reviewOpen.value = true
}

// Persist all travelers and advance to the final step (assumes validation passed).
async function commitTravelers() {
  clearTimeout(autoSaveTimer)
  reviewOpen.value = false
  savingTravelers.value = true
  try {
    const ids: number[] = []
    if (isMultiTour.value) {
      for (const tr of purchaseTours.value) {
        const valid = (travelersByTour.value[tr.id] || []).filter((x: any) => x.full_name?.trim())
        await api(`/bookings/${tr.id}/travelers`, { method: 'POST', body: { travelers: valid } })
        if (tr.id) ids.push(tr.id)
      }
    } else {
      const valid = travelers.value.filter(tr => tr.full_name?.trim())
      await api(`/bookings/${booking.value.id}/travelers`, { method: 'POST', body: { travelers: valid } })
      if (booking.value?.id) ids.push(booking.value.id)
    }
    completedSteps.value.add(2)
    currentStep.value = 3

    // Operator notification (fire-and-forget — backend dedupes via cache so
    // a retry or accidental double-click won't spam reservations@). We don't
    // await it: the customer continues to step 3 even if the email fails.
    for (const id of ids) {
      api(`/bookings/${id}/notify-completed`, { method: 'POST' }).catch(() => {})
    }
  } catch (e: any) {
    travelerError.value = t('error_saving')
  } finally {
    savingTravelers.value = false
  }
}

// --- Autosave (silent) -----------------------------------------------------
// Persist progress as the user types so nothing is lost if they leave and
// return via the same link. Reuses the travelers endpoint (delete+recreate is
// idempotent); only saves tours whose lead traveler already has a name, never
// validates and never advances the step.
let autoSaveTimer: any = null
async function runAutoSave() {
  try {
    let savedAny = false
    if (isMultiTour.value) {
      for (const tr of purchaseTours.value) {
        const list = travelersByTour.value[tr.id] || []
        if (!list[0]?.full_name?.trim()) continue
        await api(`/bookings/${tr.id}/travelers`, { method: 'POST', body: { travelers: list.filter((x: any) => x.full_name?.trim()) } })
        savedAny = true
      }
    } else if (travelers.value[0]?.full_name?.trim()) {
      await api(`/bookings/${booking.value.id}/travelers`, { method: 'POST', body: { travelers: travelers.value.filter(x => x.full_name?.trim()) } })
      savedAny = true
    }
    autoSaveState.value = savedAny ? 'saved' : 'idle'
  } catch {
    autoSaveState.value = 'idle'
  }
}
function scheduleAutoSave() {
  if (currentStep.value !== 2) return
  travelerError.value = null            // user is editing → dismiss any stale error
  autoSaveState.value = 'saving'
  clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(runAutoSave, 1200)
}
watch([travelers, travelersByTour], scheduleAutoSave, { deep: true })

// Voucher = the printable confirmation page. window.print() lets the user
// save it as PDF / print, and works on mobile (no backend PDF endpoint needed).
function downloadVoucher() {
  if (import.meta.client) window.print()
}
</script>

<style>
@media print { nav, footer, button { display: none !important; } }
</style>
