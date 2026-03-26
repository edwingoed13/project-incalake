<script setup lang="ts">
interface Props {
  tour: any
}

const props = defineProps<Props>()

const DIFFICULTY_LEVELS: Record<string, any> = {
  easy: { label: 'Fácil', color: 'green' },
  moderate: { label: 'Moderado', color: 'yellow' },
  challenging: { label: 'Desafiante', color: 'orange' },
  difficult: { label: 'Difícil', color: 'red' }
}

const SERVICE_TYPES: Record<string, any> = {
  shared: { label: 'Compartido', color: 'blue' },
  private: { label: 'Privado', color: 'purple' },
  group: { label: 'Grupal', color: 'cyan' }
}

const difficultyInfo = computed(() => {
  if (!props.tour || !props.tour.difficulty) return DIFFICULTY_LEVELS.moderate
  return DIFFICULTY_LEVELS[props.tour.difficulty] || DIFFICULTY_LEVELS.moderate
})

const serviceTypeInfo = computed(() => {
  if (!props.tour || !props.tour.service_type) return SERVICE_TYPES.shared
  return SERVICE_TYPES[props.tour.service_type] || SERVICE_TYPES.shared
})

const durationText = computed(() => {
  if (!props.tour) return '0 horas'

  const days = props.tour.duration_days || 0
  const hours = props.tour.duration_hours || 0

  if (days > 0) {
    return `${days} día${days > 1 ? 's' : ''}`
  }

  if (hours > 0) {
    return `${hours} hora${hours > 1 ? 's' : ''}`
  }

  return 'Duración variable'
})

const departureTimeFormatted = computed(() => {
  if (!props.tour || !props.tour.departure_time) return ''

  const time = props.tour.departure_time
  const [hours, minutes] = time.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
})
</script>

<template>
  <section v-if="tour" class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
    <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-cyan-500 mr-2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
      </svg>
      Información importante
    </h2>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
      <!-- Duration -->
      <div class="p-2.5 text-center border border-gray-200 rounded-lg">
        <div class="flex justify-center mb-1.5">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary-600">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
          </div>
        </div>
        <h3 class="font-semibold text-gray-900 mb-0.5 text-xs">Duración</h3>
        <p class="text-xs text-gray-600">{{ durationText }}</p>
      </div>

      <!-- Difficulty -->
      <div class="p-2.5 text-center border border-gray-200 rounded-lg">
        <div class="flex justify-center mb-1.5">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary-600">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" />
            </svg>
          </div>
        </div>
        <h3 class="font-semibold text-gray-900 mb-0.5 text-xs">Dificultad</h3>
        <p class="text-xs text-gray-600">{{ difficultyInfo?.label || 'Moderada' }}</p>
      </div>

      <!-- Max Capacity -->
      <div class="p-2.5 text-center border border-gray-200 rounded-lg">
        <div class="flex justify-center mb-1.5">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary-600">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
          </div>
        </div>
        <h3 class="font-semibold text-gray-900 mb-0.5 text-xs">Capacidad</h3>
        <p class="text-xs text-gray-600">{{ tour.max_capacity || tour.capacity || 'N/A' }} personas</p>
      </div>

      <!-- Service Type -->
      <div class="p-2.5 text-center border border-gray-200 rounded-lg">
        <div class="flex justify-center mb-1.5">
          <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary-600">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
            </svg>
          </div>
        </div>
        <h3 class="font-semibold text-gray-900 mb-0.5 text-xs">Tipo</h3>
        <p class="text-xs text-gray-600">{{ serviceTypeInfo?.label || 'Tour' }}</p>
      </div>
    </div>

    <!-- Departure Time -->
    <div v-if="tour.departure_time" class="mt-3 p-2 bg-blue-50 rounded-lg border border-blue-200">
      <div class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-blue-600">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <div class="text-xs">
          <span class="font-semibold text-blue-900">Hora de salida:</span>
          <span class="ml-1.5 text-blue-700">{{ departureTimeFormatted }}</span>
        </div>
      </div>
    </div>

    <!-- Meeting Point -->
    <div v-if="tour.meeting_point" class="mt-2 p-2 bg-green-50 rounded-lg border border-green-200">
      <div class="flex items-start gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-green-600 mt-0.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        <div class="text-xs">
          <span class="font-semibold text-green-900">Punto de encuentro:</span>
          <p class="mt-0.5 text-green-700">{{ tour.meeting_point }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
