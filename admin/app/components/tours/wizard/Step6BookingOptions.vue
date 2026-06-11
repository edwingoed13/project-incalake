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
          @click="store.bookingOptions.dataRequirementType = 'leader'"
        >
          Solo líder
        </button>
        <button
          type="button"
          :class="[
            'px-4 py-1.5 text-xs font-bold uppercase tracking-widest rounded-md transition-all',
            store.bookingOptions.dataRequirementType === 'all' ? 'bg-default text-primary shadow-sm' : 'text-muted',
          ]"
          @click="store.bookingOptions.dataRequirementType = 'all'"
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
              <!-- Resultados dropdown. max-h sized to fit roughly 6 entries
                   (each card ~ 52 px); the explicit pb-2 buys a few pixels
                   so the LAST scrolled-into-view entry doesn't get clipped
                   under the rounded corner / shadow. The footer caption
                   below tells the user there are more if truncated. -->
              <div
                v-if="parentDropdownOpen && (parentCandidates.length > 0 || parentSearching)"
                class="absolute z-30 mt-1 w-full bg-default border border-default rounded-lg shadow-xl max-h-[360px] overflow-y-auto pb-2"
              >
                <div v-if="parentSearching && parentCandidates.length === 0" class="px-3 py-2 text-xs text-muted">
                  Buscando…
                </div>
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
                    {{ cand.city_name }} · {{ cand.child_count }} variante(s) ya
                  </span>
                </button>
                <p v-if="parentCandidates.length >= 50" class="px-3 py-2 text-[11px] text-muted italic border-t border-default">
                  Mostrando los primeros 50. Refina tu búsqueda para acotar.
                </p>
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
          <UFormField label="Color del badge">
            <div class="grid grid-cols-6 gap-2">
              <button
                v-for="c in availableColors"
                :key="c.token"
                type="button"
                :class="[
                  'flex flex-col items-center gap-1 p-2 rounded-lg border-2 transition-all',
                  store.bookingOptions.optionColor === c.token ? 'border-primary' : 'border-default hover:border-muted'
                ]"
                @click="store.bookingOptions.optionColor = c.token"
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

const previewBadgeClass = computed(() => {
  switch (store.bookingOptions.optionColor) {
    case 'violet':  return 'bg-violet-100 text-violet-700'
    case 'amber':   return 'bg-amber-100 text-amber-700'
    case 'rose':    return 'bg-rose-100 text-rose-700'
    case 'emerald': return 'bg-emerald-100 text-emerald-700'
    case 'sky':     return 'bg-sky-100 text-sky-700'
    case 'blue':    return 'bg-blue-100 text-blue-700'
    default:        return 'bg-slate-100 text-slate-700'
  }
})

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

function onParentSearch(term: string) {
  clearTimeout(parentSearchTimer)
  parentSearchTimer = setTimeout(() => fetchParentCandidates(term), 250)
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
  if (store.bookingOptions.optionLabel) return 'parent'
  return 'standalone'
}
const variantMode = ref<VariantMode>(deriveMode())

// Keep the mode in sync if the underlying fields change (e.g. parent picked
// via the search dropdown). The setter is explicit (setMode) so toggling
// modes resets the right fields without losing already-typed labels.
watch(
  () => [store.bookingOptions.parentTourId, store.bookingOptions.optionLabel],
  () => { variantMode.value = deriveMode() },
)

function setMode(m: VariantMode) {
  variantMode.value = m
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
const currentParentLabel = computed(() => {
  const found = parentCandidates.value.find(p => p.id === store.bookingOptions.parentTourId)
  return found?.h1_title || ''
})

function onParentInputFocus() {
  parentDropdownOpen.value = true
  if (parentCandidates.value.length === 0) fetchParentCandidates()
}

function onParentSearchInput() {
  parentDropdownOpen.value = true
  clearTimeout(parentSearchTimer)
  parentSearchTimer = setTimeout(() => fetchParentCandidates(parentSearchQuery.value), 250)
}

function selectParent(cand: ParentCandidate) {
  store.bookingOptions.parentTourId = cand.id
  parentSearchQuery.value = cand.h1_title
  parentDropdownOpen.value = false
  store.isDirty = true
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
onMounted(() => {
  document.addEventListener('mousedown', closeDropdownOnOutsideClick)
  if (store.bookingOptions.parentTourId) fetchParentCandidates()
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', closeDropdownOnOutsideClick)
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
