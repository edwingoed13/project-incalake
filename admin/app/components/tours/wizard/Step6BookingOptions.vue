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
            <span class="text-[10px] text-muted italic">datos básicos</span>
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
            <span class="text-[10px] text-muted italic">datos específicos</span>
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

                <!-- The map button leads, the name follows. Underneath the
                     box it read as a footnote to a field, so the box looked
                     like the thing to type into — and it is not editable at
                     all. Action first, result below it. -->
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

                <!-- Shown, not edited. This was a textarea sitting next to the
                     address the map picker had just written, so retyping it by
                     accident cost nothing and matched nothing — one of these
                     ended up reading "pUERTA DE LA plaza" while its pin still
                     said Parque De La Cultura. The place to change it is the
                     map, where the name and the coordinates move together. -->
                <div
                  class="w-full rounded-md border border-default px-3 py-2 text-sm leading-snug min-h-[2.75rem] flex items-center"
                  :class="point.descriptions[store.currentLanguage]
                    ? 'bg-elevated/50 text-default'
                    : 'bg-elevated/30 text-muted italic'"
                >
                  {{ point.descriptions[store.currentLanguage] || `Sin nombre en ${store.currentLanguage.toUpperCase()} — márcalo en el mapa` }}
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
              <p class="text-[11px] text-muted">Recojo en hoteles dentro de un radio o de una zona que dibujes</p>
            </div>
          </label>

          <Transition name="fade">
            <div v-if="store.bookingOptions.enableHotelPickup" class="px-3 pb-3 pt-2 border-t border-default space-y-2">
              <!-- Mode lives here, not only inside the map modal: from the
                   panel there was no sign the drawn option existed at all. -->
              <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-muted mb-1.5">Forma del área</p>
                <div class="grid grid-cols-2 gap-2">
                  <UButton
                    :color="store.bookingOptions.pickupAreaType !== 'polygon' ? 'primary' : 'neutral'"
                    :variant="store.bookingOptions.pickupAreaType !== 'polygon' ? 'solid' : 'outline'"
                    icon="i-lucide-target"
                    size="sm"
                    block
                    @click="setPickupAreaType('radius')"
                  >
                    Radio
                  </UButton>
                  <UButton
                    :color="store.bookingOptions.pickupAreaType === 'polygon' ? 'primary' : 'neutral'"
                    :variant="store.bookingOptions.pickupAreaType === 'polygon' ? 'solid' : 'outline'"
                    icon="i-lucide-pen-tool"
                    size="sm"
                    block
                    @click="setPickupAreaType('polygon')"
                  >
                    Zona dibujada
                  </UButton>
                </div>
              </div>

              <div class="grid grid-cols-[1fr_2fr] gap-2 items-end">
                <UFormField
                  v-if="store.bookingOptions.pickupAreaType !== 'polygon'"
                  label="Radio (km)"
                  :ui="{ label: 'text-[10px] font-black uppercase tracking-widest text-muted' }"
                >
                  <UInputNumber v-model="store.bookingOptions.pickupRadiusKm" :min="1" :max="100" class="w-full" />
                </UFormField>
                <UButton
                  :icon="store.bookingOptions.pickupAreaType === 'polygon' ? 'i-lucide-pen-tool' : 'i-lucide-target'"
                  color="neutral"
                  size="sm"
                  block
                  :class="store.bookingOptions.pickupAreaType === 'polygon' ? 'col-span-2' : ''"
                  @click="openPickupModal('hotel_pickup')"
                >
                  {{ store.bookingOptions.pickupAreaType === 'polygon' ? 'Editar zona dibujada' : 'Configurar radio' }}
                </UButton>
              </div>

              <!-- Same reasoning as the meeting points, same order: this text
                   is written by the map, never typed here, so it belongs below
                   the button that produces it rather than above it where it
                   looked like the field to fill in. -->
              <div
                class="w-full rounded-md border border-default px-3 py-2 text-sm leading-snug min-h-[2.75rem] flex items-center"
                :class="currentBookingTexts.pickupLocationDescription
                  ? 'bg-elevated/50 text-default'
                  : 'bg-elevated/30 text-muted italic'"
              >
                {{ currentBookingTexts.pickupLocationDescription || 'Sin zona definida — configúrala en el mapa' }}
              </div>
              <!-- A drawn area with no shape covers nowhere, so it must not
                   read as configured. -->
              <UAlert
                v-if="store.bookingOptions.pickupAreaType === 'polygon' && !store.bookingOptions.pickupArea?.length"
                color="warning"
                variant="subtle"
                icon="i-lucide-triangle-alert"
                description="Zona dibujada seleccionada pero sin dibujar: no se podrá recoger en ningún hotel."
              />
              <UAlert
                v-else-if="store.bookingOptions.pickupAreaType === 'polygon'"
                color="success"
                variant="subtle"
                icon="i-lucide-circle-check"
                :description="`${store.bookingOptions.pickupArea.length} zona(s) dibujada(s)`"
              />
              <UAlert
                v-else-if="store.bookingOptions.pickupCenterLat && store.bookingOptions.pickupCenterLng"
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
        <!-- Two questions in order, not three equally-weighted cards.
             "Does this tour sell in several forms?" is NO for most of the
             catalogue — 50 of 290 tours are unattached — so that answer ends
             the section in one click. Only then does the second question
             appear, and it is the one that carries a consequence. -->
        <p class="text-xs text-muted">
          Un mismo producto puede venderse de varias formas — <em>Compartido</em>, <em>Privado</em>, <em>+ Guía Privado</em>.
          Cada forma es un tour aparte; aquí se agrupan para que el viajero las vea como
          <strong>opciones dentro de una sola página</strong>.
        </p>

        <label class="flex items-start gap-3 rounded-xl border border-default p-3 cursor-pointer hover:border-primary/50 transition-colors">
          <USwitch
            :model-value="variantMode !== 'standalone'"
            @update:model-value="onToggleModalidades"
          />
          <span class="min-w-0">
            <span class="block text-sm font-bold">Este tour se vende en varias modalidades</span>
            <span class="block text-[11px] text-muted">
              Apagado: aparece por su cuenta en el listado público, como un tour normal.
            </span>
          </span>
        </label>

        <div v-if="variantMode !== 'standalone'" class="space-y-2">
          <p class="text-[10px] font-black uppercase tracking-widest text-muted">¿Qué es este tour dentro del grupo?</p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button
              type="button"
              :class="modeBtnClass('parent')"
              @click="setMode('parent')"
            >
              <div :class="modeRadioClass('parent')">
                <div v-if="variantMode === 'parent'" class="size-2 bg-white rounded-full" />
              </div>
              <div class="flex flex-col min-w-0">
                <span class="text-sm font-bold" :class="variantMode === 'parent' ? 'text-primary' : ''">El tour principal</span>
                <span class="text-[11px] text-muted">Su página es la que ve el viajero. Las demás modalidades se eligen dentro de ella.</span>
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
                <span class="text-sm font-bold" :class="variantMode === 'child' ? 'text-primary' : ''">Una modalidad de otro tour</span>
                <!-- The consequence belongs on the card that causes it. It used
                     to live only in the parent's linked-modalities list — that
                     is, on the other tour — so the person making this exact
                     choice never saw it. -->
                <span class="text-[11px] text-warning font-semibold">Dejará de aparecer por su cuenta en el listado público.</span>
                <span class="text-[11px] text-muted">Se elegirá dentro de la página del principal. Su enlace directo sigue funcionando.</span>
              </div>
            </button>
          </div>
        </div>

        <!-- Config: padre/variante necesitan etiqueta y color; sólo variante necesita el padre -->
        <div v-if="variantMode !== 'standalone'" class="space-y-4 pt-2 border-t border-default">

          <!-- Padre selector (solo para 'child') -->
          <UFormField v-if="variantMode === 'child'" label="¿De qué tour es modalidad?" hint="Busca el tour principal cuya página mostrará esta modalidad" required>
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
              Tour principal: <strong>{{ currentParentLabel }}</strong>
            </p>
            <p v-else-if="parentSearchQuery && !parentSearching && parentCandidates.length === 0" class="text-[11px] text-muted mt-1">
              Sin resultados para "{{ parentSearchQuery }}".
            </p>
          </UFormField>

          <!-- Etiqueta -->
          <UFormField
            label="Nombre que verá el viajero"
            hint="Texto corto: Compartido, Privado, + Guía Privado…"
            required
            :error="nombreModalidadError"
          >
            <UInput v-model="store.bookingOptions.optionLabel" placeholder="Ej: Compartido / + Guía Privado / Privado" maxlength="50" />
            <!-- The fallback is announced instead of injected. The field used
                 to be pre-filled with "Estándar" the moment you picked a mode,
                 so an operator saw a value nobody typed and could ship it
                 believing it was theirs — which is why tour 306 shows
                 "ESTÁNDAR" on the public page today. -->
            <template #help>
              <span v-if="!String(store.bookingOptions.optionLabel || '').trim()" class="text-[11px] text-muted">
                Si lo dejas vacío, el viajero verá «{{ variantMode === 'parent' ? 'Estándar' : 'Variante' }}».
              </span>
            </template>
          </UFormField>

          <!-- Color -->
          <UFormField label="Color de la etiqueta" hint="Se elige solo según el nombre (Compartido=azul, +Guía=violeta, Privado=ámbar). Puedes cambiarlo.">
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
                <span class="text-[10px] font-semibold uppercase tracking-wider text-muted">{{ c.name }}</span>
              </button>
            </div>
          </UFormField>

          <!-- Preview.
               It used to show the coloured pill on its own, which answered
               what the label looks like and nothing about where it lands. The
               operator was configuring a card they had never seen: hence
               "¿dónde queda esto?". This mirrors the real widget — same
               heading, same "Desde", same "Seleccionada" marker — and says
               where on the page it sits. -->
          <div class="rounded-xl border border-default bg-elevated p-3 space-y-2">
            <p class="text-[10px] font-bold uppercase tracking-widest text-muted">Así lo verá el viajero</p>

            <!-- Mock of the public "Elige tu opción" card -->
            <div class="rounded-lg border border-default bg-default p-2.5">
              <p class="text-[11px] font-black text-default flex items-center gap-1">
                <UIcon name="i-lucide-sliders-horizontal" class="size-3 text-primary" />
                Elige tu opción
              </p>
              <p class="text-[10px] text-muted mb-2">Esta actividad tiene varias modalidades. Elige la que mejor se adapte a ti.</p>

              <div class="grid gap-1.5" :class="previewOptions.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                <div
                  v-for="(opt, i) in previewOptions"
                  :key="i"
                  class="rounded-lg border-2 p-2 min-w-0"
                  :class="opt.actual ? 'border-primary bg-primary/5' : 'border-default'"
                >
                  <div class="flex items-start justify-between gap-1">
                    <span
                      class="inline-block px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                      :class="opt.badge"
                    >
                      {{ opt.label }}
                    </span>
                    <span v-if="opt.actual" class="text-[9px] font-black text-primary shrink-0">✓ Seleccionada</span>
                  </div>
                  <p class="text-[9px] text-muted uppercase tracking-wider font-semibold mt-1.5">Desde</p>
                  <p class="text-[11px] font-black" :class="opt.actual ? 'text-primary' : 'text-default'">{{ opt.price }}</p>
                </div>
              </div>
            </div>

            <p class="text-[11px] text-muted leading-snug">
              Aparece <strong>arriba de la página del tour</strong>, justo antes del panel de reserva. Al elegir otra
              modalidad, el precio y el contenido cambian sin salir de la página.
            </p>
          </div>

          <!-- The two rhythms, stated once for the whole section. -->
          <p class="text-[11px] text-muted flex items-start gap-1.5">
            <UIcon name="i-lucide-info" class="size-3.5 shrink-0 mt-px" />
            <span>
              El <strong>nombre y el color</strong> se aplican al pulsar «Actualizar», como el resto del tour.
              <strong>Vincular o quitar modalidades es inmediato</strong> y no se deshace al descartar el borrador.
            </span>
          </p>

          <!-- PARENT mode only: manage the child variants attached to THIS
               tour. Lets the operator build the whole group from the parent
               instead of editing each child separately. -->
          <div v-if="variantMode === 'parent'" class="space-y-3 pt-4 border-t border-default">
            <div>
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <p class="text-sm font-bold">Modalidades de este tour</p>
                <!-- Two save rhythms live in one section and nothing said so.
                     Linking or removing a modality is an immediate API call —
                     it reaches the public site at once, even on a published
                     tour — while the name and colour park in the draft until
                     «Actualizar». The trap that follows: link one, rename it,
                     see "Cambios sin publicar", press «Descartar» believing it
                     undoes everything, and the link is still there, live. -->
                <UBadge color="warning" variant="subtle" size="xs" icon="i-lucide-zap">
                  Se aplican al instante
                </UBadge>
              </div>
              <p class="text-[11px] text-muted">Tours que se muestran como opciones dentro de este tour principal.</p>
              <!-- The consequence nobody expects: attaching a tour here takes
                   it OUT of /tours. The public listing shows only the parent of
                   each group, so a variant that used to appear on its own
                   quietly stops doing so — findable afterwards only through
                   this page or its direct link. Better said here than
                   discovered when someone asks where their tour went. -->
              <p class="text-[11px] text-warning mt-1 flex items-start gap-1">
                <UIcon name="i-lucide-info" class="size-3.5 shrink-0 mt-px" />
                <span>
                  Al vincular un tour, <strong>deja de aparecer por su cuenta en el listado público</strong>:
                  pasa a verse solo como opción dentro de este. Su enlace directo sigue funcionando.
                </span>
              </p>
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
                  <span v-else class="text-[11px] text-warning">Sin nombre — edítalo en ese tour</span>
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
                placeholder="Buscar un tour para agregar como modalidad…"
                icon="i-lucide-search"
                :loading="childSearching"
                @focus="childDropdownOpen = true"
                @input="onChildSearchInput"
              />
              <!-- Drop-UP: this search box is the LAST element of the step, so a
                   downward dropdown gets clipped by the scroll area / hidden
                   behind the wizard's bottom nav. Opening upward keeps every
                   result visible. -->
              <div
                v-if="childDropdownOpen && (childSearching || childSearchQuery.trim().length >= 2)"
                class="absolute z-30 bottom-full mb-1 w-full bg-default border border-default rounded-lg shadow-xl max-h-[260px] overflow-y-auto"
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
              Solo aparecen tours activos que aún no tienen modalidades ni pertenecen a otro tour. Tras vincular, ponle su nombre (Compartido/Privado…) editando ese tour.
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
// Unlinking modalities is immediate and public, so it goes through a confirm.
const { confirm } = useConfirm()

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
      // Shared across languages: the photo of the corner is the same corner
      // whichever language the traveller reads.
      image: point?.image ?? null,
    }
  } else {
    return {
      lat: store.bookingOptions.pickupCenterLat,
      lng: store.bookingOptions.pickupCenterLng,
      radius: store.bookingOptions.pickupRadiusKm,
      description: currentBookingTexts.value.pickupLocationDescription || '',
      areaType: store.bookingOptions.pickupAreaType || 'radius',
      area: store.bookingOptions.pickupArea || [],
    }
  }
})

const openPickupModal = (type: 'meeting_point' | 'hotel_pickup') => {
  pickupModalType.value = type
  isMapModalOpen.value = true
}

// Switching to a drawn zone opens the map straight away: choosing the mode and
// then having to find a second button to actually draw is the step people miss.
const setPickupAreaType = (type: 'radius' | 'polygon') => {
  if (store.bookingOptions.pickupAreaType === type) return
  store.bookingOptions.pickupAreaType = type
  store.isDirty = true
  if (type === 'polygon') openPickupModal('hotel_pickup')
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
    image: null,
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

const handlePickupSave = (data: any) => {
  if (pickupModalType.value === 'meeting_point') {
    const point = store.bookingOptions.meetingPoints[editingMeetingPointIdx.value]
    if (point) {
      point.lat = data.lat
      point.lng = data.lng
      point.image = data.image ?? null
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
    store.bookingOptions.pickupAreaType = data.areaType === 'polygon' ? 'polygon' : 'radius'
    // Keep whatever was drawn even when saving in radius mode, so switching
    // back doesn't silently discard the operator's shape.
    if (Array.isArray(data.area)) store.bookingOptions.pickupArea = data.area
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
// All the variant-grouping endpoints are admin-gated server-side now (they
// were public by mistake), so every call must carry the operator's token.
const authHeaders = () => ({ Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}` })

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

// `token` is what gets stored (the public page maps it to classes); `name` is
// what the operator reads — the swatch said BLUE/AMBER while the hint next to
// it spoke Spanish.
const availableColors = [
  { token: 'blue',    name: 'Azul',     swatch: 'bg-blue-500' },
  { token: 'violet',  name: 'Violeta',  swatch: 'bg-violet-500' },
  { token: 'amber',   name: 'Ámbar',    swatch: 'bg-amber-500' },
  { token: 'rose',    name: 'Rosa',     swatch: 'bg-rose-500' },
  { token: 'emerald', name: 'Verde',    swatch: 'bg-emerald-500' },
  { token: 'sky',     name: 'Celeste',  swatch: 'bg-sky-500' },
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

/**
 * The rows the public "Elige tu opción" card would show, as it would show
 * them: this tour plus whatever is linked to it.
 *
 * Built from the same three inputs the site uses — label, colour and price —
 * so the preview cannot quietly drift from the real card. The fallbacks match
 * the frontend's own: an unnamed parent reads "Estándar", an unnamed variant
 * reads "Variante".
 */
const previewOptions = computed(() => {
  const precio = (v: any) => {
    const n = Number(v)
    return Number.isFinite(n) && n > 0 ? `$ ${n.toFixed(2)}` : '—'
  }
  // Cheapest configured price for THIS tour, which is what "Desde" shows.
  const propio = (store.commercialRules.ageStages || [])
    .filter((st: any) => st.active)
    .flatMap((st: any) => (st.nationalities || []).flatMap((n: any) => (n.ranges || []).map((r: any) => Number(r.price))))
    .filter((n: number) => Number.isFinite(n) && n > 0)

  const filas = [{
    label: store.bookingOptions.optionLabel || (variantMode.value === 'parent' ? 'Estándar' : 'Variante'),
    badge: badgeClassFor(store.bookingOptions.optionColor),
    price: propio.length ? precio(Math.min(...propio)) : '—',
    actual: true,
  }]

  if (variantMode.value === 'parent') {
    for (const c of linkedChildren.value) {
      filas.push({
        label: c.option_label || 'Variante',
        badge: badgeClassFor(c.option_color),
        price: precio(c.min_price),
        actual: false,
      })
    }
  }
  return filas
})

// ===== Child variants management (PARENT mode) ==========================
// Build the option group from the parent: list linked children, search +
// attach free tours, detach with one click. Each attach/detach is an
// immediate API call (sets the CHILD's parent_tour_id), independent of this
// tour's autosave.
const linkedChildren = ref<{ id: number; h1_title: string; option_label: string | null; option_color: string | null; active: boolean; min_price?: number | string | null }[]>([])
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
    const res = await $fetch<{ data: any[] }>(`${apiBase}/admin/tours/${store.tourId}/children?language=${store.currentLanguage || 'ES'}`, { headers: authHeaders() })
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
    const res = await $fetch<{ data: any[] }>(`${apiBase}/admin/tours/eligible-children?${params.toString()}`, { headers: authHeaders() })
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
      headers: authHeaders(),
      body: { parent_tour_id: store.tourId },
    })
    childSearchQuery.value = ''
    childCandidates.value = []
    childDropdownOpen.value = false
    await loadChildren()
  } catch (e: any) {
    console.error('attach child failed', e)
    useToast().add({ title: 'No se pudo vincular la modalidad', description: e?.data?.message, color: 'error', icon: 'i-lucide-circle-alert' })
  } finally {
    attachingId.value = null
  }
}

async function detachChild(c: { id: number }) {
  detachingId.value = c.id
  try {
    await $fetch(`${apiBase}/admin/tours/${c.id}/set-parent`, {
      method: 'POST',
      headers: authHeaders(),
      body: { parent_tour_id: null },
    })
    await loadChildren()
  } catch (e) {
    console.error('detach child failed', e)
  } finally {
    detachingId.value = null
  }
}

/**
 * Unlink every modality at once, so "this tour has no modalities" is true in
 * the database and not just on screen. Returns the ids it could not detach —
 * a half-applied change is what leaves orphans behind.
 */
async function detachAllChildren(): Promise<number[]> {
  const fallidos: number[] = []
  for (const c of [...linkedChildren.value]) {
    try {
      await $fetch(`${apiBase}/admin/tours/${c.id}/set-parent`, {
        method: 'POST',
        headers: authHeaders(),
        body: { parent_tour_id: null },
      })
    } catch (e) {
      console.error('detach child failed', c.id, e)
      fallidos.push(c.id)
    }
  }
  await loadChildren()
  return fallidos
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
    const res = await $fetch<{ data: ParentCandidate[] }>(`${apiBase}/admin/tours/eligible-parents?${params.toString()}`, { headers: authHeaders() })
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

async function setMode(m: VariantMode) {
  // Turning a parent back into a standalone tour used to clear only THIS
  // tour's label. `parent_tour_id` lives on the children, so they kept
  // pointing here: deriveMode() saw them on the next load and put the screen
  // back to "Tour principal", silently undoing what the operator chose. Either
  // the links go too, or the choice cannot be made.
  if (m === 'standalone' && variantMode.value === 'parent' && linkedChildren.value.length > 0) {
    const n = linkedChildren.value.length
    const ok = await confirm({
      title: `Este tour tiene ${n} ${n === 1 ? 'modalidad vinculada' : 'modalidades vinculadas'}`,
      description: `Al quitarlas, ${n === 1 ? 'volverá' : 'volverán'} a aparecer por su cuenta en el listado público, cada una como un tour aparte. Esto se aplica de inmediato, no al pulsar «Actualizar».`,
      confirmLabel: n === 1 ? 'Quitar la modalidad' : `Quitar las ${n} modalidades`,
      cancelLabel: 'Cancelar',
      confirmColor: 'warning',
      icon: 'i-lucide-unlink',
      iconColor: 'warning',
    })
    if (!ok) return
    const fallidos = await detachAllChildren()
    if (fallidos.length) {
      useToast().add({
        title: 'No se pudieron quitar todas las modalidades',
        description: `Quedaron vinculadas ${fallidos.length}. Vuelve a intentarlo.`,
        color: 'error',
        icon: 'i-lucide-circle-alert',
      })
      return
    }
  }

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
    // Deliberately NOT pre-filling "Estándar" here. Doing so put a value in
    // the field that nobody chose, which is how tour 306 came to publish
    // "ESTÁNDAR" as its option name. The fallback is now stated under the
    // field instead, so an empty box reads as empty.
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

/**
 * The first question: does this tour have modalities at all?
 *
 * On, it defaults to "the main tour" — the common case, and the one that
 * cannot orphan anything. Off routes through setMode('standalone'), which
 * carries the guard that unlinks the children first.
 */
async function onToggleModalidades(on: boolean) {
  await setMode(on ? 'parent' : 'standalone')
}

/**
 * Flagged only for a MODALITY. Both fallbacks are silent today, but they are
 * not equally bad: an unnamed main tour shows "Estándar", which is a fair
 * default for the base option, while an unnamed modality shows "Variante" —
 * a word that tells a traveller nothing about what they are choosing.
 * Blocking the main tour too would have locked tour 306 out of saving over a
 * field that reads fine on its public page.
 */
const nombreModalidadError = computed(() =>
  variantMode.value === 'child' && !String(store.bookingOptions.optionLabel || '').trim()
    ? 'Ponle un nombre; sin él el viajero lee «Variante», que no dice nada.'
    : undefined
)

const variantBadgeColor = computed(() => variantMode.value === 'standalone' ? 'neutral' as const : 'primary' as const)
const variantBadgeLabel = computed(() => {
  // The collapsed header is the only clue this section exists. "Tour
  // independiente" said the least of the three; a count says what is inside.
  if (variantMode.value === 'parent') {
    const n = linkedChildren.value.length
    return n ? `Principal · ${n} ${n === 1 ? 'modalidad' : 'modalidades'}` : 'Tour principal'
  }
  if (variantMode.value === 'child') {
    return currentParentLabel.value ? `Modalidad de ${currentParentLabel.value}` : 'Modalidad'
  }
  return 'Sin modalidades'
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
  if (!n || n <= 0) return 'sin modalidades vinculadas'
  if (n === 1) return '1 modalidad vinculada'
  return `${n} modalidades vinculadas`
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


.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
