<script setup lang="ts">
import { useTourWizardStore, setWizardErrorNotifier, setWizardStepGuard } from '~/stores/tourWizard'
import { useRoute, useRouter } from 'vue-router'
import { computed, onMounted, onBeforeUnmount, watch, ref } from 'vue'

import WizardStepper from '~/components/v2/WizardStepper.vue'
import WizardInsightsSidebar from '~/components/tours/wizard/WizardInsightsSidebar.vue'
import Step1BasicInfo from '~/components/tours/wizard/Step1BasicInfo.vue'
import Step2ContentSEO from '~/components/tours/wizard/Step2ContentSEO.vue'
import Step3DetailedContent from '~/components/tours/wizard/Step3DetailedContent.vue'
import Step4CommercialRules from '~/components/tours/wizard/Step4CommercialRules.vue'
import Step5Multimedia from '~/components/tours/wizard/Step5Multimedia.vue'
import Step6BookingOptions from '~/components/tours/wizard/Step6BookingOptions.vue'
import Step8Availability from '~/components/tours/wizard/Step8Availability.vue'
import Step8FinalReview from '~/components/tours/wizard/Step8FinalReview.vue'

definePageMeta({
  layout: 'admin-v2',
  middleware: 'auth',
})

const store = useTourWizardStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const { confirm } = useConfirm()
const config = useRuntimeConfig()

// Public preview — also exposed in the navbar (below 2xl the insights sidebar
// that normally holds this button is hidden, so testers on tablets/laptops
// couldn't reach it).
const FRONTEND_URL = (config.public as any).frontendUrl || 'https://incalake-frontend.vercel.app'
const slugifyCity = (name: string) => (name || '')
  .toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '')
  .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-')
const previewLang = computed(() => {
  const langs = [store.currentLanguage || 'es', 'es', 'en', 'pt', 'fr', 'de', 'it']
  for (const l of langs) {
    if (store.contentSEO?.[l]?.slug) return l
  }
  return ''
})
const previewUrl = computed(() => {
  if (!store.tourId || store.tourId === 'new' || !previewLang.value) return ''
  const slug = (store.contentSEO[previewLang.value].slug || '').trim()
  if (!slug) return ''
  const city = store.basicInfo.citySlug || slugifyCity(store.basicInfo.nearestCity || '') || 'puno'
  return `${FRONTEND_URL}/${previewLang.value}/${city}/${slug}`
})
const previewTour = () => {
  if (!previewUrl.value) {
    toast.add({ title: 'Vista previa no disponible', description: 'Guarda el tour para generar el enlace.', color: 'warning', icon: 'i-lucide-info' })
    return
  }
  window.open(previewUrl.value, '_blank', 'noopener,noreferrer')
}

// Pre-warm the public page's Vercel cache as soon as the preview link is known,
// so opening "Vista previa" is a cache HIT (~0.5s) instead of a cold SSR (~3-4s).
let warmedUrl = ''
const warmPreview = () => {
  if (!import.meta.client) return
  const u = previewUrl.value
  if (!u || warmedUrl === u) return
  warmedUrl = u
  fetch(u, { mode: 'no-cors' }).catch(() => {})
}
watch(previewUrl, (u) => { if (u) warmPreview() }, { immediate: true })

// Publish from the bottom nav (last step). Mirrors the sidebar action so the
// flow works below xl too, where the insights sidebar is hidden.
const publishing = ref(false)
const publishTour = async () => {
  const wasPublished = store.persistedStatus === 'published'
  const ok = await confirm({
    title: wasPublished ? 'Actualizar publicación' : 'Publicar tour',
    description: wasPublished
      ? '¿Confirmas la actualización? Los cambios serán visibles en el sitio público inmediatamente.'
      : '¿Confirmas publicar este tour? Será visible en el sitio público inmediatamente.',
    confirmLabel: wasPublished ? 'Actualizar' : 'Publicar',
    confirmColor: 'success',
    icon: 'i-lucide-rocket',
    iconColor: 'success',
  })
  if (!ok) return
  publishing.value = true
  try {
    // publishDraft runs the normal save (the wizard state is already loaded,
    // draft or not) and then drops the parked copy so it can't re-apply later.
    const done = await store.publishDraft('published')
    if (done) {
      toast.add({
        title: wasPublished ? 'Cambios publicados' : 'Tour publicado',
        description: 'Ya son visibles en el sitio público.',
        icon: 'i-lucide-rocket',
        color: 'success',
      })
    } else if (store.draftError) {
      toast.add({
        title: 'Revisa el resultado',
        description: store.draftError,
        color: 'warning',
        icon: 'i-lucide-triangle-alert',
        duration: 10000,
      })
    }
  } finally {
    publishing.value = false
  }
}

// Someone else saved this tour while this tab was editing. Autosave has
// stopped, so the operator must pick a side — there is no safe default:
// keeping mine erases theirs, taking theirs erases mine.
const resolvingConflict = ref(false)

// "hace 4 min" beats an ISO timestamp for deciding which version to keep.
const conflictTimeAgo = computed(() => {
  const iso = store.draftConflict?.updatedAt
  if (!iso) return ''
  const mins = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000))
  if (mins < 1) return 'unos segundos'
  if (mins < 60) return `${mins} min`
  const h = Math.round(mins / 60)
  return h === 1 ? '1 hora' : `${h} horas`
})

// A CLEAN tab that regains focus silently catches up with drafts saved from
// another tab/computer instead of waiting to collide later — most "conflicts"
// with a shared account are just this: a stale passive tab.
const onFocusCatchUp = async () => {
  if (document.visibilityState !== 'visible') return
  const refreshed = await store.refreshDraftIfClean()
  if (refreshed) {
    toast.add({
      title: 'Borrador actualizado',
      description: 'Se cargaron cambios guardados desde otra pestaña o equipo.',
      color: 'info',
      icon: 'i-lucide-refresh-cw',
    })
  }
}
onMounted(() => document.addEventListener('visibilitychange', onFocusCatchUp))
onBeforeUnmount(() => document.removeEventListener('visibilitychange', onFocusCatchUp))

const keepTheirs = async () => {
  const ok = await confirm({
    title: 'Descartar tus cambios',
    description: 'Se cargará la versión de la otra persona y se perderá lo que hayas editado en esta pestaña sin guardar.',
    confirmLabel: 'Cargar la suya',
    cancelLabel: 'Cancelar',
    confirmColor: 'error',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'warning',
  })
  if (!ok) return
  resolvingConflict.value = true
  try {
    await store.resolveConflictWithTheirs()
    toast.add({ title: 'Cargada la versión más reciente', color: 'success', icon: 'i-lucide-refresh-cw' })
  } finally {
    resolvingConflict.value = false
  }
}

const keepMine = async () => {
  const who = store.draftConflict?.updatedByName || 'la otra persona'
  const ok = await confirm({
    title: 'Sobrescribir el otro borrador',
    description: `Tus cambios reemplazarán los de ${who}. Su trabajo se perderá y no se puede deshacer.`,
    confirmLabel: 'Sobrescribir',
    cancelLabel: 'Cancelar',
    confirmColor: 'error',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return
  resolvingConflict.value = true
  try {
    const done = await store.resolveConflictWithMine()
    toast.add(done
      ? { title: 'Guardado con tus cambios', color: 'success', icon: 'i-lucide-circle-check' }
      : { title: 'No se pudo guardar', description: store.draftError || '', color: 'error', icon: 'i-lucide-circle-alert' })
  } finally {
    resolvingConflict.value = false
  }
}

// Throw away parked edits and go back to what the public site is serving.
const discardDraft = async () => {
  const ok = await confirm({
    title: 'Descartar cambios sin publicar',
    description: 'Se perderán todos los cambios que no has publicado y volverás a la versión que está en vivo. Esto no se puede deshacer.',
    confirmLabel: 'Descartar cambios',
    cancelLabel: 'Conservarlos',
    confirmColor: 'error',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'error',
  })
  if (!ok) return

  if (await store.discardDraft()) {
    // Re-read the live tour so the wizard stops showing the discarded edits.
    await store.fetchTourData(String(route.params.id))
    store.isDirty = false
    toast.add({
      title: 'Borrador descartado',
      description: 'Estás viendo la versión publicada.',
      color: 'success',
      icon: 'i-lucide-undo-2',
    })
  } else {
    toast.add({
      title: 'No se pudo descartar',
      description: store.draftError || 'Inténtalo de nuevo.',
      color: 'error',
      icon: 'i-lucide-circle-alert',
    })
  }
}

// Remember the last focused input + scroll position per tour, so F5 keeps the user editing in place.
const focusKey = computed(() => `wizard:focus:${route.params.id}:${route.query.lang || 'es'}:${store.currentStep}`)
const { restore: restoreFocus } = useFocusMemory(focusKey.value)

// Track whether the initial fetch has finished, so subsequent saves (which also set
// store.loading) don't unmount the step component and reset scroll/dropdowns/focus.
const hasFetched = ref(false)
const isInitialLoading = computed(() => store.loading && !hasFetched.value)

const stepLabels = [
  { id: 1, category: 'Datos generales', title: 'Información básica', description: 'Comienza con los datos esenciales del tour. Se usan para indexación interna y filtros de búsqueda del cliente.' },
  { id: 2, category: 'Contenido', title: 'Contenido del tour', description: 'Redacta el título público, las descripciones y el itinerario que verá el viajero.' },
  { id: 3, category: 'SEO', title: 'SEO y buscadores', description: 'Optimiza cómo aparece el tour en Google: meta título, meta descripción y URL.' },
  { id: 4, category: 'Reglas comerciales', title: 'Precios y rangos', description: 'Configura precios por etapa de edad, nacionalidad y cantidad de pasajeros.' },
  { id: 5, category: 'Multimedia', title: 'Galería y video', description: 'Sube fotos de calidad y un video que muestren lo mejor de la experiencia.' },
  { id: 6, category: 'Reservas', title: 'Opciones de reserva', description: 'Define políticas, anticipación, datos requeridos, recojo, guía y otras reglas de la reserva.' },
  // El paso de Categorías y etiquetas se retiró: nada público las mostraba
  // (8 de 100 tours con categorías, 1 con etiquetas) y pedir ese trabajo sin
  // efecto visible era tiempo perdido del operador. Los datos existentes se
  // conservan en la base por si algún día se les da un uso visible.
  { id: 7, category: 'Operación', title: 'Disponibilidad y calendario', description: 'Define fechas activas, bloqueos por temporada baja, ofertas y restricciones de capacidad.' },
  { id: 8, category: 'Publicar', title: 'Revisión final', description: 'Resumen del tour. Revisa cada paso y publica.' },
]

const currentStepLabel = computed(() => stepLabels.find(s => s.id === store.currentStep) || stepLabels[0])

// Menu del salto de pasos (barra inferior): un item por paso, con marca en el
// actual. Reusa stepLabels para no duplicar títulos.
const stepMenuItems = computed(() => [
  stepLabels.map(s => ({
    label: `${s.id}. ${s.title}`,
    icon: s.id === store.currentStep ? 'i-lucide-circle-dot' : undefined,
    color: s.id === store.currentStep ? ('primary' as const) : undefined,
    onSelect: () => irAPaso(s.id),
  })),
])

// Cambiar de paso con trabajo sin guardar ya no se avisa con una etiqueta en
// la barra: se pregunta, y no se avanza hasta que el operador decide. La
// etiqueta estaba, y no la veia nadie — pasar de paso es justo el momento en
// que se deja de mirar arriba.
// Estado del paso actual: como llego y si se toco algo desde entonces.
const editadoEnEstePaso = ref(false)
let instantaneaPaso = ''


async function guardarAntesDeAvanzar(forzar = false): Promise<boolean> {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  const esNuevo = !store.tourId || store.tourId === 'new'
  if (esNuevo) {
    await store.saveCurrentProgress({ silent: true })
    const nuevoId = store.tourId
    if (nuevoId && nuevoId !== 'new') {
      await router.replace({ path: `/admin/v2/tours/${nuevoId}/edit`, query: route.query })
    }
    return !store.isDirty
  }
  // autosave() se rinde en silencio si ya hay otro en vuelo — y entonces esto
  // devolvia "guardado" sin haber escrito nada. Esperamos al que corre.
  for (let i = 0; i < 60 && store.autosaving; i++) {
    await new Promise(r => setTimeout(r, 100))
  }
  // Y hay que volver a marcarlo DESPUES de esa espera: el guardado en vuelo
  // apaga isDirty al terminar, y autosave() se rinde en silencio cuando lo ve
  // apagado. Marcarlo antes de esperar era pedir que no escribiera nada — que
  // es exactamente lo que pasaba al descartar: la reversion no se guardaba y
  // esto devolvia «guardado» igualmente.
  if (forzar) store.isDirty = true
  await store.autosave()
  if (store.autosaveError) {
    toast.add({
      title: 'No se pudo guardar',
      description: store.autosaveError,
      color: 'error',
      icon: 'i-lucide-circle-alert',
      duration: 8000,
    })
    return false
  }
  return true
}

async function irAPaso(destino: number) {
  if (destino < 1 || destino > store.totalSteps || destino === store.currentStep) return

  if (!editadoEnEstePaso.value && !store.isDirty) {
    store.goToStep(destino, { sinGuardia: true })
    return
  }

  const salida = await confirm({
    title: 'Cambiaste algo en este paso',
    description: (!store.tourId || store.tourId === 'new')
      ? '¿Lo conservas? Este tour todavía no existe en el servidor: lo que escribiste vive solo en esta pestaña.'
      : '¿Lo conservas? Se guarda como borrador; la versión publicada no cambia hasta que publiques.',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'warning',
    confirmLabel: 'Guardar cambios',
    confirmIcon: 'i-lucide-save',
    confirmColor: 'primary',
    cancelLabel: 'Quedarme aquí',
  })

  if (salida !== true) return

  const ok = await guardarAntesDeAvanzar(true)
  // Un guardado fallido no debe llevarse por delante el paso: el operador se
  // queda donde estaba, con su trabajo y con el motivo en pantalla.
  if (!ok) return

  editadoEnEstePaso.value = false
  store.goToStep(destino, { sinGuardia: true })
}

const autosaveLabel = computed(() => {
  if (store.autosaving) return 'Guardando...'
  // isDirty MUST be checked before lastSavedAt. After the first save
  // lastSavedAt is never null again, so the old order made 'Cambios sin
  // guardar' unreachable: with edits pending the badge turned amber (its color
  // computed below checks isDirty first) but still read "Guardado · HH:MM".
  // The operator saw "guardado" while holding unsaved changes. Same order as
  // autosaveColor now.
  if (store.isDirty) return 'Cambios sin guardar'
  if (store.lastSavedAt) {
    const at = new Date(store.lastSavedAt).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
    // On a live tour "Guardado" would read as "published" — it isn't.
    return store.isLiveTour() ? `Borrador guardado · ${at}` : `Guardado · ${at}`
  }
  return 'Todo guardado'
})
const autosaveColor = computed<'warning' | 'success' | 'info' | 'neutral'>(() => {
  if (store.autosaving) return 'info'
  if (store.isDirty) return 'warning'
  if (store.lastSavedAt) return 'success'
  return 'neutral'
})

// --- Persistent status banner ---------------------------------------------
// Toasts communicate EVENTS; what operators were missing is STATE. The
// step-change toast lasts 2.5s in a corner nobody watches while pressing
// "Siguiente", so edits on a live tour felt identical to edits on a draft.
// They are not: autosave() ships the tour's current status, so editing a
// published tour pushes to the public site ~2s later with no confirmation.
// This bar never scrolls away and says which of the two is happening.
// Class strings are static so Tailwind's scanner keeps them.
/**
 * Whether the status bar is telling the operator something they need to act on,
 * or just restating the situation. A published tour with nothing parked is the
 * steady state — the save badge in the top bar covers it, and the row is worth
 * more as working space.
 */
/**
 * Whether the form is long enough to scroll. The gutter arrows only earn their
 * place when the bottom bar is out of reach: on a short step — every accordion
 * collapsed, say — that bar is already on screen and the arrows just sat on top
 * of its own Anterior/Siguiente buttons.
 */
const formScrollEl = ref<HTMLElement | null>(null)
const formScrolls = ref(false)
let formResizeObserver: ResizeObserver | null = null

function measureFormScroll() {
  const el = formScrollEl.value
  // A couple of pixels of slack: sub-pixel rounding otherwise reports a scroll
  // on a form that fits exactly.
  formScrolls.value = !!el && el.scrollHeight - el.clientHeight > 8
}

onMounted(() => {
  measureFormScroll()
  if (typeof ResizeObserver === 'undefined') return
  formResizeObserver = new ResizeObserver(measureFormScroll)
  const el = formScrollEl.value
  if (!el) return
  // The container for viewport changes, its content for accordions opening and
  // closing — the container's own height never moves when they do.
  formResizeObserver.observe(el)
  if (el.firstElementChild) formResizeObserver.observe(el.firstElementChild)
})

onBeforeUnmount(() => {
  formResizeObserver?.disconnect()
  formResizeObserver = null
})

watch(() => store.currentStep, () => nextTick(measureFormScroll))

const bannerIsNews = computed(() =>
  store.hasPendingDraft
  || store.basicInfo.status !== 'published'
)

const statusBanner = computed(() => {
  // Parked edits win over plain "published": the operator needs to know the
  // site is NOT showing what's on screen, and that publishing is still pending.
  if (store.basicInfo.status === 'published' && store.hasPendingDraft) {
    return {
      icon: 'i-lucide-file-clock',
      label: 'CAMBIOS SIN PUBLICAR',
      // Name the button that is actually on screen: on a published tour it
      // reads "Actualizar", not "Publicar", and telling the operator to press
      // a button that isn't there is how edits sit unpublished for days.
      text: 'El público sigue viendo la versión anterior. Pulsa «Actualizar» (arriba a la derecha) para aplicarlos.',
      wrapper: 'bg-indigo-50 dark:bg-indigo-950/40 border-indigo-300 dark:border-indigo-800',
      accent: 'text-indigo-700 dark:text-indigo-400',
      muted: 'text-indigo-800/90 dark:text-indigo-200/80',
    }
  }

  switch (store.basicInfo.status) {
    case 'published':
      return {
        icon: 'i-lucide-radio',
        label: 'PUBLICADO',
        text: 'Está en vivo. Lo que edites se guarda como borrador hasta que publiques.',
        wrapper: 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800',
        accent: 'text-emerald-700 dark:text-emerald-400',
        muted: 'text-emerald-800/90 dark:text-emerald-200/80',
      }
    case 'archived':
      return {
        icon: 'i-lucide-archive',
        label: 'ARCHIVADO',
        text: 'No visible al público.',
        wrapper: 'bg-slate-100 dark:bg-slate-800/60 border-slate-300 dark:border-slate-700',
        accent: 'text-slate-600 dark:text-slate-300',
        muted: 'text-slate-600/90 dark:text-slate-400',
      }
    default:
      return {
        icon: 'i-lucide-file-pen-line',
        label: 'BORRADOR',
        text: 'No visible al público hasta que pulses Publicar.',
        wrapper: 'bg-slate-100 dark:bg-slate-800/60 border-slate-300 dark:border-slate-700',
        accent: 'text-slate-600 dark:text-slate-300',
        muted: 'text-slate-600/90 dark:text-slate-400',
      }
  }
})

// Save state repeated inside the banner: the navbar badge sits far from where
// the eye is when clicking through steps.
const saveState = computed(() => {
  if (store.autosaving) {
    return { icon: 'i-lucide-loader-circle', spin: true, class: 'text-blue-600 dark:text-blue-400' }
  }
  if (store.isDirty) {
    return { icon: 'i-lucide-circle-dot', spin: false, class: 'text-amber-600 dark:text-amber-400' }
  }
  return { icon: 'i-lucide-circle-check', spin: false, class: 'text-emerald-600 dark:text-emerald-400' }
})

const lastStepKey = computed(() => `wizard:lastStep:${route.params.id}`)

onMounted(async () => {
  // For /new, wipe any state inherited from a previously edited tour.
  // Without this, opening "Nuevo tour" after editing tour 306 shows tour 306
  // pre-filled, and the draft autosave would clone it as a new tour. Must run
  // BEFORE the lang/step setup below so those settings aren't overwritten.
  if (route.params.id === 'new') {
    store.resetWizard()
  }

  const langParam = (route.query.lang as string)?.toLowerCase()
  if (langParam) {
    store.currentLanguage = langParam
  }

  // Restore step: ?step=N wins over localStorage (so direct links still work),
  // otherwise fall back to the last step the user was on for this tour.
  const stepParam = parseInt(String(route.query.step || ''), 10)
  if (Number.isFinite(stepParam) && stepParam >= 1 && stepParam <= store.totalSteps) {
    store.currentStep = stepParam
  } else if (route.params.id && route.params.id !== 'new') {
    const remembered = parseInt(String(localStorage.getItem(lastStepKey.value) || ''), 10)
    if (Number.isFinite(remembered) && remembered >= 1 && remembered <= store.totalSteps) {
      store.currentStep = remembered
    }
  }

  if (route.params.id && route.params.id !== 'new') {
    store.setTourId(route.params.id as string)
    await store.fetchTourData(route.params.id as string)

    // A published tour may have edits parked from a previous session (possibly
    // by another operator). Overlay them on top of the live data so the
    // operator resumes where they left off instead of silently seeing the
    // live version and re-doing the work.
    if (store.isLiveTour()) {
      const restored = await store.loadDraft()
      // Re-arm dirty tracking against the data that is now on screen.
      //
      // The baseline is otherwise captured 300ms after `loading` flips, and
      // this draft fetch is a network call that usually lands later. The
      // applied draft then looked like the operator typing: the tour went
      // dirty on its own, autosave re-saved the draft nobody had touched, and
      // the list reported it as edited seconds ago — erasing the very "hace
      // 2 h" that tells you whether the work is yours or a colleague's. It
      // also bumped the version, so another open tab's next save could be
      // rejected as a conflict that never happened.
      armDirtyTracking(300)
      // And clear the flag the watcher may already have set: its deep watch
      // flushes on a microtask, so it can run between loadDraft() mutating
      // state and this line. Re-arming alone suppresses future firings but
      // does not undo one that already happened.
      store.isDirty = false
      if (restored) {
        toast.add({
          title: 'Borrador restaurado',
          description: store.draftUpdatedByName
            ? `Cambios sin publicar guardados por ${store.draftUpdatedByName}.`
            : 'Estás viendo cambios sin publicar, no la versión en vivo.',
          color: 'info',
          icon: 'i-lucide-file-clock',
          duration: 6000,
        })
      }
    }

    if (langParam) {
      store.currentLanguage = langParam
    }
  }

  // Mark initial load as done — from now on, store.loading transitions are saves, not fetches,
  // so the step component shouldn't unmount.
  hasFetched.value = true

  // Restore focus + scroll after data + DOM are settled
  await restoreFocus()
})

// Sync current step → URL query param + localStorage. Uses router.replace so it doesn't pollute browser history.
watch(() => store.currentStep, (newStep) => {
  if (route.params.id && route.params.id !== 'new') {
    try { localStorage.setItem(lastStepKey.value, String(newStep)) } catch { /* quota or disabled */ }
  }
  // Changing steps keeps everything in the store, so nothing is lost — but
  // flush any pending debounced autosave right away instead of letting edits
  // sit unsaved for 2 more seconds while the user has already moved on. A
  // toast confirms the outcome so the operator KNOWS the previous step's
  // edits were saved (or not) without having to find the navbar badge. When
  // the debounced autosave already ran BEFORE the switch (edits older than
  // ~2s), still confirm — silence read as "did it save?".
  // `toast` viene del setup (línea superior) — llamar useToast() dentro del
  // watcher pierde el contexto de Nuxt y el aviso nunca aparecía.
  if (store.tourId && store.tourId !== 'new') {
    if (store.isDirty && !store.autosaving) {
      if (autosaveTimer) {
        clearTimeout(autosaveTimer)
        autosaveTimer = null
      }
      store.autosave().then(() => {
        if (store.autosaveError) {
          toast.add({
            title: 'El paso anterior NO se guardó',
            description: store.autosaveError,
            color: 'error',
            icon: 'i-lucide-circle-alert',
            duration: 8000,
          })
        } else {
          toast.add({
            title: store.isLiveTour()
              ? 'Guardado en borrador (sin publicar)'
              : 'Cambios del paso anterior guardados',
            color: 'success',
            icon: 'i-lucide-circle-check',
            duration: 2500,
          })
          editedThisSession = false
        }
      })
    } else if (editedThisSession && !store.autosaveError) {
      toast.add({
        title: 'Todo guardado',
        description: 'Los cambios anteriores ya estaban guardados.',
        color: 'success',
        icon: 'i-lucide-circle-check',
        duration: 2000,
      })
      // One confirmation per burst of edits: further navigation without new
      // changes stays silent (the navbar badge keeps the persistent state).
      editedThisSession = false
    }
  }
  const current = parseInt(String(route.query.step || ''), 10)
  if (current === newStep) return
  router.replace({ query: { ...route.query, step: String(newStep) } })
})

let autosaveTimer: ReturnType<typeof setTimeout> | null = null
let firstSaveInFlight = false
// True once the operator modified anything this session — gates the "Todo
// guardado" step-change toast so untouched navigation stays silent.
let editedThisSession = false

// --- Dirty tracking that actually covers the whole wizard ------------------
// Most inputs v-model straight into the store; only a handful of actions set
// isDirty, so autosave (and the step-change toast) missed most edits — e.g.
// changing the capacity never marked dirty. Deep-watch every data slice and
// compare against a BASELINE snapshot: step components normalize/default
// fields when they mount, and a raw "any mutation = dirty" flag toasted
// "guardado" on tours nobody touched. Only real data differences count.
let suppressDirty = true
let baselineJson = ''
const slicesJson = () => {
  try {
    // selectedCategories/selectedTags, NOT `categories`: that one is the
    // available-category CATALOG (reference data refetched on load), so
    // picking categories or tags in step 7 never registered as an edit and
    // autosave skipped it unless something else happened to be dirty.
    return JSON.stringify([
      store.basicInfo, store.contentSEO, store.detailedContent,
      store.commercialRules, store.multimedia, store.bookingOptions,
      store.selectedCategories, store.selectedTags, store.availability,
    ])
  } catch { return '' }
}
// (Re)arm: capture the current data as the reference and start comparing.
let armTimer: ReturnType<typeof setTimeout> | null = null
function armDirtyTracking(delayMs: number) {
  suppressDirty = true
  if (armTimer) clearTimeout(armTimer)
  armTimer = setTimeout(() => {
    baselineJson = slicesJson()
    // Como llegaba el paso. isDirty no sirve para preguntar al salir: el
    // autoguardado lo apaga 1,5 s despues de la ultima tecla, asi que cuando
    // el operador pulsa «Siguiente» ya casi nunca queda nada «sin guardar» —
    // y la pregunta no salia nunca. Esto compara contra la entrada al paso, no
    // contra el ultimo guardado, y sobrevive al autoguardado.
    instantaneaPaso = baselineJson
    editadoEnEstePaso.value = false
    suppressDirty = false
  }, delayMs)
}

watch(
  () => [
    store.basicInfo, store.contentSEO, store.detailedContent,
    store.commercialRules, store.multimedia, store.bookingOptions,
    store.selectedCategories, store.selectedTags, store.availability,
  ],
  () => {
    if (suppressDirty || store.loading) return
    const ahora = slicesJson()
    if (instantaneaPaso && ahora !== instantaneaPaso) editadoEnEstePaso.value = true
    if (store.isDirty) return
    if (ahora !== baselineJson) store.isDirty = true
  },
  { deep: true }
)
// Initial load settles (mutations flush async) → arm against loaded data.
watch(() => store.loading, (loading, wasLoading) => {
  if (wasLoading && !loading) armDirtyTracking(300)
})
// New tours never enter loading — arm shortly after mount instead.
onMounted(() => armDirtyTracking(1500))

// Cualquier cambio de paso — los botones de abajo, el menu de salto y la barra
// del navbar, que llamaba al store por su cuenta — pasa por la pregunta.
onMounted(() => setWizardStepGuard((destino) => { void irAPaso(destino) }))
onBeforeUnmount(() => setWizardStepGuard(null))

// Store actions lose the Nuxt context after awaits; give the store a
// context-ful toast so validation/save errors stop falling back to alert().
onMounted(() => {
  setWizardErrorNotifier((title, description) =>
    toast.add({ title, description, color: 'error', icon: 'i-lucide-circle-alert', duration: 8000 })
  )
})
onBeforeUnmount(() => setWizardErrorNotifier(null))
// A completed save is the new reference (paths/normalizations included).
watch(() => store.isDirty, (dirty) => {
  if (!dirty) baselineJson = slicesJson()
})
// Step mounts normalize their fields — refresh the baseline right after the
// switch so those writes don't read as user edits. (The step-change toast
// above runs FIRST in its own watcher with the pre-switch state.)
watch(() => store.currentStep, () => armDirtyTracking(400))

// Señal determinista: cada componente de paso dispara @vue:mounted al terminar
// su montaje (normalizaciones síncronas incluidas), y ahí re-armamos con un
// margen mínimo. Los temporizadores de arriba se quedan como respaldo, pero ya
// no son la única defensa: en máquinas lentas la ventana de 300-400ms se
// quedaba corta, una normalización caía DESPUÉS del baseline y marcaba el
// wizard como «sucio» sin que nadie tocara nada — en un tour publicado eso
// significa un borrador espurio guardado solo. (Observado en la revisión:
// toast «Todo guardado» sin ediciones.)
const onStepMounted = () => armDirtyTracking(80)

watch(() => store.isDirty, (dirty) => {
  if (dirty) editedThisSession = true
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  if (!dirty) return

  // New tour: first dirty change triggers a debounced "create draft" save.
  // After it succeeds, the store has a real tourId and we update the URL so
  // F5 / direct link can resume editing.
  const isNew = !store.tourId || store.tourId === 'new'
  if (isNew) {
    if (firstSaveInFlight) return
    // Defense-in-depth: never create a draft from empty/leaked state. A real
    // new tour starts with a title; if title is blank, this is either initial
    // mount or stale state we shouldn't persist.
    if (!String(store.basicInfo.title || '').trim()) return
    autosaveTimer = setTimeout(async () => {
      autosaveTimer = null
      firstSaveInFlight = true
      try {
        await store.saveCurrentProgress({ silent: true })
        const newId = store.tourId
        if (newId && newId !== 'new') {
          await router.replace({
            path: `/admin/v2/tours/${newId}/edit`,
            query: route.query,
          })
        }
      } finally {
        firstSaveInFlight = false
      }
    }, 1500)
    return
  }

  // Existing tour: regular debounced autosave
  autosaveTimer = setTimeout(() => {
    store.autosave()
    autosaveTimer = null
  }, 2000)
})

// Warn before leaving with unsaved changes (only fires on actual close/refresh,
// not on internal Vue route changes).
const onBeforeUnload = (e: BeforeUnloadEvent) => {
  if (store.isDirty || firstSaveInFlight) {
    e.preventDefault()
    // Modern browsers ignore the message but require returnValue to be set.
    e.returnValue = ''
  }
}

// Internal navigation (e.g. "Volver", sidebar links) bypasses beforeunload,
// so gate it with the project's confirm dialog when a save is still pending.
onBeforeRouteLeave(async (to) => {
  // The create-draft flow replaces /new/edit with /{id}/edit — same editor.
  if (/^\/admin\/v2\/tours\/[^/]+\/edit$/.test(to.path)) return true
  if (!store.isDirty && !store.autosaving && !firstSaveInFlight) return true
  return await confirm({
    title: 'Cambios sin guardar',
    description: 'Hay cambios que todavía no terminan de guardarse. Si sales ahora podrían perderse.',
    confirmLabel: 'Salir de todos modos',
    cancelLabel: 'Quedarme',
    confirmColor: 'error',
    icon: 'i-lucide-triangle-alert',
    iconColor: 'warning',
  })
})

// Ctrl+S / Cmd+S manual save
const onKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault()
    if (store.tourId && store.tourId !== 'new') {
      // saveWork, not saveCurrentProgress: on a published tour Ctrl+S must
      // park a draft, not push the edits live.
      store.saveWork()
    }
  }
}

onMounted(() => {
  window.addEventListener('beforeunload', onBeforeUnload)
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  if (autosaveTimer) {
    clearTimeout(autosaveTimer)
    autosaveTimer = null
  }
  window.removeEventListener('beforeunload', onBeforeUnload)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <UDashboardPanel id="tour-editor-v2">
    <template #header>
      <UDashboardNavbar :ui="{ center: 'flex-1 min-w-0', root: 'gap-2' }">
        <template #leading>
          <UDashboardSidebarCollapse />
          <UBadge color="primary" variant="subtle" size="md" class="ml-1 font-mono font-bold shrink-0 hidden sm:inline-flex">
            Tour {{ route.params.id !== 'new' ? '#' + route.params.id : 'nuevo' }}
          </UBadge>
        </template>

        <!-- Steps live in the top bar to save a row of vertical space -->
        <template #default>
          <WizardStepper bare />
        </template>

        <template #right>
          <!-- spin the loader ICON, not the text — animate-spin on the label
               span made the whole "Guardando…" text rotate. -->
          <UBadge
            :color="autosaveColor"
            variant="subtle"
            size="md"
            class="shrink-0"
            :icon="store.autosaving ? 'i-lucide-loader-circle' : (store.isDirty ? 'i-lucide-circle-dot' : 'i-lucide-circle-check')"
            :ui="{ leadingIcon: store.autosaving ? 'animate-spin' : '' }"
          >
            {{ autosaveLabel }}
          </UBadge>
          <!-- Below 2xl the insights sidebar (which holds these) is hidden,
               so keep preview + publish + back reachable here as a fallback. -->
          <UButton
            icon="i-lucide-eye"
            color="neutral"
            variant="ghost"
            class="xl:hidden"
            :disabled="!previewUrl"
            :title="previewUrl || 'Guarda el tour para generar el enlace'"
            aria-label="Vista previa"
            @mouseenter="warmPreview"
            @click="previewTour"
          />
          <UButton
            icon="i-lucide-rocket"
            color="success"
            class="xl:hidden"
            :loading="publishing"
            :disabled="store.loading || store.autosaving"
            @click="publishTour"
          >
            <!-- persistedStatus, no el campo del formulario: el label debe
                 reflejar lo que ESTÁ publicado, no lo que alguien tipea. -->
            {{ store.persistedStatus === 'published' ? 'Actualizar' : 'Publicar' }}
          </UButton>
          <UButton
            to="/admin/v2/tours"
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="ghost"
            class="xl:hidden"
          >
            Volver
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="flex h-full min-h-0">
        <!-- Main content -->
        <main class="relative flex-1 flex flex-col min-h-0">
          <!-- Persistent status bar. Sits OUTSIDE the scroll container below so
               it stays pinned while the operator moves through the form.
               While the tour is still loading it shows a neutral placeholder:
               the computed banner reads the store's DEFAULTS, so a published
               tour used to flash «BORRADOR» + «Publicar» for as long as the
               fetch took (30-40s on a slow connection). -->
          <div
            v-if="isInitialLoading"
            class="shrink-0 border-b border-default bg-elevated/40 px-4 lg:px-6 py-2.5 flex items-center gap-3"
          >
            <USkeleton class="size-5 rounded-full" />
            <USkeleton class="h-3.5 w-64" />
          </div>
          <!-- Only when it carries news. "Está en vivo, lo que edites se guarda
               como borrador" is standing advice, not an alert: it was spending a
               permanent 41px row — 4% of the window — restating something the
               save badge in the top bar already reports. It comes back the
               moment there is something unpublished, a draft, or an archived
               tour, which is exactly when the operator must not miss it. -->
          <div
            v-else-if="bannerIsNews"
            class="shrink-0 border-b px-4 lg:px-6 py-2.5 flex items-center gap-3"
            :class="statusBanner.wrapper"
          >
            <UIcon :name="statusBanner.icon" class="size-5 shrink-0" :class="statusBanner.accent" />
            <p class="min-w-0 flex-1 text-sm leading-snug">
              <span class="font-bold" :class="statusBanner.accent">{{ statusBanner.label }}</span>
              <span class="ml-2" :class="statusBanner.muted">{{ statusBanner.text }}</span>
            </p>
            <UButton
              v-if="store.hasPendingDraft"
              size="xs"
              color="neutral"
              variant="ghost"
              icon="i-lucide-undo-2"
              class="shrink-0"
              @click="discardDraft"
            >
              Descartar
            </UButton>
          </div>

          <!-- WHAT is unpublished. The banner above only says that something
               is parked; with six languages and nine steps, that left the
               operator opening every one to find it. The languages are
               buttons because the next thing you want is to go there. -->
          <div
            v-if="store.hasPendingDraft && (store.draftChangedLanguages.length || store.draftChangedSections.length)"
            class="shrink-0 border-b border-indigo-200 dark:border-indigo-900 bg-indigo-50/60 dark:bg-indigo-950/20 px-4 lg:px-6 py-2 flex items-center gap-2 flex-wrap"
          >
            <span class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700/80 dark:text-indigo-400/80">
              Falta publicar en
            </span>
            <UButton
              v-for="code in store.draftChangedLanguages"
              :key="code"
              size="xs"
              color="info"
              :variant="store.currentLanguage === code.toLowerCase() ? 'solid' : 'subtle'"
              class="font-mono"
              :title="`Ver el contenido en ${code}`"
              @click="store.currentLanguage = code.toLowerCase()"
            >
              {{ code }}
            </UButton>
            <UBadge
              v-for="section in store.draftChangedSections"
              :key="section"
              color="info"
              variant="outline"
              size="xs"
            >
              {{ section }}
            </UBadge>
          </div>

          <!-- Edit collision. Sits above the form, outside the scroll area,
               and stays until resolved: autosave is stopped while it shows, so
               anything typed now is going nowhere. -->
          <div
            v-if="store.draftConflict"
            class="shrink-0 border-b border-red-300 dark:border-red-800 bg-red-50 dark:bg-red-950/40 px-4 lg:px-6 py-3"
          >
            <div class="flex items-start gap-3">
              <UIcon name="i-lucide-users" class="size-5 shrink-0 mt-0.5 text-red-600 dark:text-red-400" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-red-700 dark:text-red-400">
                  Este tour se guardó desde otro lugar
                </p>
                <!-- The whole team shares one login, so naming the user
                     ("Admin guardó...") tells the operator nothing. Where and
                     when is what helps them decide. -->
                <p class="text-sm text-red-800/90 dark:text-red-200/80 leading-snug mt-0.5">
                  Alguien guardó cambios{{ conflictTimeAgo ? ` hace ${conflictTimeAgo}` : '' }} desde
                  otra pestaña o equipo con esta misma cuenta. Tu guardado automático está en pausa
                  para no pisar ese trabajo.
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-2.5">
                  <UButton
                    size="xs"
                    color="neutral"
                    variant="solid"
                    icon="i-lucide-refresh-cw"
                    :loading="resolvingConflict"
                    @click="keepTheirs"
                  >
                    Usar la versión más reciente (descarta lo que escribí aquí)
                  </UButton>
                  <UButton
                    size="xs"
                    color="error"
                    variant="outline"
                    icon="i-lucide-triangle-alert"
                    :loading="resolvingConflict"
                    @click="keepMine"
                  >
                    Conservar lo mío (reemplaza lo del otro lugar)
                  </UButton>
                </div>
              </div>
            </div>
          </div>

          <!-- pb-28 reserves space below the last field so the sticky bottom
               nav bar (~56px) never covers it; scroll-pb-28 makes keyboard /
               programmatic scroll-into-view stop above the bar too. Fixes the
               recurring "dropdown / last input hidden behind the footer". -->
          <div ref="formScrollEl" class="flex-1 overflow-y-auto p-4 lg:p-6 pb-28 scroll-pb-28 xl:pb-6 xl:scroll-pb-6">
          <!-- 2xl+: en un monitor grande, 1024px de tope dejaban pasillos de
               margen muerto a ambos lados del formulario. -->
          <div class="max-w-5xl 2xl:max-w-7xl mx-auto">
            <!-- Step header (the stepper already shows the step/category).
                 The tour title lives here at full content width — the navbar
                 truncated long titles next to the stepper. -->
            <div class="mb-5">
              <p v-if="store.basicInfo.title" class="text-sm font-semibold text-primary leading-snug mb-1">
                {{ store.basicInfo.title }}
              </p>
              <h1 class="text-xl font-bold tracking-tight">{{ currentStepLabel?.title }}</h1>
              <p class="text-sm text-muted leading-snug mt-1">{{ currentStepLabel?.description }}</p>
            </div>

            <!-- Loading (only during initial fetch — saves use the autosave badge in the navbar) -->
            <div v-if="isInitialLoading" class="flex flex-col items-center justify-center py-20">
              <UIcon name="i-lucide-loader-circle" class="size-10 text-primary animate-spin mb-3" />
              <p class="text-sm font-bold text-muted">Cargando datos del tour...</p>
            </div>

            <!-- Step components -->
            <Transition v-else name="fade" mode="out-in">
              <Step1BasicInfo v-if="store.currentStep === 1" @vue:mounted="onStepMounted" />
              <Step3DetailedContent v-else-if="store.currentStep === 2" @vue:mounted="onStepMounted" />
              <Step2ContentSEO v-else-if="store.currentStep === 3" @vue:mounted="onStepMounted" />
              <Step4CommercialRules v-else-if="store.currentStep === 4" @vue:mounted="onStepMounted" />
              <Step5Multimedia v-else-if="store.currentStep === 5" @vue:mounted="onStepMounted" />
              <Step6BookingOptions v-else-if="store.currentStep === 6" @vue:mounted="onStepMounted" />
              <Step8Availability v-else-if="store.currentStep === 7" @vue:mounted="onStepMounted" />
              <Step8FinalReview v-else-if="store.currentStep === 8" @vue:mounted="onStepMounted" />
              <UCard v-else>
                <div class="flex flex-col items-center text-center py-12 gap-3">
                  <UIcon name="i-lucide-hammer" class="size-12 text-muted" />
                  <p class="text-base font-bold">Paso {{ store.currentStep }} en construcción</p>
                  <UButton variant="ghost" size="sm" @click="irAPaso(store.currentStep - 1)">Regresar al paso anterior</UButton>
                </div>
              </UCard>
            </Transition>
          </div>
            <!-- Bottom navigation. Below xl it sticks to the bottom of the scroll
               area, because the stepper in the top bar is hidden there and this
               is the only way to move between steps. From xl it flows at the end
               of the form instead: the stepper above already jumps anywhere, so a
               permanently pinned bar was charging 49px for an affordance the page
               already had — and "Siguiente" belongs where the form ends. -->
            <div class="sticky bottom-0 xl:static -mx-4 lg:-mx-6 mt-6 border-t border-default bg-default px-4 lg:px-6 py-2">
              <div class="max-w-5xl 2xl:max-w-7xl mx-auto flex items-center justify-between gap-3">
                <UButton
                  icon="i-lucide-arrow-left"
                  color="neutral"
                  variant="ghost"
                  :disabled="store.currentStep <= 1"
                  @click="irAPaso(store.currentStep - 1)"
                >
                  Anterior
                </UButton>

                <!-- Salto directo entre pasos. En movil el stepper del navbar no
                     existe (lo aplastan los botones), asi que sin esto llegar al
                     paso 8 eran siete toques de «Siguiente». -->
                <UDropdownMenu :items="stepMenuItems" :content="{ align: 'center', side: 'top' }">
                  <UButton color="neutral" variant="ghost" size="sm" trailing-icon="i-lucide-chevrons-up-down" class="tabular-nums whitespace-nowrap shrink-0">
                    Paso {{ store.currentStep }} de {{ store.totalSteps }}
                    <span class="hidden sm:inline text-muted font-normal">· {{ currentStepLabel?.title }}</span>
                  </UButton>
                </UDropdownMenu>

                <UButton
                  v-if="store.currentStep < store.totalSteps"
                  trailing-icon="i-lucide-arrow-right"
                  color="primary"
                  @click="irAPaso(store.currentStep + 1)"
                >
                  Siguiente
                </UButton>
                <span v-else class="text-xs text-muted inline-flex items-center gap-1">
                  <UIcon name="i-lucide-check" class="size-3.5 text-success" />
                  Último paso · revisa y publica
                </span>
              </div>
            </div>
          </div>


          <!-- Step arrows, carousel-style, parked in the gutters beside the
               form. With the bottom bar flowing at the end of the page,
               advancing meant scrolling to the bottom first — on a long step
               that is a lot of wheel for one click. These sit at eye level and
               never move, so the operator can move on from wherever they are.
               xl+ only: below that the bottom bar is still pinned and these
               would have no gutter to sit in. -->
          <button
            v-if="store.currentStep > 1 && formScrolls"
            type="button"
            class="hidden xl:flex absolute left-1 top-1/2 -translate-y-1/2 z-20 size-10 items-center justify-center rounded-full border border-default bg-default/90 backdrop-blur shadow-md text-muted hover:text-primary hover:border-primary/50 transition-colors"
            title="Paso anterior"
            aria-label="Paso anterior"
            @click="irAPaso(store.currentStep - 1)"
          >
            <UIcon name="i-lucide-chevron-left" class="size-5" />
          </button>
          <button
            v-if="store.currentStep < store.totalSteps && formScrolls"
            type="button"
            class="hidden xl:flex absolute right-1 top-1/2 -translate-y-1/2 z-20 size-10 items-center justify-center rounded-full border border-default bg-default/90 backdrop-blur shadow-md text-muted hover:text-primary hover:border-primary/50 transition-colors"
            title="Paso siguiente"
            aria-label="Paso siguiente"
            @click="irAPaso(store.currentStep + 1)"
          >
            <UIcon name="i-lucide-chevron-right" class="size-5" />
          </button>
        </main>

        <!-- Insights sidebar -->
        <!-- Oculto durante la carga inicial: sus tarjetas (calidad, ubicación,
             botón publicar) se calculan sobre el store vacío y mostraban
             «20% — necesita más trabajo» y «Publicar» en tours completos y
             ya publicados. -->
        <WizardInsightsSidebar v-if="!isInitialLoading" />
      </div>
    </template>
  </UDashboardPanel>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
