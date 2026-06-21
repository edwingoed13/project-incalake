<template>
  <div class="flex flex-col gap-6">
    <!-- Opciones de reserva · secciones colapsables -->
    <!-- Language selector -->
    <UCard :ui="{ body: 'p-3 sm:p-3' }">
      <div class="flex items-center gap-3">
        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center">
          <UIcon name="i-lucide-languages" class="size-4 text-primary" />
        </div>
        <div class="flex-1">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Editando opciones de reserva en</p>
          <div class="flex items-center gap-1 mt-1">
            <UButton
              v-for="lang in tourLanguages"
              :key="lang"
              size="xs"
              :color="store.currentLanguage === lang ? 'primary' : 'neutral'"
              :variant="store.currentLanguage === lang ? 'solid' : 'subtle'"
              class="uppercase font-black tracking-wider"
              @click="store.currentLanguage = lang"
            >
              {{ lang }}
            </UButton>
          </div>
        </div>
      </div>
    </UCard>

    <!-- 1. Políticas y Cancelaciones -->
    <WizardSection
      collapsible
      title="Políticas y cancelaciones"
      icon="i-lucide-shield-check"
      :open="isSectionExpanded('policies')"
      @update:open="toggleSection('policies')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs" class="capitalize">{{ store.bookingOptions.policyType || 'standard' }}</UBadge>
      </template>

      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <button
            v-for="type in policyTypes"
            :key="type.id"
            type="button"
            :class="[
              'p-4 rounded-xl border-2 text-left transition-all flex items-center gap-3',
              store.bookingOptions.policyType === type.id
                ? 'border-primary bg-primary/5 shadow-md shadow-primary/10'
                : 'border-default hover:border-muted',
            ]"
            @click="store.bookingOptions.policyType = type.id"
          >
            <div :class="['size-5 rounded-full border-2 flex items-center justify-center shrink-0', store.bookingOptions.policyType === type.id ? 'border-primary bg-primary' : 'border-default']">
              <div v-if="store.bookingOptions.policyType === type.id" class="size-2 bg-white rounded-full" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold" :class="store.bookingOptions.policyType === type.id ? 'text-primary' : ''">{{ type.name }}</span>
              <span class="text-[11px] text-muted">{{ type.description }}</span>
            </div>
          </button>
        </div>

        <UFormField
          :label="store.bookingOptions.policyType === 'standard' ? 'Políticas pre-establecidas (editables)' : 'Descripción personalizada'"
        >
          <div class="rounded-lg overflow-hidden">
            <TiptapEditor
              v-if="store.bookingOptions.policyType === 'standard'"
              :modelValue="currentBookingTexts.policyDescription || ''"
              placeholder="Escribe las políticas estándar aquí..."
              :key="'policy-std-' + store.currentLanguage"
              @update:modelValue="(v: string) => { const seo = store.contentSEO[store.currentLanguage]; if (seo?.bookingTexts) seo.bookingTexts.policyDescription = v; store.bookingOptions.policyDescription = v }"
            />
            <TiptapEditor
              v-else
              :modelValue="currentBookingTexts.policyDescriptionCustom || ''"
              placeholder="Escribe las políticas personalizadas para esta actividad..."
              :key="'policy-custom-' + store.currentLanguage"
              @update:modelValue="(v: string) => { const seo = store.contentSEO[store.currentLanguage]; if (seo?.bookingTexts) seo.bookingTexts.policyDescriptionCustom = v; store.bookingOptions.policyDescriptionCustom = v }"
            />
          </div>
        </UFormField>

        <UAlert
          v-if="store.bookingOptions.policyType === 'standard'"
          color="info"
          variant="subtle"
          icon="i-lucide-info"
          description="Estas son las políticas estándar de Inca Lake. Puedes modificarlas si esta actividad lo requiere."
        />
      </div>
    </WizardSection>

    <!-- 2. Tiempo de Anticipación -->
    <WizardSection
      collapsible
      title="Tiempo de anticipación"
      icon="i-lucide-clock"
      :open="isSectionExpanded('anticipation')"
      @update:open="toggleSection('anticipation')"
    >
      <template #actions>
        <UBadge color="warning" variant="subtle" size="xs">{{ anticipationSummary }}</UBadge>
      </template>

      <div class="space-y-4">
        <div class="grid grid-cols-3 gap-3">
          <UFormField label="Días" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationDays" :min="0" :max="30" class="w-full" />
          </UFormField>
          <UFormField label="Horas" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationHours" :min="0" :max="23" class="w-full" />
          </UFormField>
          <UFormField label="Minutos" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
            <UInputNumber v-model="anticipationMinutes" :min="0" :max="59" :step="1" class="w-full" />
          </UFormField>
        </div>

        <UAlert
          color="warning"
          variant="subtle"
          icon="i-lucide-lightbulb"
          :title="`Anticipación: ${anticipationSummary}`"
          description="Combina días, horas y minutos. Ejemplo: 2 horas 30 minutos significa que los clientes deben reservar al menos 2h 30m antes del inicio del tour."
        />
      </div>
    </WizardSection>

    <!-- 3 & 4. Datos Requeridos -->
    <WizardSection
      collapsible
      title="Datos requeridos del cliente"
      icon="i-lucide-user-plus"
      :open="isSectionExpanded('data')"
      @update:open="toggleSection('data')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs">
          {{ store.bookingOptions.dataRequirementType === 'all' ? 'Todos' : 'Solo líder' }} · {{ (store.bookingOptions.personalInfoRequired?.length || 0) + (store.bookingOptions.operationalInfoRequired?.length || 0) }} campos
        </UBadge>
      </template>

      <div class="space-y-4">
      <div class="flex bg-elevated rounded-lg p-1 border border-default w-fit">
        <button
          type="button"
          :class="[
            'px-4 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-all',
            store.bookingOptions.dataRequirementType === 'leader' ? 'bg-default text-primary shadow-sm' : 'text-muted',
          ]"
          @click="store.bookingOptions.dataRequirementType = 'leader'; store.isDirty = true"
        >
          Solo líder
        </button>
        <button
          type="button"
          :class="[
            'px-4 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-all',
            store.bookingOptions.dataRequirementType === 'all' ? 'bg-default text-primary shadow-sm' : 'text-muted',
          ]"
          @click="store.bookingOptions.dataRequirementType = 'all'; store.isDirty = true"
        >
          Todos los pasajeros
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Personal Info -->
        <div class="space-y-2">
          <div class="flex items-center justify-between pb-1.5 border-b border-default">
            <p class="text-[11px] font-black uppercase tracking-widest text-muted">Información personal</p>
            <span class="text-[9px] text-muted italic">datos básicos</span>
          </div>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="(label, key) in personalFields"
              :key="key"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.personalInfoRequired, key)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.personalInfoRequired, key, v)"
              />
              <span class="text-xs font-medium select-none">{{ label }}</span>
            </label>
          </div>
        </div>

        <!-- Operational Info -->
        <div class="space-y-2">
          <div class="flex items-center justify-between pb-1.5 border-b border-default">
            <p class="text-[11px] font-black uppercase tracking-widest text-muted">Información operacional</p>
            <span class="text-[9px] text-muted italic">datos específicos</span>
          </div>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="(label, key) in operationalFields"
              :key="key"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.operationalInfoRequired, key)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.operationalInfoRequired, key, v)"
              />
              <span class="text-xs font-medium select-none">{{ label }}</span>
            </label>
          </div>
        </div>
      </div>
      </div>
    </WizardSection>

    <!-- 5. Opciones de Recojo -->
    <WizardSection
      collapsible
      title="Opciones de recojo"
      icon="i-lucide-map-pin"
      :open="isSectionExpanded('pickup')"
      @update:open="toggleSection('pickup')"
    >
      <template #actions>
        <UBadge
          :color="store.bookingOptions.enableMeetingPoint || store.bookingOptions.enableHotelPickup ? 'success' : 'error'"
          variant="subtle"
          size="xs"
        >
          {{ [store.bookingOptions.enableMeetingPoint && 'Encuentro', store.bookingOptions.enableHotelPickup && 'Hotel'].filter(Boolean).join(' + ') || 'Sin configurar' }}
        </UBadge>
      </template>

      <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <!-- Meeting Points (multi) -->
        <div
          :class="[
            'rounded-lg border-2 transition-all',
            store.bookingOptions.enableMeetingPoint ? 'border-primary bg-primary/5' : 'border-default',
          ]"
        >
          <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
            <UCheckbox v-model="store.bookingOptions.enableMeetingPoint" color="primary" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold">
                Puntos de encuentro
                <UBadge
                  v-if="store.bookingOptions.meetingPoints.length > 0"
                  color="primary"
                  variant="subtle"
                  size="xs"
                  class="ml-1"
                >
                  {{ store.bookingOptions.meetingPoints.length }}
                </UBadge>
              </p>
              <p class="text-[11px] text-muted">El cliente puede elegir entre uno o varios lugares de encuentro</p>
            </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableMeetingPoint" class="px-3 pb-3 pt-2 border-t border-default space-y-2">
              <!-- Empty state -->
              <div
                v-if="store.bookingOptions.meetingPoints.length === 0"
                class="rounded-lg border-2 border-dashed border-default p-4 text-center"
              >
                <UIcon name="i-lucide-map-pin-off" class="size-6 text-muted mx-auto mb-1.5" />
                <p class="text-xs text-muted mb-2">Aún no hay puntos de encuentro</p>
                <UButton icon="i-lucide-plus" color="primary" size="xs" @click="addMeetingPoint">
                  Agregar primer punto
                </UButton>
              </div>

              <!-- Points list -->
              <div
                v-for="(point, idx) in store.bookingOptions.meetingPoints"
                :key="point.id"
                class="rounded-lg border border-default bg-default p-2.5 space-y-2"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <UBadge color="primary" variant="solid" size="xs" class="font-mono shrink-0">#{{ idx + 1 }}</UBadge>
                    <p v-if="point.lat != null && point.lng != null" class="text-[10px] font-mono text-muted truncate">
                      {{ point.lat.toFixed(5) }}, {{ point.lng.toFixed(5) }}
                    </p>
                    <p v-else class="text-[10px] italic text-muted">Sin coordenadas</p>
                  </div>
                  <div class="flex items-center gap-0.5 shrink-0">
                    <UButton
                      icon="i-lucide-arrow-up"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :disabled="idx === 0"
                      title="Subir"
                      @click="moveMeetingPoint(idx, -1)"
                    />
                    <UButton
                      icon="i-lucide-arrow-down"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                      :disabled="idx === store.bookingOptions.meetingPoints.length - 1"
                      title="Bajar"
                      @click="moveMeetingPoint(idx, 1)"
                    />
                    <UButton
                      icon="i-lucide-trash-2"
                      color="error"
                      variant="ghost"
                      size="xs"
                      title="Eliminar este punto"
                      @click="removeMeetingPoint(idx)"
                    />
                  </div>
                </div>

                <UTextarea
                  :model-value="point.descriptions[store.currentLanguage] || ''"
                  :placeholder="`Descripción en ${store.currentLanguage.toUpperCase()} (ej: Plaza de Armas de Puno)...`"
                  :rows="2"
                  class="w-full"
                  @update:model-value="(v: string) => updatePointDescription(idx, v)"
                />

                <div class="flex items-center gap-2">
                  <UButton
                    icon="i-lucide-map-pin"
                    color="neutral"
                    variant="solid"
                    size="xs"
                    class="flex-1"
                    @click="openMeetingPointModal(idx)"
                  >
                    {{ point.lat != null ? 'Editar en el mapa' : 'Marcar en el mapa' }}
                  </UButton>
                  <UIcon
                    v-if="point.lat != null && point.lng != null"
                    name="i-lucide-circle-check"
                    class="size-4 text-success"
                    :title="`Lat ${point.lat.toFixed(5)}, Lng ${point.lng.toFixed(5)}`"
                  />
                </div>
              </div>

              <!-- Add another -->
              <UButton
                v-if="store.bookingOptions.meetingPoints.length > 0"
                icon="i-lucide-plus"
                color="primary"
                variant="soft"
                size="sm"
                block
                @click="addMeetingPoint"
              >
                Agregar otro punto de encuentro
              </UButton>
            </div>
          </Transition>
        </div>

        <!-- Hotel Pickup -->
        <div
          :class="[
            'rounded-lg border-2 transition-all',
            store.bookingOptions.enableHotelPickup ? 'border-primary bg-primary/5' : 'border-default',
          ]"
        >
          <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer">
            <UCheckbox v-model="store.bookingOptions.enableHotelPickup" color="primary" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-bold">Recojo en hotel</p>
              <p class="text-[11px] text-muted">Recojo en hoteles dentro de un radio</p>
            </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableHotelPickup" class="px-3 pb-3 pt-2 border-t border-default space-y-2">
              <UTextarea
                v-model="currentBookingTexts.pickupLocationDescription"
                placeholder="Ej: Hoteles del centro y alrededores..."
                :rows="2"
                class="w-full"
              />
              <div class="grid grid-cols-[1fr_2fr] gap-2 items-end">
                <UFormField label="Radio (km)" :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }">
                  <UInputNumber v-model="store.bookingOptions.pickupRadiusKm" :min="1" :max="100" class="w-full" />
                </UFormField>
                <UButton
                  icon="i-lucide-target"
                  color="neutral"
                  size="sm"
                  block
                  @click="openPickupModal('hotel_pickup')"
                >
                  Configurar radio
                </UButton>
              </div>
              <UAlert
                v-if="store.bookingOptions.pickupCenterLat && store.bookingOptions.pickupCenterLng"
                color="success"
                variant="subtle"
                icon="i-lucide-circle-check"
                :description="`Radio de ${store.bookingOptions.pickupRadiusKm}km configurado`"
              />
              <UTextarea
                v-model="currentBookingTexts.dropoffLocationDescription"
                placeholder="Punto de retorno (opcional)..."
                :rows="2"
                class="w-full"
              />
            </div>
          </Transition>
        </div>
      </div>

      <UAlert
        v-if="!store.bookingOptions.enableMeetingPoint && !store.bookingOptions.enableHotelPickup"
        color="error"
        variant="subtle"
        icon="i-lucide-triangle-alert"
        title="Alerta de seguridad"
        description="Debes habilitar al menos una opción de recojo para que el tour sea reservable."
      />
      </div>
    </WizardSection>

    <!-- 6. Asociar Guías -->
    <WizardSection
      collapsible
      title="Configuración de guía"
      icon="i-lucide-megaphone"
      :open="isSectionExpanded('guide')"
      @update:open="toggleSection('guide')"
    >
      <template #actions>
        <UBadge color="primary" variant="subtle" size="xs">
          {{ guideTypes.find(g => g.id === store.bookingOptions.guideType)?.name || 'Sin definir' }}
          <template v-if="store.bookingOptions.guideType === 'live_guide' && store.bookingOptions.guideLanguages?.length">
            · {{ store.bookingOptions.guideLanguages.length }} idiomas
          </template>
        </UBadge>
      </template>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="space-y-2">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Tipo de acompañante</p>
          <div class="space-y-1.5">
            <label
              v-for="guide in guideTypes"
              :key="guide.id"
              :class="[
                'flex items-center gap-2.5 px-3 py-2 rounded-lg border-2 transition-all cursor-pointer',
                store.bookingOptions.guideType === guide.id
                  ? 'border-primary bg-primary/5'
                  : 'border-default hover:border-muted',
              ]"
            >
              <input type="radio" v-model="store.bookingOptions.guideType" :value="guide.id" class="hidden" />
              <div :class="['size-4 rounded-full border-2 flex items-center justify-center shrink-0', store.bookingOptions.guideType === guide.id ? 'border-primary' : 'border-default']">
                <div v-if="store.bookingOptions.guideType === guide.id" class="size-2 bg-primary rounded-full" />
              </div>
              <span class="text-sm font-medium" :class="store.bookingOptions.guideType === guide.id ? 'text-primary' : ''">{{ guide.name }}</span>
            </label>
          </div>
        </div>

        <div v-if="store.bookingOptions.guideType === 'live_guide'" class="space-y-2">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">Idiomas disponibles</p>
          <div class="grid grid-cols-2 gap-x-3 gap-y-1.5">
            <label
              v-for="lang in guideLanguages"
              :key="lang.id"
              class="flex items-center gap-2 px-1.5 py-1 rounded hover:bg-elevated/40 transition-colors cursor-pointer"
            >
              <UCheckbox
                :model-value="isInArray(store.bookingOptions.guideLanguages, lang.id)"
                color="primary"
                @update:model-value="(v: boolean) => toggleInArray(store.bookingOptions.guideLanguages, lang.id, v)"
              />
              <span class="text-xs font-medium select-none">{{ lang.name }}</span>
            </label>
          </div>
        </div>
      </div>
    </WizardSection>

    <!-- 6. Opciones de la actividad — variant grouping (Compartido / +Guía / Privado) -->
    <WizardSection
      collapsible
      title="Opciones de la actividad"
      icon="i-lucide-layers"
      :open="isSectionExpanded('variant')"
      @update:open="toggleSection('variant')"
    >
      <template #actions>
        <UBadge :color="variantBadgeColor" variant="subtle" size="xs">
          {{ variantBadgeLabel }}
        </UBadge>
      </template>

      <div class="space-y-4">
        <p class="text-xs text-muted">
          Si la actividad tiene varias modalidades (e.g. <em>Compartido</em>, <em>+Guía Privado</em>, <em>Privado</em>),
          cada una vive como un tour aparte y se agrupan aquí. <strong>El cliente las ve como cards en la página del tour padre.</strong>
        </p>

        <!-- Tipo: 3 modos -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <button
            type="button"
            :class="modeBtnClass('standalone')"
            @click="setMode('standalone')"
          >
            <div :class="modeRadioClass('standalone')">
              <div v-if="variantMode === 'standalone'" class="size-2 bg-white rounded-full" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold" :class="variantMode === 'standalone' ? 'text-primary' : ''">Tour independiente</span>
              <span class="text-[11px] text-muted">Sin variantes. Aparece solo en el listado público.</span>
            </div>
          </button>
          <button
            type="button"
            :class="modeBtnClass('parent')"
            @click="setMode('parent')"
          >
            <div :class="modeRadioClass('parent')">
              <div v-if="variantMode === 'parent'" class="size-2 bg-white rounded-full" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold" :class="variantMode === 'parent' ? 'text-primary' : ''">Opción principal de actividad</span>
              <span class="text-[11px] text-muted">Es el tour "canónico" que ve el público. Otras variantes le apuntan a este.</span>
            </div>
          </button>
          <button
            type="button"
            :class="modeBtnClass('child')"
            @click="setMode('child')"
          >
            <div :class="modeRadioClass('child')">
              <div v-if="variantMode === 'child'" class="size-2 bg-white rounded-full" />
            </div>
            <div class="flex flex-col min-w-0">
              <span class="text-sm font-bold" :class="variantMode === 'child' ? 'text-primary' : ''">Variante de otra actividad</span>
              <span class="text-[11px] text-muted">Se muestra como card dentro del padre. Oculto del listado.</span>
            </div>
          </button>
        </div>

        <!-- Config: padre/variante necesitan etiqueta y color; sólo variante necesita el padre -->
        <div v-if="variantMode !== 'standalone'" class="space-y-4 pt-2 border-t border-default">

          <!-- Padre selector (solo para 'child') -->
          <UFormField v-if="variantMode === 'child'" label="Actividad padre" hint="Busca el tour canónico que agrupa esta variante" required>
            <div ref="parentSearchWrapperEl" class="relative" @keydown.esc="parentDropdownOpen = false">
              <UInput
                v-model="parentSearchQuery"
                placeholder="Escribe parte del nombre del tour…"
                icon="i-lucide-search"
                :loading="parentSearching"
                :ui="{ trailing: 'pe-1' }"
                @focus="onParentInputFocus"
                @input="onParentSearchInput"
              >
                <template v-if="parentDropdownOpen" #trailing>
                  <UButton color="neutral" variant="link" size="xs" icon="i-lucide-x" :padded="false" aria-label="Cerrar lista" @click="parentDropdownOpen = false" />
                </template>
              </UInput>
              <!-- Resultados dropdown. Search-first: only renders once the
                   operator types (>=2 chars) or while a fetch is in flight,
                   so an idle focus shows nothing. max-h-[260px] keeps it
                   short enough to sit under the input without reaching the
                   wizard's sticky footer. -->
              <div
                v-if="parentDropdownOpen && (parentSearching || parentSearchQuery.trim().length >= 2)"
                class="absolute z-30 mt-1 w-full bg-default border border-default rounded-lg shadow-xl max-h-[260px] overflow-y-auto"
              >
                <!-- Loading -->
                <div v-if="parentSearching" class="px-3 py-3 text-xs text-muted flex items-center gap-2">
                  <UIcon name="i-lucide-loader-circle" class="size-4 shrink-0 animate-spin" />
                  Buscando…
                </div>
                <!-- No results -->
                <div v-else-if="parentCandidates.length === 0" class="px-3 py-3 text-xs text-muted">
                  Sin resultados para "{{ parentSearchQuery.trim() }}".
                </div>
                <!-- Results -->
                <template v-else>
                  <button
                    v-for="cand in parentCandidates"
                    :key="cand.id"
                    type="button"
                    class="w-full text-left px-3 py-2 hover:bg-elevated transition-colors flex flex-col gap-0.5 border-b border-default last:border-0"
                    :class="store.bookingOptions.parentTourId === cand.id ? 'bg-primary/5' : ''"
                    @click="selectParent(cand)"
                  >
                    <span class="text-sm font-semibold">{{ cand.h1_title }}</span>
                    <span class="text-[11px] text-muted">
                      {{ cand.city_name }} · {{ formatChildCount(cand.child_count) }}
                    </span>
                  </button>
                  <p v-if="parentCandidates.length >= 50" class="px-3 py-2 text-[11px] text-muted italic border-t border-default">
                    Hay más de 50. Escribe más para acotar.
                  </p>
                </template>
              </div>
            </div>
            <p v-if="store.bookingOptions.parentTourId && currentParentLabel" class="text-[11px] text-muted mt-1">
              Padre seleccionado: <strong>{{ currentParentLabel }}</strong>
            </p>
            <p v-else-if="parentSearchQuery && !parentSearching && parentCandidates.length === 0" class="text-[11px] text-muted mt-1">
              Sin resultados para "{{ parentSearchQuery }}".
            </p>
          </UFormField>

          <!-- Etiqueta -->
          <UFormField label="Etiqueta de esta opción" hint="Texto corto que verá el cliente en el badge (e.g. Compartido, Privado, + Guía Privado)" required>
            <UInput v-model="store.bookingOptions.optionLabel" placeholder="Ej: Compartido / + Guía Privado / Privado" maxlength="50" />
          </UFormField>

          <!-- Color -->
          <UFormField label="Color del badge" hint="Se asigna automáticamente según la etiqueta (Compartido=azul, +Guía=violeta, Privado=ámbar). Puedes cambiarlo.">
            <div class="grid grid-cols-6 gap-2">
              <button
                v-for="c in availableColors"
                :key="c.token"
                type="button"
                :class="[
                  'flex flex-col items-center gap-1 p-2 rounded-lg border-2 transition-all',
                  store.bookingOptions.optionColor === c.token ? 'border-primary' : 'border-default hover:border-muted'
                ]"
                @click="pickColor(c.token)"
              >
                <span :class="['inline-block size-6 rounded-full', c.swatch]"></span>
                <span class="text-[10px] font-semibold uppercase tracking-wider text-muted">{{ c.token }}</span>
              </button>
            </div>
          </UFormField>

          <!-- Preview -->
          <div class="rounded-xl border border-default bg-elevated p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-muted mb-2">Vista previa del badge</p>
            <span
              class="inline-block px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider"
              :class="previewBadgeClass"
            >
              {{ store.bookingOptions.optionLabel || 'Sin etiqueta' }}
            </span>
          </div>

          <!-- PARENT mode only: manage the child variants attached to THIS
               tour. Lets the operator build the whole group from the parent
               instead of editing each child separately. -->
          <div v-if="variantMode === 'parent'" class="space-y-3 pt-4 border-t border-default">
            <div>
              <p class="text-sm font-bold">Variantes vinculadas</p>
              <p class="text-[11px] text-muted">Tours que se muestran como opciones dentro de esta actividad.</p>
            </div>

            <!-- Current children -->
            <div v-if="childrenLoading" class="text-xs text-muted flex items-center gap-2">
              <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" /> Cargando…
            </div>
            <div v-else-if="linkedChildren.length" class="space-y-2">
              <div
                v-for="c in linkedChildren"
                :key="c.id"
                class="flex items-center justify-between gap-2 rounded-lg border border-default bg-default px-3 py-2"
              >
                <div class="min-w-0">
                  <p class="text-sm font-semibold truncate">{{ c.h1_title }}</p>
                  <span v-if="c.option_label" class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" :class="badgeClassFor(c.option_color)">
                    {{ c.option_label }}
                  </span>
                  <span v-else class="text-[11px] text-warning">Sin etiqueta — edítala en ese tour</span>
                </div>
                <UButton
                  icon="i-lucide-unlink" color="error" variant="ghost" size="xs"
                  :loading="detachingId === c.id" title="Quitar de la actividad"
                  @click="detachChild(c)"
                />
              </div>
            </div>
            <p v-else class="text-xs text-muted italic">Aún no hay variantes vinculadas.</p>

            <!-- Search-to-add -->
            <div ref="childSearchWrapperEl" class="relative" @keydown.esc="childDropdownOpen = false">
              <UInput
                v-model="childSearchQuery"
                placeholder="Buscar tour para agregar como variante…"
                icon="i-lucide-search"
                :loading="childSearching"
                @focus="childDropdownOpen = true"
                @input="onChildSearchInput"
              />
              <div
                v-if="childDropdownOpen && (childSearching || childSearchQuery.trim().length >= 2)"
                class="absolute z-30 mt-1 w-full bg-default border border-default rounded-lg shadow-xl max-h-[260px] overflow-y-auto"
              >
                <div v-if="childSearching" class="px-3 py-3 text-xs text-muted flex items-center gap-2">
                  <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" /> Buscando…
                </div>
                <div v-else-if="childCandidates.length === 0" class="px-3 py-3 text-xs text-muted">
                  Sin tours disponibles para "{{ childSearchQuery.trim() }}".
                </div>
                <template v-else>
                  <button
                    v-for="cand in childCandidates"
                    :key="cand.id"
                    type="button"
                    class="w-full text-left px-3 py-2 hover:bg-elevated transition-colors flex flex-col gap-0.5 border-b border-default last:border-0"
                    :disabled="attachingId === cand.id"
                    @click="attachChild(cand)"
                  >
                    <span class="text-sm font-semibold">{{ cand.h1_title }}</span>
                    <span class="text-[11px] text-muted">{{ cand.city_name }}</span>
                  </button>
                </template>
              </div>
            </div>
            <p class="text-[11px] text-muted">
              Solo aparecen tours activos, sin variantes propias y sin padre. Tras vincular, ponle su etiqueta (Compartido/Privado…) editando ese tour.
            </p>
          </div>
        </div>
      </div>
    </WizardSection>

    <!-- Map Modal -->
    <PickupMapModal
      :is-open="isMapModalOpen"
      :type="pickupModalType"
      :initial-data="pickupModalData"
      @close="isMapModalOpen = false"
      @save="handlePickupSave"
    />
  </div>
</template>

<script setup lang="ts">
import { useTourWizardStore } from '~/stores/tourWizard'
import TiptapEditor from '~/components/v2/TiptapEditorV2.vue'
import PickupMapModal from '~/components/tours/wizard/PickupMapModal.vue'
import WizardSection from './WizardSection.vue'
import { ref, computed } from 'vue'

const store = useTourWizardStore()

// Collapsible sections — state persisted in localStorage so F5 keeps each open/closed.
const { toggleSection, isSectionExpanded } = useCollapsibles('wizard:step6')

// Helper to toggle a value in/out of an array (UCheckbox multi-select pattern)
const toggleInArray = (arr: any[], value: any, checked: boolean) => {
  const idx = arr.indexOf(value)
  if (checked && idx === -1) arr.push(value)
  if (!checked && idx !== -1) arr.splice(idx, 1)
  // Mark the wizard dirty so the (isDirty-gated) autosave actually persists the
  // change — without this, ticking required-data fields / guide languages was
  // silently lost unless another change happened to flip isDirty.
  store.isDirty = true
}

const isInArray = (arr: any[] | undefined, value: any) => Array.isArray(arr) && arr.includes(value)

// === Tiempo de anticipación: D + H + M combinables ===
// Internamente convertimos a "minutes" totales y guardamos en quantity/unit del store.
const anticipationTotalMinutes = computed(() => {
  const q = store.bookingOptions.bookingAnticipationQuantity || 0
  const u = store.bookingOptions.bookingAnticipationUnit
  if (u === 'minutes') return q
  if (u === 'hours') return q * 60
  if (u === 'days') return q * 24 * 60
  return q * 60
})

const anticipationDays = computed({
  get: () => Math.floor(anticipationTotalMinutes.value / (24 * 60)),
  set: (v) => updateAnticipation(v, anticipationHours.value, anticipationMinutes.value),
})

const anticipationHours = computed({
  get: () => Math.floor((anticipationTotalMinutes.value % (24 * 60)) / 60),
  set: (v) => updateAnticipation(anticipationDays.value, v, anticipationMinutes.value),
})

const anticipationMinutes = computed({
  get: () => anticipationTotalMinutes.value % 60,
  set: (v) => updateAnticipation(anticipationDays.value, anticipationHours.value, v),
})

const updateAnticipation = (days: number, hours: number, minutes: number) => {
  const total = (Number(days) || 0) * 24 * 60 + (Number(hours) || 0) * 60 + (Number(minutes) || 0)
  store.bookingOptions.bookingAnticipationQuantity = total
  store.bookingOptions.bookingAnticipationUnit = 'minutes'
}

const anticipationSummary = computed(() => {
  const d = anticipationDays.value
  const h = anticipationHours.value
  const m = anticipationMinutes.value
  const parts: string[] = []
  if (d > 0) parts.push(`${d} ${d === 1 ? 'día' : 'días'}`)
  if (h > 0) parts.push(`${h} ${h === 1 ? 'hora' : 'horas'}`)
  if (m > 0) parts.push(`${m} ${m === 1 ? 'minuto' : 'minutos'}`)
  return parts.length ? parts.join(' ') : 'Sin anticipación'
})

const tourLanguages = computed(() => {
  return Object.keys(store.contentSEO).filter(code => {
    const seo = store.contentSEO[code]
    return seo && seo.title
  })
})

// Per-language booking texts - direct reference to store object
const currentBookingTexts = computed(() => {
  const seo = store.contentSEO[store.currentLanguage]
  if (!seo) return { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  if (!seo.bookingTexts) {
    seo.bookingTexts = { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
  }
  return seo.bookingTexts
})

// Map Modal Logic
const isMapModalOpen = ref(false)
const pickupModalType = ref<'meeting_point' | 'hotel_pickup'>('meeting_point')
// Index of the meeting point being edited (when modal is in 'meeting_point' mode).
const editingMeetingPointIdx = ref<number>(-1)

const pickupModalData = computed(() => {
  if (pickupModalType.value === 'meeting_point') {
    const point = store.bookingOptions.meetingPoints[editingMeetingPointIdx.value]
    return {
      lat: point?.lat ?? null,
      lng: point?.lng ?? null,
      description: point?.descriptions?.[store.currentLanguage] || '',
    }
  } else {
    return {
      lat: store.bookingOptions.pickupCenterLat,
      lng: store.bookingOptions.pickupCenterLng,
      radius: store.bookingOptions.pickupRadiusKm,
      description: currentBookingTexts.value.pickupLocationDescription || '',
    }
  }
})

const openPickupModal = (type: 'meeting_point' | 'hotel_pickup') => {
  pickupModalType.value = type
  isMapModalOpen.value = true
}

const openMeetingPointModal = (idx: number) => {
  editingMeetingPointIdx.value = idx
  pickupModalType.value = 'meeting_point'
  isMapModalOpen.value = true
}

const newMeetingPointId = () => `mp-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`

const addMeetingPoint = () => {
  store.bookingOptions.meetingPoints.push({
    id: newMeetingPointId(),
    lat: null,
    lng: null,
    descriptions: {},
  })
  store.isDirty = true
}

const removeMeetingPoint = (idx: number) => {
  store.bookingOptions.meetingPoints.splice(idx, 1)
  store.isDirty = true
}

const moveMeetingPoint = (idx: number, delta: number) => {
  const target = idx + delta
  const arr = store.bookingOptions.meetingPoints
  if (target < 0 || target >= arr.length) return
  const [item] = arr.splice(idx, 1)
  arr.splice(target, 0, item)
  store.isDirty = true
}

const updatePointDescription = (idx: number, value: string) => {
  const point = store.bookingOptions.meetingPoints[idx]
  if (!point) return
  if (!point.descriptions) point.descriptions = {}
  point.descriptions[store.currentLanguage] = value
  store.isDirty = true
}

const handlePickupSave = (data: any) => {
  if (pickupModalType.value === 'meeting_point') {
    const point = store.bookingOptions.meetingPoints[editingMeetingPointIdx.value]
    if (point) {
      point.lat = data.lat
      point.lng = data.lng
      if (!point.descriptions) point.descriptions = {}
      if (data.description) point.descriptions[store.currentLanguage] = data.description
      // Keep legacy single-point fields in sync with the first entry so anything
      // still reading meetingPointLat/Lng keeps working until callers migrate.
      if (editingMeetingPointIdx.value === 0) {
        store.bookingOptions.meetingPointLat = data.lat
        store.bookingOptions.meetingPointLng = data.lng
      }
      store.isDirty = true
    }
  } else {
    store.bookingOptions.pickupCenterLat = data.lat
    store.bookingOptions.pickupCenterLng = data.lng
    store.bookingOptions.pickupRadiusKm = data.radius
    currentBookingTexts.value.pickupLocationDescription = data.description
    store.isDirty = true
  }
  isMapModalOpen.value = false
}

const policyTypes = [
  { id: 'standard', name: 'Standard (Global)', description: 'Políticas pre-establecidas por Inca Lake para todos sus tours.' },
  { id: 'custom', name: 'Personalizada', description: 'Políticas únicas para esta actividad específica.' }
] as const

const personalFields = {
  first_name: 'Nombre',
  last_name: 'Apellido',
  birthdate: 'Fecha de Nacimiento',
  nationality: 'Nacionalidad',
  phone_whatsapp: 'Número de WhatsApp',
  email: 'Correo Electrónico',
  dietary_restrictions: 'Restricciones Alimentarias',
  gender: 'Género'
}

const operationalFields = {
  peru_entry_date: 'Fecha de ingreso al Perú',
  hotel_name: 'Nombre de su hotel',
  passport_copy: 'Copia de pasaporte o ID',
  arrival_flight: 'Vuelo de llegada',
  departure_flight: 'Vuelo de salida',
  weight_kg: 'Peso (kg)',
  height_m: 'Altura (m)',
  arrival_bus_company: 'Cía de bus de llegada',
  arrival_train: 'Tren de llegada'
}

const guideTypes = [
  { id: 'live_guide', name: 'Guía de tour en vivo' },
  { id: 'audio_guide', name: 'Audio Guía y Audífonos' },
  { id: 'informative_brochures', name: 'Folletos informativos' },
  { id: 'no_guide', name: 'Sin Guía / Tickets' },
  { id: 'none', name: 'No mostrar nada' }
] as const

const guideLanguages = [
  { id: 1, name: 'Español' },
  { id: 2, name: 'Inglés' },
  { id: 3, name: 'Francés' },
  { id: 4, name: 'Alemán' },
  { id: 5, name: 'Portugués' },
  { id: 6, name: 'Italiano' }
]

const calculateExampleTime = () => {
  const q = store.bookingOptions.bookingAnticipationQuantity
  const u = store.bookingOptions.bookingAnticipationUnit

  if (u === 'minutes') {
    const m = q % 60
    const h = Math.floor(q / 60)
    if (h === 0) return `${q} minutos antes (a las 6:${(60 - m).toString().padStart(2, '0')} AM)`
    const remaining = m === 0 ? '' : ` ${m} minutos`
    return `${h}h${remaining} antes del inicio`
  }

  if (u === 'hours') {
    if (q >= 7) {
      return `las ${24 - (q - 7)}:00 del día anterior`
    } else {
      return `las ${7 - q}:00 AM del mismo día`
    }
  } else {
    return `${q === 1 ? 'un día' : q + ' días'} antes del inicio`
  }
}

// ==== Variant grouping (Step 6 — Opciones de la actividad) =================
// Parent typeahead is server-driven (debounced) so the admin can pick from a
// fresh list even when the catalog grows. The list scopes to the same city
// by default because activities almost always live in one destination.
const config = useRuntimeConfig()
// Admin uses public.apiUrl (NUXT_PUBLIC_API_URL); the rest of this app
// reads it under that name. Using apiBase here was a copy from the
// frontend repo and left this fetch with `undefined/admin/...`, which
// failed silently and surfaced as "Sin resultados" for every search.
const apiBase = config.public.apiUrl

type ParentCandidate = {
  id: number
  h1_title: string
  slug: string | null
  city_id: number | null
  city_name: string | null
  child_count: number
}

const parentCandidates = ref<ParentCandidate[]>([])
const parentSearching = ref(false)

const availableColors = [
  { token: 'blue',    swatch: 'bg-blue-500' },
  { token: 'violet',  swatch: 'bg-violet-500' },
  { token: 'amber',   swatch: 'bg-amber-500' },
  { token: 'rose',    swatch: 'bg-rose-500' },
  { token: 'emerald', swatch: 'bg-emerald-500' },
  { token: 'sky',     swatch: 'bg-sky-500' },
] as const

// Site-wide color standard for the common variant labels — matches what the
// public detail page already shows (Compartido=blue, +Guía=violet,
// Privado=amber). Auto-applied when the operator types the label so every
// tour is consistent without anyone having to remember the convention. The
// operator can still override by clicking a swatch (sets colorManuallySet).
function suggestColorForLabel(label: string): string | null {
  const l = label.toLowerCase()
  if (l.includes('privado') && !(l.includes('guía') || l.includes('guia'))) return 'amber'
  if (l.includes('guía') || l.includes('guia')) return 'violet'
  if (l.includes('compartido') || l.includes('grupal') || l.includes('shared')) return 'blue'
  return null  // unknown label → leave whatever's set
}

const colorManuallySet = ref(false)

watch(() => store.bookingOptions.optionLabel, (label) => {
  if (colorManuallySet.value) return
  const suggested = suggestColorForLabel(label || '')
  if (suggested) store.bookingOptions.optionColor = suggested
})

function pickColor(token: string) {
  store.bookingOptions.optionColor = token
  colorManuallySet.value = true
  store.isDirty = true
}

function badgeClassFor(color?: string | null): string {
  switch (color) {
    case 'violet':  return 'bg-violet-100 text-violet-700'
    case 'amber':   return 'bg-amber-100 text-amber-700'
    case 'rose':    return 'bg-rose-100 text-rose-700'
    case 'emerald': return 'bg-emerald-100 text-emerald-700'
    case 'sky':     return 'bg-sky-100 text-sky-700'
    case 'blue':    return 'bg-blue-100 text-blue-700'
    default:        return 'bg-slate-100 text-slate-700'
  }
}
const previewBadgeClass = computed(() => badgeClassFor(store.bookingOptions.optionColor))

// ===== Child variants management (PARENT mode) ==========================
// Build the option group from the parent: list linked children, search +
// attach free tours, detach with one click. Each attach/detach is an
// immediate API call (sets the CHILD's parent_tour_id), independent of this
// tour's autosave.
const linkedChildren = ref<{ id: number; h1_title: string; option_label: string | null; option_color: string | null; active: boolean }[]>([])
const childrenLoading = ref(false)
const childSearchQuery = ref('')
const childDropdownOpen = ref(false)
const childSearching = ref(false)
const childCandidates = ref<{ id: number; h1_title: string; city_name: string | null }[]>([])
const childSearchWrapperEl = ref<HTMLElement | null>(null)
const attachingId = ref<number | null>(null)
const detachingId = ref<number | null>(null)
let childSearchTimer: any = null

async function loadChildren() {
  if (!store.tourId || store.tourId === 'new') { linkedChildren.value = []; return }
  childrenLoading.value = true
  try {
    const res = await $fetch<{ data: any[] }>(`${apiBase}/admin/tours/${store.tourId}/children?language=${store.currentLanguage || 'ES'}`)
    linkedChildren.value = res.data || []
  } catch (e) {
    console.error('load children failed', e)
  } finally {
    childrenLoading.value = false
  }
}

async function fetchChildCandidates(search = '') {
  childSearching.value = true
  try {
    const params = new URLSearchParams({ language: store.currentLanguage || 'ES' })
    if (store.tourId) params.set('exclude_id', String(store.tourId))
    if (store.basicInfo?.cityId) params.set('city_id', String(store.basicInfo.cityId))
    if (search) params.set('search', search)
    const res = await $fetch<{ data: any[] }>(`${apiBase}/admin/tours/eligible-children?${params.toString()}`)
    childCandidates.value = res.data || []
  } catch (e) {
    console.error('eligible-children failed', e)
    childCandidates.value = []
  } finally {
    childSearching.value = false
  }
}

function onChildSearchInput() {
  childDropdownOpen.value = true
  clearTimeout(childSearchTimer)
  const q = childSearchQuery.value.trim()
  if (q.length < 2) { childCandidates.value = []; childSearching.value = false; return }
  childSearchTimer = setTimeout(() => fetchChildCandidates(q), 250)
}

async function attachChild(cand: { id: number; h1_title: string }) {
  attachingId.value = cand.id
  try {
    await $fetch(`${apiBase}/admin/tours/${cand.id}/set-parent`, {
      method: 'POST',
      body: { parent_tour_id: store.tourId },
    })
    childSearchQuery.value = ''
    childCandidates.value = []
    childDropdownOpen.value = false
    await loadChildren()
  } catch (e: any) {
    console.error('attach child failed', e)
    alert(e?.data?.message || 'No se pudo vincular la variante.')
  } finally {
    attachingId.value = null
  }
}

async function detachChild(c: { id: number }) {
  detachingId.value = c.id
  try {
    await $fetch(`${apiBase}/admin/tours/${c.id}/set-parent`, {
      method: 'POST',
      body: { parent_tour_id: null },
    })
    await loadChildren()
  } catch (e) {
    console.error('detach child failed', e)
  } finally {
    detachingId.value = null
  }
}

function closeChildDropdownOnOutsideClick(e: MouseEvent) {
  if (!childDropdownOpen.value) return
  const el = childSearchWrapperEl.value
  if (el && !el.contains(e.target as Node)) childDropdownOpen.value = false
}

let parentSearchTimer: any = null
async function fetchParentCandidates(search = '') {
  parentSearching.value = true
  try {
    const params = new URLSearchParams({ language: store.currentLanguage || 'ES' })
    if (store.tourId) params.set('exclude_id', String(store.tourId))
    // Scope to the same city by default — operators almost always group
    // variants within one destination. Drop city_id to widen the search.
    if (store.basicInfo?.cityId) params.set('city_id', String(store.basicInfo.cityId))
    if (search) params.set('search', search)
    const res = await $fetch<{ data: ParentCandidate[] }>(`${apiBase}/admin/tours/eligible-parents?${params.toString()}`)
    parentCandidates.value = res.data || []
  } catch (e) {
    console.error('eligible-parents failed', e)
    parentCandidates.value = []
  } finally {
    parentSearching.value = false
  }
}

// Three-mode UX (clearer than the old binary toggle, which hid the fact that
// a tour can be a PARENT — option_label + color set, no parent_tour_id —
// without being a child). Modes:
//   standalone — independent tour, no variants. parent_tour_id=null,
//                option_label=''.
//   parent     — canonical option of an activity that has variants
//                (e.g. tour 306 "Compartido"). parent_tour_id=null,
//                option_label set.
//   child      — secondary variant pointing at a parent.
//                parent_tour_id set, option_label set.
type VariantMode = 'standalone' | 'parent' | 'child'

function deriveMode(): VariantMode {
  if (store.bookingOptions.parentTourId) return 'child'
  // A tour is a parent if it has a label OR already has child variants pointing
  // at it (child_count > 0) — the latter recovers parent mode even when the
  // operator never set the parent's own option_label.
  if (store.bookingOptions.optionLabel || (store.bookingOptions.childCount || 0) > 0) return 'parent'
  return 'standalone'
}
const variantMode = ref<VariantMode>(deriveMode())

// Keep the mode in sync if the underlying fields change (e.g. parent picked
// via the search dropdown). The setter is explicit (setMode) so toggling
// modes resets the right fields without losing already-typed labels.
watch(
  () => [store.bookingOptions.parentTourId, store.bookingOptions.optionLabel, store.bookingOptions.childCount],
  () => { variantMode.value = deriveMode() },
)

// Load child variants when in parent mode AND the tour id is ready. Watching
// both covers the async load order (tour data sets tourId + derives parent
// mode after this component mounts). Declared here, AFTER variantMode, to
// avoid a temporal-dead-zone access during setup.
watch(
  () => [variantMode.value, store.tourId] as const,
  ([m, id]) => { if (m === 'parent' && id && id !== 'new') loadChildren() },
  { immediate: true }
)

function setMode(m: VariantMode) {
  variantMode.value = m
  // Switching modes re-enables auto-color: a fresh variant should follow
  // the site standard until the operator deliberately overrides again.
  colorManuallySet.value = false
  if (m === 'standalone') {
    store.bookingOptions.parentTourId = null
    store.bookingOptions.optionLabel = ''
    store.bookingOptions.optionColor = 'blue'
  } else if (m === 'parent') {
    store.bookingOptions.parentTourId = null
    if (!store.bookingOptions.optionLabel) {
      store.bookingOptions.optionLabel = 'Estándar'
    }
  } else if (m === 'child') {
    // Reveal the search UI; parent_tour_id stays null until the user picks
    // one (backend validates exists:tours,id on save).
    if (parentCandidates.value.length === 0) fetchParentCandidates()
  }
  store.isDirty = true
}

function modeBtnClass(m: VariantMode): string {
  const active = variantMode.value === m
  return [
    'p-4 rounded-xl border-2 text-left transition-all flex items-center gap-3',
    active
      ? 'border-primary bg-primary/5 shadow-md shadow-primary/10'
      : 'border-default hover:border-muted',
  ].join(' ')
}

function modeRadioClass(m: VariantMode): string {
  const active = variantMode.value === m
  return [
    'size-5 rounded-full border-2 flex items-center justify-center shrink-0',
    active ? 'border-primary bg-primary' : 'border-default',
  ].join(' ')
}

const variantBadgeColor = computed(() => variantMode.value === 'standalone' ? 'neutral' as const : 'primary' as const)
const variantBadgeLabel = computed(() => {
  if (variantMode.value === 'parent') return 'Actividad principal'
  if (variantMode.value === 'child') return 'Variante'
  return 'Tour independiente'
})

// ---- Parent search (replaces the broken USelectMenu integration) ----
// USelectMenu in Nuxt UI v4 doesn't fire an `@update:search-term` event,
// so the server-side debounced fetch never ran — typing in the dropdown
// just filtered the initial 50 client-side. Replaced with a plain UInput
// + custom results list: simpler, server-driven, behaves predictably.
const parentSearchQuery = ref('')
const parentDropdownOpen = ref(false)
// Stable label for the currently-linked parent. Held separately from the
// search results so it survives a follow-up search (which clears
// parentCandidates) — otherwise "Padre seleccionado: X" would vanish the
// moment the operator types a new query.
const selectedParentLabel = ref('')
const currentParentLabel = computed(() => selectedParentLabel.value)

// Minimum characters before we hit the server. Below this we show a hint
// instead of dumping all 50 tours — that giant list was overflowing past
// the wizard's sticky footer and the operator never wants to scroll 50
// unrelated tours anyway. Search-first keeps the dropdown short.
const PARENT_SEARCH_MIN = 2

function onParentInputFocus() {
  parentDropdownOpen.value = true
}

function onParentSearchInput() {
  parentDropdownOpen.value = true
  clearTimeout(parentSearchTimer)
  const q = parentSearchQuery.value.trim()
  if (q.length < PARENT_SEARCH_MIN) {
    // Clear stale results so the hint shows immediately, and don't fetch.
    parentCandidates.value = []
    parentSearching.value = false
    return
  }
  parentSearchTimer = setTimeout(() => fetchParentCandidates(q), 250)
}

function selectParent(cand: ParentCandidate) {
  store.bookingOptions.parentTourId = cand.id
  selectedParentLabel.value = cand.h1_title
  parentSearchQuery.value = ''   // reset the box so the next search starts clean
  parentDropdownOpen.value = false
  store.isDirty = true
}

// Human-friendly child count copy. The original "X variante(s) ya"
// confused the operator: "ya" was meant as "already has" but read
// ambiguous, and "variante(s)" mixed singular/plural in one string.
// Branch by count instead.
function formatChildCount(n: number): string {
  if (!n || n <= 0) return 'sin variantes vinculadas'
  if (n === 1) return '1 variante vinculada'
  return `${n} variantes vinculadas`
}

// Ref to the search wrapper so the outside-click handler can scope to it.
// The previous version used target.closest('.relative') which matches every
// Tailwind .relative on the page — so any click closed nothing and the
// dropdown stayed open over the wizard's Next/Back buttons.
const parentSearchWrapperEl = ref<HTMLElement | null>(null)

function closeDropdownOnOutsideClick(e: MouseEvent) {
  if (!parentDropdownOpen.value) return
  const wrapper = parentSearchWrapperEl.value
  if (wrapper && !wrapper.contains(e.target as Node)) {
    parentDropdownOpen.value = false
  }
}

// Init: bind outside-click handler + warm the candidate cache if this tour
// already has a parent (so currentParentLabel can resolve the name on first
// render instead of showing the bare id). Use `mousedown` rather than
// `click` so the dropdown closes BEFORE a click on, say, the "Siguiente"
// button reaches it — otherwise the wizard step changes while the dropdown
// is still trying to handle the same event.
onMounted(async () => {
  document.addEventListener('mousedown', closeDropdownOnOutsideClick)
  document.addEventListener('mousedown', closeChildDropdownOnOutsideClick)
  // If this tour already points at a parent, resolve the parent's name once
  // so "Padre seleccionado: X" shows on load. We fetch the unfiltered list
  // (ordered id desc, limit 50) and look the parent up; parents tend to be
  // recent high-id tours so they land in that window. The candidates are
  // then cleared again by the next user keystroke (search-first UX).
  if (store.bookingOptions.parentTourId) {
    await fetchParentCandidates()
    const found = parentCandidates.value.find(p => p.id === store.bookingOptions.parentTourId)
    if (found) selectedParentLabel.value = found.h1_title
    parentCandidates.value = []  // reset so the dropdown starts on the hint, not 50 rows
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', closeDropdownOnOutsideClick)
  document.removeEventListener('mousedown', closeChildDropdownOnOutsideClick)
})
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
}
.dark .glass-card {
  background: rgba(15, 23, 42, 0.5);
}

.material-symbols-outlined.filled {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
