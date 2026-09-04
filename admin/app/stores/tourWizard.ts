import { defineStore } from 'pinia'
import { nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'

// Store-level error surface. Actions lose the Nuxt context after their first
// await, so useToast() there throws and we used to fall back to the ugly
// native alert. The editor page registers a context-ful notifier instead.
let externalNotifier: ((title: string, description?: string) => void) | null = null
export const setWizardErrorNotifier = (fn: typeof externalNotifier) => { externalNotifier = fn }

// Shape marker for parked drafts. Bump it whenever a state slice is renamed or
// restructured: the API drops drafts whose version doesn't match instead of
// restoring them into fields that no longer mean the same thing.
const DRAFT_SCHEMA_VERSION = 'v1'

const notifyError = (title: string, description?: string) => {
  if (externalNotifier) return externalNotifier(title, description)
  try {
    useToast().add({ title, description, color: 'error', icon: 'i-lucide-circle-alert', duration: 8000 })
  } catch {
    alert(description ? `${title}\n\n${description}` : title)
  }
}

// Default cancellation policy table — kept in sync with
// backend/app/Support/StandardCancellationPolicy.php. New tours start with
// this prefilled per language; admins can edit per-tour if they need to.
const buildPolicyTable = (headers: string[], rows: string[][]) => {
  const escape = (s: string) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;')
  const head = `<thead><tr>${headers.map(h => `<th>${escape(h)}</th>`).join('')}</tr></thead>`
  const body = `<tbody>${rows.map(r => `<tr>${r.map(c => `<td>${escape(c)}</td>`).join('')}</tr>`).join('')}</tbody>`
  return `<table class="tiptap-table">${head}${body}</table>`
}

const STANDARD_POLICY: Record<string, string> = {
  es: buildPolicyTable(
    ['Periodo de Anticipación para Anulación', 'Penalidad', 'Detalles'],
    [
      ['Hasta 48 horas antes del inicio del tour', '20% del total', 'Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.'],
      ['Dentro de las 48 horas antes del inicio del tour', '100% del total', 'Monto total acordado del servicio.'],
    ],
  ),
  en: buildPolicyTable(
    ['Cancellation Notice Period', 'Penalty', 'Details'],
    [
      ['Up to 48 hours before the tour starts', '20% of total', 'Administrative costs, credit/debit card fees and other related charges.'],
      ['Within 48 hours before the tour starts', '100% of total', 'Full agreed service amount.'],
    ],
  ),
  pt: buildPolicyTable(
    ['Prazo de Antecedência para Cancelamento', 'Penalidade', 'Detalhes'],
    [
      ['Até 48 horas antes do início do tour', '20% do total', 'Despesas administrativas, taxas de cartão de crédito/débito e outras relacionadas.'],
      ['Dentro de 48 horas antes do início do tour', '100% do total', 'Valor total acordado do serviço.'],
    ],
  ),
  fr: buildPolicyTable(
    ["Période d'Anticipation pour Annulation", 'Pénalité', 'Détails'],
    [
      ["Jusqu'à 48 heures avant le début du tour", '20% du total', 'Frais administratifs, commissions de carte de crédit/débit et autres frais associés.'],
      ['Dans les 48 heures avant le début du tour', '100% du total', 'Montant total convenu du service.'],
    ],
  ),
  de: buildPolicyTable(
    ['Stornierungsfrist', 'Gebühr', 'Details'],
    [
      ['Bis zu 48 Stunden vor Tourbeginn', '20% des Gesamtbetrags', 'Verwaltungskosten, Kredit-/Debitkartengebühren und andere damit verbundene Kosten.'],
      ['Innerhalb von 48 Stunden vor Tourbeginn', '100% des Gesamtbetrags', 'Vollständig vereinbarter Servicebetrag.'],
    ],
  ),
  it: buildPolicyTable(
    ['Periodo di Preavviso per Annullamento', 'Penalità', 'Dettagli'],
    [
      ["Fino a 48 ore prima dell'inizio del tour", '20% del totale', 'Spese amministrative, commissioni di carta di credito/debito e altre correlate.'],
      ["Entro 48 ore prima dell'inizio del tour", '100% del totale', 'Importo totale concordato del servizio.'],
    ],
  ),
}

export interface TourStep1 {
  title: string
  subtitle: string
  code: string
  serviceType: string
  targetAudience: string
  difficulty: string
  capacityMin: number
  capacityMax: number
  duration: number
  durationUnit: 'hours' | 'days' | 'minutes'
  durationDays: number
  durationHours: number
  durationMinutes: number
  startTime: string
  startTimes: Array<{ time: string; duration: number; durationUnit: 'hours' | 'days' | 'minutes'; days?: number; hours?: number; minutes?: number }>
  timezone: string
  nearestCity: string
  nearestAirport: string
  cityId?: number
  citySlug?: string
  cityCoordinates?: {
    lat: number
    lng: number
  }
  languageId?: number
  status?: 'draft' | 'published' | 'archived'
}

export interface TourSeoKeyword {
  keyword: string
  is_primary: boolean
}

export interface TourSeoFaq {
  question: string
  answer: string
}

export interface TourStep2Content {
  title: string
  shortDescription: string
  metaTitle: string
  metaDescription: string
  slug: string
  keywords?: TourSeoKeyword[]
  faqs?: TourSeoFaq[]
}

export interface ItineraryDay {
  id: string
  dayNumber: number
  title: string
  location: string
  description: string
  image: string
}

export interface MapPoint {
  id?: string
  name: string
  description: string
  coordinates: string
  type: string
  order: number
}

export interface TimelineItem {
  id: string
  time: string
  activity: string
}

export interface TourStep3Content {
  itinerary: ItineraryDay[]
  itineraryText: string
  inclusions: string
  exclusions: string
  detailedDescription: string
  recommendations: string
  thingsToBring: string
  generalPolicies: string
  cancellationPolicy: string
  customSections: Array<{ id: string; title: string; content: string }>
  mapPoints: MapPoint[]
  timelineItems?: TimelineItem[]
}

export interface PaxPriceRange {
  id: string
  from: number
  to: number
  price: number
}

export interface NationalityPrice {
  id: string
  nationalityId: string
  ageMin: number
  ageMax: number
  ranges: PaxPriceRange[]
}

export interface AgeStagePrice {
  id: string
  description: string
  minAge: number
  maxAge: number
  active: boolean
  nationalities: NationalityPrice[]
  /** False for bands the business has locked; the server enforces it too. */
  editable?: boolean
}

export interface Coupon {
  id: string
  code: string
  discount: number
  isActive: boolean
}

export interface TourImage {
  id: string
  url: string
  filename: string
  size: number
  altText: string
  titleText: string
  description: string
  isPrimary: boolean
  order: number
  // Non-destructive crop support
  originalUrl?: string          // full image the crop was derived from
  cropData?: { coordinates: { left: number; top: number; width: number; height: number }; aspect: number | null } | null
  newDisplayPath?: string | null // temp path of a freshly cropped file (existing images, re-cropped this session)
}

export interface TourStep5Multimedia {
  youtubeUrl: string
  galleryLayout: 'featured' | 'grid' | 'slider' | 'mosaic_vertical'
  images: TourImage[]
}

export interface MeetingPoint {
  id: string
  lat: number | null
  lng: number | null
  descriptions: Record<string, string>
  /** Reference photo so the traveller recognises the spot on the day. */
  image?: string | null
}

export interface TourStep6 {
  policyType: 'standard' | 'custom'
  policyDescription: string
  policyDescriptionCustom: string
  bookingAnticipationQuantity: number
  bookingAnticipationUnit: 'hours' | 'days'
  dataRequirementType: 'leader' | 'all'
  operationalInfoRequired: string[]
  personalInfoRequired: string[]
  enableMeetingPoint: boolean
  meetingPointDescription: string
  meetingPointLat: number | null
  meetingPointLng: number | null
  meetingPoints: MeetingPoint[]
  enableHotelPickup: boolean
  pickupLocationDescription: string
  pickupRadiusKm: number
  pickupCenterLat: number | null
  pickupCenterLng: number | null
  // 'radius' keeps the circle; 'polygon' uses pickupArea, a list of rings so a
  // tour can cover separate neighbourhoods.
  pickupAreaType: 'radius' | 'polygon'
  pickupArea: Array<Array<{ lat: number; lng: number }>>
  dropoffLocationDescription: string
  guideType: 'live_guide' | 'audio_guide' | 'informative_brochures' | 'no_guide' | 'none'
  guideLanguages: number[]
  // Variant grouping — Compartido / +Guía / Privado config (Phase 1)
  parentTourId: number | null
  optionLabel: string
  optionColor: string
  // Number of child variants pointing at this tour (read-only, from the API).
  // Lets Step 6 detect a parent even before its own option_label is set.
  childCount: number
}

export interface TourStep4 {
  paymentMethod: 'all' | 'paypal' | 'culqi'
  ageStages: AgeStagePrice[]
  taxPercentage: number
  advancePaymentPercentage: number
  coupons: Coupon[]
}

export interface Category {
  id: number
  name: string
  description?: string
  icon?: string
}

export interface AvailabilityBlock {
  id: string
  startDate: string
  endDate: string
  reason: string
}

export interface AvailabilityOffer {
  id: string
  startDate: string
  endDate: string
  discount: number
  discountType: 'percentage' | 'amount'
  color: string
}

export interface TourStep8Availability {
  requireAvailability: boolean
  start: string
  end: string
  // No end date at all: the calendar never closes and the tour is left out of
  // the expiry warnings sent to reservations.
  neverExpires: boolean
  activeDays: number[]
  specialDays: string[]
  blocks?: AvailabilityBlock[]
  offers?: AvailabilityOffer[]
}

const LANGS = ['en', 'es', 'fr', 'de', 'pt', 'it'] as const

/** Empty per-language SEO/content maps — the shape state() starts from, so a
 *  fetch can return to it instead of layering one tour on top of another. */
const emptyContentSEO = (): Record<string, any> =>
  Object.fromEntries(LANGS.map(code => [code, {
    title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '',
    youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [],
    bookingTexts: {
      policyDescription: STANDARD_POLICY[code] || '',
      policyDescriptionCustom: '', meetingPointDescription: '',
      pickupLocationDescription: '', dropoffLocationDescription: '',
    },
  }]))

const emptyDetailedContent = (): Record<string, any> =>
  Object.fromEntries(LANGS.map(code => [code, {
    itinerary: [], itineraryText: '', inclusions: '', exclusions: '',
    detailedDescription: '', recommendations: '', thingsToBring: '',
    generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [],
  }]))

/**
 * Canonical JSON for comparing a draft slice against the live one.
 *
 * Object key order is NOT stable across the round trip — the draft comes back
 * from the API and gets spread into fresh objects — so a plain JSON.stringify
 * reports differences that do not exist. Keys are sorted; array order is left
 * alone because in this data it carries meaning (itinerary steps, gallery
 * order). null and '' are folded together: the wizard writes '' into fields the
 * API returns as null, and treating that as an edit would flag every language.
 */
const canonical = (value: any): string => {
  const walk = (v: any): any => {
    if (v === null || v === undefined || v === '') return null
    if (Array.isArray(v)) return v.map(walk)
    if (typeof v === 'object') {
      return Object.keys(v).sort().reduce((acc: Record<string, any>, k) => {
        acc[k] = walk(v[k])
        return acc
      }, {})
    }
    return v
  }
  return JSON.stringify(walk(value))
}

const sameContent = (a: any, b: any): boolean => canonical(a) === canonical(b)

/**
 * La pagina del asistente registra aqui su pregunta de "tienes cambios sin
 * guardar". Vive fuera del state porque es una funcion, no datos: no debe
 * viajar en instantaneas ni en el borrador.
 */
let stepGuard: ((step: number) => void) | null = null
export function setWizardStepGuard(fn: ((step: number) => void) | null) {
  stepGuard = fn
}

export const useTourWizardStore = defineStore('tourWizard', {
  state: () => ({
    currentStep: 1,
    // 9 steps: Info, SEO, Detalle, Precios, Multimedia, Reservas, Categorías, Calendario, Revisión final

    totalSteps: 8,
    isDirty: false,
    loading: false,
    autosaveEnabled: true,
    autosaving: false,
    autosaveError: null as string | null,
    lastSavedAt: null as number | null,
    tourId: null as string | null,
    // El negocio es primero-español: sin ?lang en la URL el editor abría en
    // inglés, evaluaba la calidad sobre la traducción EN (casi siempre vacía)
    // y mostraba avisos SEO de un idioma que no es el principal.
    currentLanguage: 'es',
    availableLanguages: [] as any[],

    // --- Draft buffer (published tours only) ------------------------------
    // On a published tour every save used to go live immediately. Now edits
    // park in tour_revisions until the operator publishes them explicitly.
    // Status as PERSISTED, independent of what the form currently shows. The
    // routing decision (live row vs draft buffer) must not follow an unsaved
    // edit: if it read basicInfo.status, typing "draft" into the status field
    // would flip autosave back to writing through and unpublish the tour live.
    persistedStatus: 'draft' as string,
    hasPendingDraft: false,
    // Version of the draft this session last read. Sent back on save so the
    // API can reject a write built on a copy someone else has since replaced.
    draftVersion: null as number | null,
    // Set when that rejection happens: autosave stops and the operator has to
    // choose, because silently retrying is exactly the overwrite we're avoiding.
    draftConflict: null as { message: string; updatedByName: string | null; updatedAt: string | null; otherTab: boolean } | null,
    draftUpdatedAt: null as string | null,
    draftUpdatedByName: null as string | null,
    draftError: null as string | null,
    // The tour exactly as the public sees it, captured at the end of
    // fetchTourData() and BEFORE any parked draft is overlaid.
    //
    // "Cambios sin publicar" on its own cannot say WHERE the changes are — the
    // draft is one opaque snapshot of the whole wizard — so an operator facing
    // six languages and nine steps had nowhere to start looking. Keeping the
    // live copy is what makes the difference computable, and computing it
    // against CURRENT state (rather than once, at load) means the answer stays
    // right as they keep editing instead of going stale after the first save.
    liveSnapshot: null as Record<string, any> | null,
    // Signature of the age bands as last written, so a tour save only touches
    // that global table when a name or range actually changed.
    ageStagesSaved: '' as string,
    publishing: false,
    
    // Step 1 Data ...
    basicInfo: {
      title: '',
      subtitle: '',
      code: '',
      serviceType: 'tour',
      targetAudience: 'all',
      difficulty: 'easy',
      capacityMin: 1,
      capacityMax: 99,
      duration: 1,
      durationUnit: 'hours',
      durationDays: 0,
      durationHours: 1,
      durationMinutes: 0,
      startTime: '08:00',
      startTimes: [{ time: '08:00', duration: 1, durationUnit: 'hours', days: 0, hours: 1, minutes: 0 }],
      timezone: 'America/Lima',
      nearestCity: '',
      nearestAirport: '',
      cityId: undefined,
      languageId: undefined,
      status: 'draft'
    } as TourStep1,

    // Step 2 Data (Multi-language)
    contentSEO: {
      en: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.en, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      es: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.es, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      fr: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.fr, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      de: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.de, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      pt: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.pt, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      it: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.it, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
    } as Record<string, any>,

    // Step 3 Data (Multi-language)
    detailedContent: {
      en: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
      es: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
      fr: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
      de: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
      pt: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
      it: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] },
    } as Record<string, TourStep3Content>,

    commercialRules: {
      paymentMethod: 'all',
      ageStages: [
        {
          id: '1',
          description: 'Adulto',
          minAge: 16,
          maxAge: 99,
          active: true,
          nationalities: [
            {
              id: 'n1',
              nationalityId: 'general',
              ageMin: 16,
              ageMax: 99,
              ranges: [
                { id: 'r1', from: 1, to: 1, price: 49 },
                { id: 'r2', from: 2, to: 20, price: 45 }
              ]
            }
          ]
        },
        { 
          id: '2', 
          description: 'Niño', 
          minAge: 3, 
          maxAge: 11, 
          active: false, 
          nationalities: [] 
        }
      ],
      taxPercentage: 10,
      advancePaymentPercentage: 20,
      coupons: []
    } as TourStep4,
    multimedia: {
      youtubeUrl: '',
      galleryLayout: 'featured',
      images: []
    } as TourStep5Multimedia,
    tempImages: [] as {
      filename: string,
      path: string,
      original_path?: string,
      crop_data?: { coordinates: { left: number; top: number; width: number; height: number }; aspect: number | null } | null,
      alt_text?: string,
      title_text?: string,
      description?: string,
      is_primary?: boolean,
      order?: number
    }[],

    bookingOptions: {
      policyType: 'standard',
      policyDescription: '',
      policyDescriptionCustom: '',
      bookingAnticipationQuantity: 24,
      bookingAnticipationUnit: 'hours',
      dataRequirementType: 'leader',
      operationalInfoRequired: [],
      personalInfoRequired: ['first_name', 'last_name', 'email', 'phone_whatsapp'],
      enableMeetingPoint: false,
      meetingPointDescription: '',
      meetingPointLat: null,
      meetingPointLng: null,
      meetingPoints: [] as MeetingPoint[],
      enableHotelPickup: false,
      pickupLocationDescription: '',
      pickupRadiusKm: 5,
      pickupCenterLat: null,
      pickupCenterLng: null,
      pickupAreaType: 'radius' as 'radius' | 'polygon',
      pickupArea: [] as Array<Array<{ lat: number; lng: number }>>,
      dropoffLocationDescription: '',
      guideType: 'live_guide',
      guideLanguages: [1, 2], // Spanish, English
      parentTourId: null,
      optionLabel: '',
      optionColor: 'blue',
      childCount: 0,
    } as TourStep6,

    selectedCategories: [] as number[],
    selectedTags: [] as number[],
    categories: [] as Category[],

    availability: {
      requireAvailability: false,
      start: new Date().toISOString().split('T')[0],
      end: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0],
      neverExpires: false,
      activeDays: [1, 2, 3, 4, 5, 6, 0], // Monday to Sunday
      specialDays: [],
      blocks: [],
      offers: []
    } as TourStep8Availability,
  }),

  getters: {
    /**
     * Languages whose content differs from what the public sees, as uppercase
     * codes. Empty when nothing is parked, when the live snapshot is missing
     * (a brand-new tour), or once the changes are published.
     *
     * Whole per-language slices are compared, not a hand-picked field or two:
     * a language stays silent only when genuinely nothing in it differs, so
     * the absence of a mark can be trusted.
     */
    draftChangedLanguages(): string[] {
      const live = (this as any).liveSnapshot
      if (!live) return []
      const out: string[] = []
      for (const code of LANGS) {
        const seoChanged = !sameContent((this as any).contentSEO?.[code], live.contentSEO?.[code])
        const detailChanged = !sameContent((this as any).detailedContent?.[code], live.detailedContent?.[code])
        if (seoChanged || detailChanged) out.push(code.toUpperCase())
      }
      return out
    },

    /** Non-language parts of the tour that differ from the published version. */
    draftChangedSections(): string[] {
      const live = (this as any).liveSnapshot
      if (!live) return []
      const pairs: [string, string][] = [
        ['basicInfo', 'Información básica'],
        ['commercialRules', 'Precios'],
        ['multimedia', 'Multimedia'],
        ['bookingOptions', 'Reservas'],
        ['availability', 'Calendario'],
        ['selectedCategories', 'Categorías'],
        ['selectedTags', 'Etiquetas'],
      ]
      return pairs
        .filter(([key]) => !sameContent((this as any)[key], live[key]))
        .map(([, label]) => label)
    },

    // Per-step "essential data present?" status, surfaced as a dot on the
    // stepper so the operator sees at a glance which core steps still need
    // work — without opening each one. Only the publish-critical steps
    // (1 Info, 2 Contenido, 3 SEO, 4 Precios, 5 Multimedia) are evaluated;
    // 6-9 are optional config and stay neutral. Mirrors the field checks the
    // InsightsSidebar quality score uses, kept here so both read one source.
    stepStatuses(): Record<number, 'complete' | 'empty'> {
      const lang = (this as any).currentLanguage || 'es'
      const seo: any = (this as any).contentSEO?.[lang] || {}
      const detailed: any = (this as any).detailedContent?.[lang] || {}
      const images: any[] = (this as any).multimedia?.images || []
      const stages: any[] = (this as any).commercialRules?.ageStages || []
      const bi: any = (this as any).basicInfo || {}

      // Rich-text fields can be an empty "<p></p>"; strip tags before testing.
      const hasRich = (v: any) => !!String(v || '').replace(/<[^>]*>/g, '').trim()
      const hasText = (v: any) => !!String(v || '').trim()

      const hasDuration =
        (Number(bi.durationDays) || 0) + (Number(bi.durationHours) || 0) + (Number(bi.durationMinutes) || 0) > 0 ||
        Number(bi.duration) > 0

      const step1 = hasText(bi.title) && (bi.cityId || hasText(bi.nearestCity)) && hasDuration
      const step2 = hasRich(detailed.detailedDescription) || hasRich(detailed.itineraryText)
      const step3 = hasText(seo.shortDescription) || hasText(seo.metaTitle) || hasText(seo.metaDescription)
      const step4 = stages.some((s: any) =>
        s.active && (s.nationalities || []).some((n: any) => (n.ranges || []).some((r: any) => Number(r.price) > 0))
      )
      const step5 = images.length >= 1

      return {
        1: step1 ? 'complete' : 'empty',
        2: step2 ? 'complete' : 'empty',
        3: step3 ? 'complete' : 'empty',
        4: step4 ? 'complete' : 'empty',
        5: step5 ? 'complete' : 'empty',
      }
    },
  },

  actions: {
    setTourId(id: string) {
      this.tourId = id
    },

    // Wipe wizard state so a "new tour" route doesn't inherit the previously
    // edited tour. Pinia Options stores ship $reset() which re-runs state();
    // also purge the per-route localStorage keys we set for /new.
    resetWizard() {
      this.$reset()
      if (typeof window !== 'undefined') {
        try {
          for (const k of Object.keys(localStorage)) {
            if (k.startsWith('wizard:focus:new:') || k === 'wizard:lastStep:new') {
              localStorage.removeItem(k)
            }
          }
        } catch { /* quota or storage disabled — non-fatal */ }
      }
    },
    
    nextStep() {
      this.goToStep(this.currentStep + 1)
    },

    prevStep() {
      this.goToStep(this.currentStep - 1)
    },

    /**
     * El unico sitio por el que se cambia de paso.
     *
     * La guardia vivia en la pagina y envolvia los botones que conocia — y la
     * barra de pasos del navbar llamaba aqui directamente, asi que saltar del 2
     * al 3 desde arriba se la saltaba entera: el operador solo veia el toast del
     * autoguardado. Puesta aqui, cualquier camino pasa por ella, incluidos los
     * que alguien anada manana.
     */
    goToStep(step: number, opciones: { sinGuardia?: boolean } = {}) {
      if (step < 1 || step > this.totalSteps || step === this.currentStep) return
      if (stepGuard && !opciones.sinGuardia) {
        stepGuard(step)
        return
      }
      this.currentStep = step
    },

    updateBasicInfo(data: Partial<TourStep1>) {
      this.basicInfo = { ...this.basicInfo, ...data }
      this.isDirty = true
    },

    // Read meeting_points from the API payload. Falls back to the legacy single
    // meeting_point_lat/lng + per-language description so tours saved before this
    // feature existed still surface as a single-item list. Must run AFTER
    // this.contentSEO is populated, since per-language descriptions live in
    // contentSEO[code].bookingTexts.meetingPointDescription.
    normalizeMeetingPoints(data: any): MeetingPoint[] {
      // Backend can send the JSON column as an array OR as a JSON-encoded string
      // (depends on whether the Eloquent cast fired). Handle both.
      let raw: any[] = []
      if (Array.isArray(data?.meeting_points)) {
        raw = data.meeting_points
      } else if (typeof data?.meeting_points === 'string' && data.meeting_points.trim()) {
        try { raw = JSON.parse(data.meeting_points) } catch { raw = [] }
        if (!Array.isArray(raw)) raw = []
      }

      if (raw.length > 0) {
        return raw.map((p: any, i: number) => ({
          id: String(p.id ?? `mp-${Date.now()}-${i}`),
          lat: p.lat != null && p.lat !== '' ? Number(p.lat) : null,
          lng: p.lng != null && p.lng !== '' ? Number(p.lng) : null,
          descriptions: (p.descriptions && typeof p.descriptions === 'object') ? { ...p.descriptions } : {},
          image: p.image || null,
        }))
      }

      // Legacy fallback — collect everything we know about the single point.
      const lat = data?.meeting_point_lat != null && data.meeting_point_lat !== ''
        ? Number(data.meeting_point_lat) : null
      const lng = data?.meeting_point_lng != null && data.meeting_point_lng !== ''
        ? Number(data.meeting_point_lng) : null

      // Pull per-language descriptions from contentSEO (already populated by the
      // translations loop earlier in fetchTourData).
      const descriptions: Record<string, string> = {}
      for (const code of Object.keys(this.contentSEO)) {
        const text = (this.contentSEO[code]?.bookingTexts?.meetingPointDescription || '').trim()
        if (text) descriptions[code] = text
      }
      // Spanish-only legacy field, in case nothing made it into translations.
      if (Object.keys(descriptions).length === 0 && data?.meeting_point_description) {
        descriptions.es = data.meeting_point_description
      }

      const hasCoords = lat != null && lng != null && !Number.isNaN(lat) && !Number.isNaN(lng)
      const hasDescription = Object.keys(descriptions).length > 0
      const wasEnabled = data?.enable_meeting_point === true || data?.enable_meeting_point === 1

      // Surface a point if ANY signal exists — coords, description, or the legacy
      // "enabled" flag. Otherwise return an empty list so the user starts clean.
      if (!hasCoords && !hasDescription && !wasEnabled) return []

      return [{
        id: `mp-${Date.now()}-0`,
        lat: hasCoords ? lat : null,
        lng: hasCoords ? lng : null,
        descriptions,
      }]
    },

    /**
     * Replace the wizard's built-in age bands with the ones actually stored.
     *
     * They used to be hardcoded here ("Adulto 16-99", "Niño 3-11") while the
     * database held its own, global, set. The screen therefore showed ranges
     * nobody could rely on, and the public site — which reads the stored rows —
     * printed something else entirely. Read the real thing, so what an operator
     * sees is what a traveller gets.
     *
     * Best-effort: an older API has no such endpoint, and failing to read the
     * bands must not stop a tour from opening.
     */
    async loadAgeStages(): Promise<void> {
      const auth = useAuthStore()
      if (!auth.token) return
      const config = useRuntimeConfig()
      try {
        const res: any = await $fetch(`${config.public.apiUrl}/admin/age-stages`, {
          headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
        })
        const rows: any[] = res?.data || []
        if (!rows.length) return
        this.commercialRules.ageStages = rows.map((row) => {
          const existing = this.commercialRules.ageStages.find(s => String(s.id) === String(row.id))
          return {
            // Keep whatever prices are already loaded for this band; only the
            // identity and the range come from the server.
            ...(existing || { active: false, nationalities: [] }),
            id: String(row.id),
            description: row.description,
            minAge: row.min_age,
            maxAge: row.max_age,
            editable: row.editable !== false,
          } as any
        })
        // Baseline: what we just read is what the server already has, so a
        // tour saved without touching the bands writes nothing to them.
        this.ageStagesSaved = JSON.stringify(
          rows.map((r: any) => ({
            id: Number(r.id),
            description: String(r.description || '').slice(0, 45),
            min_age: Number(r.min_age),
            max_age: Number(r.max_age),
          }))
        )
      } catch {
        // Keep the built-in defaults; the pricing rows below still load.
      }
    },

    /**
     * Write the age bands back. Global data, so this deliberately runs only
     * when a range or a name actually changed — a tour save should not touch
     * the whole catalogue's bands just by being a tour save.
     */
    async saveAgeStages(): Promise<void> {
      const auth = useAuthStore()
      if (!auth.token) return
      // Every band is sent: `editable` is the seeder's marker on base rows,
      // not a write lock, and filtering on it skipped the only rows that exist.
      const stages = this.commercialRules.ageStages || []
      if (!stages.length) return

      const payload = stages.map((s: any) => ({
        id: Number(s.id),
        description: String(s.description || '').slice(0, 45),
        min_age: Number(s.minAge),
        max_age: Number(s.maxAge),
      })).filter(s => Number.isFinite(s.id) && Number.isFinite(s.min_age) && Number.isFinite(s.max_age))
      if (!payload.length) return

      const firma = JSON.stringify(payload)
      if (firma === this.ageStagesSaved) return

      const config = useRuntimeConfig()
      try {
        await $fetch(`${config.public.apiUrl}/admin/age-stages`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          body: { stages: payload },
        })
        this.ageStagesSaved = firma
      } catch {
        // An older API has no endpoint here. Don't fail the tour save over it.
      }
    },

    async fetchTourData(id: string) {
      this.loading = true
      console.log(`[Store] Fetching data for tour ID: ${id}...`)
      try {
        await this.loadAgeStages()
        const config = useRuntimeConfig()
        const defaultApiUrl = config.public.apiUrl
        // Authenticated: /tours/{id} 404s for anonymous callers when the tour
        // isn't published, so without the token the wizard could no longer
        // open a draft — which is most of what it's used for.
        const auth = useAuthStore()
        const response: any = await $fetch(`${defaultApiUrl}/tours/${id}`, {
          headers: auth.token ? { Authorization: `Bearer ${auth.token}` } : undefined,
        })
        
        if (response.success && response.data) {
          const data = response.data
          console.log('[Store] Tour data received:', data)
          
          // Map Step 1: Basic Info
          this.basicInfo = {
            title: data.title || '',
            subtitle: data.short_description || '',
            code: data.code || '',
            serviceType: data.service_type || 'tour',
            targetAudience: data.target_audience || 'all',
            difficulty: data.difficulty || 'easy',
            capacityMin: 1,
            capacityMax: data.max_capacity || 99,
            duration: data.duration_quantity || (data.duration_days > 0 ? data.duration_days : (data.duration_hours || 1)),
            durationUnit: data.duration_unit || (data.duration_days > 0 ? 'days' : 'hours'),
            startTime: data.departure_time || '08:00',
            startTimes: (() => {
              // Helper: derive {days, hours, minutes} from any duration shape an
              // older record might have stored (numeric+unit, or already split).
              const splitDuration = (item: any) => {
                if (item && (item.days != null || item.hours != null || item.minutes != null)) {
                  return {
                    days: Number(item.days) || 0,
                    hours: Number(item.hours) || 0,
                    minutes: Number(item.minutes) || 0,
                  }
                }
                const qty = Number(item?.duration ?? item?.duration_quantity ?? data.duration_quantity ?? 0)
                const unit = item?.durationUnit || item?.duration_unit || data.duration_unit || 'hours'
                if (unit === 'days') return { days: Math.floor(qty), hours: 0, minutes: 0 }
                if (unit === 'minutes') return { days: 0, hours: Math.floor(qty / 60), minutes: qty % 60 }
                // hours (allow fractional like 2.5 -> 2h 30m)
                const h = Math.floor(qty)
                return { days: 0, hours: h, minutes: Math.round((qty - h) * 60) }
              }

              const arr = data.departure_times
              if (Array.isArray(arr) && arr.length > 0) {
                return arr.map((item: any) => {
                  const time = typeof item === 'string'
                    ? item.substring(0, 5)
                    : ((item?.time || '').substring(0, 5) || '08:00')
                  const parts = splitDuration(typeof item === 'string' ? null : item)
                  return {
                    time,
                    duration: Number((typeof item === 'object' ? item?.duration : null) ?? data.duration_quantity ?? 1),
                    durationUnit: ((typeof item === 'object' ? item?.durationUnit || item?.duration_unit : null) || data.duration_unit || 'hours') as 'hours' | 'days' | 'minutes',
                    days: parts.days,
                    hours: parts.hours,
                    minutes: parts.minutes,
                  }
                }).filter((x: any) => x.time)
              }
              const days = Number(data.duration_days) || 0
              const hours = Number(data.duration_hours) || 0
              const minutes = Number(data.duration_minutes) || 0
              return [{
                time: data.departure_time || '08:00',
                duration: data.duration_quantity || hours || days || 1,
                durationUnit: (data.duration_unit || (days > 0 ? 'days' : 'hours')) as 'hours' | 'days' | 'minutes',
                days, hours, minutes,
              }]
            })(),
            timezone: data.timezone || 'America/Lima',
            nearestCity: data.city?.name || '',
            nearestAirport: '',
            cityId: data.city?.id,
            citySlug: data.city?.slug || '',
            languageId: data.primary_language?.id,
            status: data.status || 'draft',
            durationDays: Number(data.duration_days) || 0,
            durationHours: Number(data.duration_hours) || (Number(data.duration_quantity) && data.duration_unit === 'hours' ? Number(data.duration_quantity) : 0),
            durationMinutes: Number(data.duration_minutes) || 0,
          }
          
          // Map Step 2: Content & SEO (translations)
          //
          // Wipe the per-language maps first. This loop only ever overwrote the
          // languages the FETCHED tour has, and left every other language
          // holding whatever the previously opened tour had put there. Moving
          // from one tour to another without a full page reload therefore
          // carried the first tour's EN/FR/DE/PT/IT into the second one, and
          // the next save wrote them as that tour's own translations — which is
          // how tour ES235 (Uyuni, $365) came to publish ES007 (Uros, $39) in
          // five languages, at the Uyuni price, live.
          //
          // resetWizard() did not cover this: it runs for /new only, and this is
          // the path between two existing tours.
          this.contentSEO = emptyContentSEO()
          this.detailedContent = emptyDetailedContent()
          // Same reason, and it must not survive a fetch that throws later:
          // a snapshot left over from the previous tour would have the editor
          // reporting another tour's differences against this one.
          this.liveSnapshot = null

          if (data.translations && Array.isArray(data.translations)) {
            data.translations.forEach((trans: any) => {
              const langCode = trans.language?.code?.toLowerCase() || 'es'
              // Initialize language key if it doesn't exist
              if (!this.contentSEO[langCode]) {
                this.contentSEO[langCode] = { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', keywords: [], faqs: [], mediaTexts: [] }
              }
              if (!this.detailedContent[langCode]) {
                this.detailedContent[langCode] = { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', customSections: [], mapPoints: [] }
              }
              if (this.contentSEO[langCode]) {
                this.contentSEO[langCode] = {
                  title: trans.h1_title || '',
                  shortDescription: trans.short_description || '',
                  metaTitle: trans.meta_title || '',
                  metaDescription: trans.meta_description || '',
                  slug: trans.slug || '',
                  keywords: trans.keywords || [],
                  faqs: trans.faqs || [],
                  youtubeUrl: trans.youtube_url || '',
                  mediaTexts: trans.media_texts || [],
                  bookingTexts: trans.booking_texts || {
                    policyDescription: '',
                    policyDescriptionCustom: '',
                    meetingPointDescription: '',
                    pickupLocationDescription: '',
                    dropoffLocationDescription: ''
                  }
                }
                
                if (this.detailedContent[langCode]) {
                   this.detailedContent[langCode] = {
                     ...this.detailedContent[langCode],
                     detailedDescription: trans.long_description || '',
                     itineraryText: trans.itinerary || '',
                     inclusions: trans.what_includes || '',
                     exclusions: trans.what_not_includes || '',
                     recommendations: trans.recommendations || '',
                     thingsToBring: trans.what_to_bring || '',
                     generalPolicies: trans.policies || '',
                     cancellationPolicy: trans.cancellation_policy || '',
                     customSections: Array.isArray(trans.custom_sections)
                       ? trans.custom_sections.map((s: any, i: number) => ({
                           id: s.id || `cs-${i}-${Date.now()}`,
                           title: s.title || '',
                           content: s.content || '',
                         }))
                       : [],
                     // Map points are the same for all languages
                     mapPoints: (data.map_points || []).map((point: any) => ({
                       id: point.id,
                       name: point.name,
                       description: point.description,
                       coordinates: point.coordinates,
                       type: point.type,
                       order: point.order
                     }))
                   }
                }
                
                // If this is the primary language, sync it back to basicInfo
                if (trans.language_id === data.primary_language_id) {
                  this.basicInfo.title = trans.h1_title || ''
                  this.basicInfo.subtitle = trans.short_description || ''
                }
              }
            })
          }

          // Map Step 4: Commercial Rules / Pricing
          // Match prices by age_stage_id to the admin slot with the same id.
          // Slot labels/ranges are hardcoded defaults and are NOT overridden by
          // the age_stages row — some legacy rows have description/ranges that
          // don't match the data actually stored under them.
          if (data.price_details && data.price_details.length > 0) {
            const grouped: Record<string, any[]> = {}
            data.price_details.forEach((p: any) => {
              const stageId = String(p.age_stage_id || p.age_stage?.id || '')
              if (!stageId) return
              if (!grouped[stageId]) grouped[stageId] = []
              grouped[stageId].push(p)
            })

            this.commercialRules.ageStages = this.commercialRules.ageStages.map(stage => {
              const prices = grouped[stage.id]
              if (prices && prices.length > 0) {
                stage.active = true
                stage.nationalities = [{
                  id: 'n1',
                  nationalityId: 'general',
                  ageMin: stage.minAge,
                  ageMax: stage.maxAge,
                  ranges: prices.map((p: any, i: number) => ({
                    id: `r${i + 1}`,
                    from: p.min_quantity || 1,
                    to: p.max_quantity || 20,
                    price: parseFloat(p.price || p.amount || 0)
                  }))
                }]
              }
              return stage
            })
          }
          this.commercialRules.taxPercentage = data.tax_percentage ?? this.commercialRules.taxPercentage
          this.commercialRules.advancePaymentPercentage = data.advance_payment_percentage ?? this.commercialRules.advancePaymentPercentage
          // The step 4 selector was never read back nor sent: it always
          // opened on "Todos los métodos" and choosing "Solo PayPal" changed
          // nothing a traveller would ever see. The column, the validation and
          // the API field existed the whole time — only the wizard was silent.
          this.commercialRules.paymentMethod = data.payment_method ?? this.commercialRules.paymentMethod

          // Map Step 5: Multimedia.
          // Normalize gallery_layout to one of the 4 admin options. Legacy /
          // frontend values (hero_mosaic, video_image, …) don't match any
          // admin card, which left the selector with no highlighted option
          // and the badge showing a raw value. Map the known frontend names
          // to their closest admin equivalent; anything unknown → featured.
          const LAYOUT_ALIASES: Record<string, string> = {
            hero_mosaic: 'featured',
            video_image: 'featured',
            video_horizontal_mosaic: 'mosaic_vertical',
          }
          const VALID_LAYOUTS = ['featured', 'grid', 'slider', 'mosaic_vertical']
          const rawLayout = data.gallery_layout || 'featured'
          const normalizedLayout = VALID_LAYOUTS.includes(rawLayout)
            ? rawLayout
            : (LAYOUT_ALIASES[rawLayout] || 'featured')

          this.multimedia = {
            youtubeUrl: data.youtube_url || '',
            galleryLayout: normalizedLayout as any,
            images: (data.media_gallery || []).map((img: any) => ({
              id: img.id,
              url: img.url,
              // Full image the crop was derived from — loaded into the cropper on
              // re-edit so the saved crop box can be restored. Falls back to url.
              originalUrl: img.original_url || img.url,
              cropData: img.crop_data || null,
              filename: '', // backend doesn't seem to store original filename
              size: 0,
              altText: img.alt_text || '',
              titleText: img.title_text || '',
              description: img.description || '',
              isPrimary: img.is_primary ?? false,
              order: img.order || 0
            }))
          }

          // Map Step 6: Booking Options
          this.bookingOptions = {
            policyType: data.policy_type || 'standard',
            policyDescription: data.policy_description || '',
            policyDescriptionCustom: data.policy_description_custom || '',
            bookingAnticipationQuantity: data.booking_anticipation_quantity || 24,
            bookingAnticipationUnit: data.booking_anticipation_unit || 'hours',
            dataRequirementType: data.data_requirement === 2 ? 'all' : 'leader',
            operationalInfoRequired: data.operational_info_required || [],
            personalInfoRequired: data.personal_info_required || ['first_name', 'last_name', 'email', 'phone_whatsapp'],
            enableMeetingPoint: data.enable_meeting_point || false,
            enableHotelPickup: data.enable_hotel_pickup || false,
            meetingPointDescription: data.meeting_point_description || '',
            meetingPointLat: data.meeting_point_lat ? Number(data.meeting_point_lat) : null,
            meetingPointLng: data.meeting_point_lng ? Number(data.meeting_point_lng) : null,
            pickupLocationDescription: data.pickup_location_description || '',
            pickupCenterLat: data.pickup_center_lat ? Number(data.pickup_center_lat) : null,
            pickupCenterLng: data.pickup_center_lng ? Number(data.pickup_center_lng) : null,
            pickupRadiusKm: data.pickup_radius_km ? Number(data.pickup_radius_km) : 5,
            pickupAreaType: data.pickup_area_type === 'polygon' ? 'polygon' : 'radius',
            // The API may return a single ring; normalise to a list of rings.
            pickupArea: (() => {
              const a = data.pickup_area
              if (!Array.isArray(a) || !a.length) return []
              return Array.isArray(a[0]) ? a : [a]
            })(),
            dropoffLocationDescription: data.dropoff_location_description || '',
            guideType: data.guide_type || 'live_guide',
            guideLanguages: (data.guide_languages || [1, 2]).map((id: any) => Number(id)),
            meetingPoints: this.normalizeMeetingPoints(data),
            parentTourId: data.parent_tour_id ? Number(data.parent_tour_id) : null,
            optionLabel: data.option_label || '',
            optionColor: data.option_color || 'blue',
            // The public `options` array is the variant group (this tour + its
            // children) when it's a parent. Use it to recover parent mode even
            // when option_label hasn't been set yet.
            childCount: Array.isArray(data.options) ? Math.max(0, data.options.length - 1) : 0,
          }

          // Map Step 7 Categories
          this.selectedCategories = data.categories?.map((c: any) => c.id) || []
          this.selectedTags = data.tags?.map((t: any) => t.id) || []

          // Map Step 8 Availability. require_availability is independent of the
          // availability_data blob (a tour can require an inquiry without a
          // configured calendar), so read it unconditionally — otherwise the
          // switch resets to off on reload for tours with no calendar data.
          this.availability.requireAvailability = data.require_availability || false
          if (data.availability_data) {
             const defaultEnd = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]
             this.availability = {
               requireAvailability: data.require_availability || false,
               start: data.availability_data.start || new Date().toISOString().split('T')[0],
               neverExpires: !!data.availability_data.neverExpires,
               // Don't substitute the +1y default when the tour never expires:
               // that is what would silently give it an end date again.
               end: data.availability_data.neverExpires
                 ? ''
                 : (data.availability_data.end || defaultEnd),
               activeDays: (data.availability_data.activeDays || [1, 2, 3, 4, 5, 6, 0]).map((d: any) => Number(d)),
               specialDays: data.availability_data.specialDays || [],
               blocks: data.availability_data.blocks || [],
               offers: data.availability_data.offers || []
             }
          }

          // What the API says is live right now. Drives whether autosave writes
          // through or parks in the draft buffer, so it must come from the
          // server response and not from the (editable) form field.
          this.persistedStatus = data.status || 'draft'

          // Abrir en el idioma primario del tour. edit.vue re-aplica ?lang
          // después de este fetch, así que un enlace explícito sigue ganando.
          const primaryCode = (data.primary_language?.code || '').toLowerCase()
          if (primaryCode) this.currentLanguage = primaryCode

          // Important: Reset isDirty after initial load
          nextTick(() => {
            this.isDirty = false
          })
          
          console.log('[Store] Updated basicInfo:', this.basicInfo)
          console.log('[Store] Updated contentSEO:', this.contentSEO)
          console.log('[Store] Updated multimedia:', this.multimedia)

          // Freeze what the public currently sees, before loadDraft() overlays
          // any parked edits. Everything the operator changes from here on is
          // measured against this, which is what lets the editor answer "what
          // exactly is unpublished, and in which language?" — a question the
          // draft itself cannot answer, being one opaque snapshot.
          this.liveSnapshot = JSON.parse(JSON.stringify(this.draftSlices()))
        }
      } catch (error) {
        console.error('[Store] Error fetching tour data:', error)
      } finally {
        this.loading = false
      }
    },

    async saveCurrentProgress(options: { silent?: boolean } = {}) {
      const silent = options.silent === true
      const auth = useAuthStore()
      if (!auth.token) {
        if (!silent) notifyError('Sesión expirada', 'Por favor vuelve a iniciar sesión.')
        return
      }

      // Validate price ranges — prevent saving when ranges overlap, duplicate
      // or have invalid bounds inside the same nationality.
      const priceErrors: string[] = []
      for (const stage of this.commercialRules.ageStages) {
        if (!stage.active) continue
        for (const nat of stage.nationalities) {
          for (let i = 0; i < nat.ranges.length; i++) {
            const r = nat.ranges[i]
            const from = Number(r.from)
            const to = Number(r.to)
            if (!Number.isFinite(from) || from < 1 || !Number.isFinite(to) || to < 1) {
              priceErrors.push(`${stage.description}: rango con valores inválidos (${r.from}-${r.to})`)
              continue
            }
            if (from > to) {
              priceErrors.push(`${stage.description}: ${from}-${to} → "Desde" mayor que "Hasta"`)
              continue
            }
            for (let j = 0; j < i; j++) {
              const o = nat.ranges[j]
              const ofrom = Number(o.from)
              const oto = Number(o.to)
              if (!Number.isFinite(ofrom) || !Number.isFinite(oto)) continue
              if (from <= oto && to >= ofrom) {
                priceErrors.push(`${stage.description}: rango ${from}-${to} se solapa con ${ofrom}-${oto}`)
                break
              }
            }
          }
        }
      }
      if (priceErrors.length) {
        if (!silent) notifyError('No se puede guardar: conflictos en los rangos de precios', priceErrors.join('\n'))
        return
      }

      this.loading = true
      const config = useRuntimeConfig()
      const defaultApiUrl = config.public.apiUrl
      const isNew = !this.tourId || this.tourId === 'new'
      // cPanel/mod_security blocks PUT on shared hosts — the backend route
      // accepts both PUT and POST, so we always use POST for updates.
      const method = 'POST'
      const url = isNew ? `${defaultApiUrl}/admin/tours` : `${defaultApiUrl}/admin/tours/${this.tourId}`

      // Build translations object keyed by language_id (as the backend expects)
      const langId = this.basicInfo.languageId || 1
      const titleSlug = (this.basicInfo.title || 'tour')
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Remove accents
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .substring(0, 150)

      // Prepare payload with Step 1 data
      const payload: Record<string, any> = {
        primary_language_id: langId,
        city_id: this.basicInfo.cityId,
        city_name: this.basicInfo.nearestCity,
        city_latitude: this.basicInfo.cityCoordinates?.lat || null,
        city_longitude: this.basicInfo.cityCoordinates?.lng || null,
        code: this.basicInfo.code,
        service_type: this.basicInfo.serviceType,
        status: this.basicInfo.status || 'draft',
        difficulty: this.basicInfo.difficulty,
        target_audience: this.basicInfo.targetAudience,
        capacity: this.basicInfo.capacityMax,
        departure_time: (this.basicInfo.startTimes?.[0]?.time || this.basicInfo.startTime || '08:00').substring(0, 5),
        departure_times: (this.basicInfo.startTimes || [])
          .map((item: any) => {
            let days = Number(item?.days) || 0
            let hours = Number(item?.hours) || 0
            const minutes = Number(item?.minutes) || 0
            // Legacy-imported multi-day tours carry hours >= 24 (e.g. 48h for
            // a 2D1N) which the API rejects (hours must be 0-23). Normalize.
            if (hours >= 24) {
              days += Math.floor(hours / 24)
              hours = hours % 24
            }
            // Keep legacy duration/duration_unit in sync (some readers still use them)
            const legacyQty = days > 0 ? days : (hours > 0 ? hours : minutes)
            const legacyUnit = days > 0 ? 'days' : (hours > 0 ? 'hours' : 'minutes')
            return {
              time: (item?.time || '').substring(0, 5),
              duration: Number(item?.duration) || legacyQty || 1,
              duration_unit: item?.durationUnit || legacyUnit || 'hours',
              days, hours, minutes,
            }
          })
          .filter((item: any) => /^\d{2}:\d{2}$/.test(item.time)),
        timezone: this.basicInfo.timezone,
        duration_quantity: this.basicInfo.duration,
        duration_unit: this.basicInfo.durationUnit,
        // Same hours>=24 normalization as departure_times (legacy 2D tours
        // stored 48h and the API's 0-23 rule rejected every save).
        ...(() => {
          let d = Number(this.basicInfo.durationDays) || 0
          let h = Number(this.basicInfo.durationHours) || 0
          if (h >= 24) {
            d += Math.floor(h / 24)
            h = h % 24
          }
          return { duration_days: d, duration_hours: h }
        })(),
        duration_minutes: Number(this.basicInfo.durationMinutes) || 0,
        youtube_url: this.multimedia.youtubeUrl,
        gallery_layout: this.multimedia.galleryLayout,
        media_gallery: this.multimedia.images.map((img: any) => ({
          id: img.id,
          url: img.url, // Base64 or existing URL
          alt_text: img.altText,
          title_text: img.titleText,
          description: img.description,
          is_primary: img.isPrimary,
          order: img.order,
          // Non-destructive crop: persist the crop box, and when re-cropped this
          // session, the freshly derived temp file to swap in as the display.
          crop_data: img.cropData ?? null,
          new_display_path: img.newDisplayPath ?? null,
        })).filter(img => typeof img.id === 'number'), // Only send existing images (IDs are numbers from DB, newly uploaded tempImages have UUIDs)
        temp_images: this.tempImages,

        // Step 4 Commercial Rules / Pricing
        tax_percentage: this.commercialRules.taxPercentage,
        advance_payment_percentage: this.commercialRules.advancePaymentPercentage,
        payment_method: this.commercialRules.paymentMethod || 'all',
        prices: this.commercialRules.ageStages.reduce((acc: Record<string, any>, stage) => {
          acc[stage.id] = {
            active: stage.active,
            ranges: stage.nationalities.flatMap(nat =>
              nat.ranges.map(range => ({
                from: range.from,
                to: range.to,
                price: range.price
              }))
            )
          }
          return acc
        }, {}),

        // Step 6 Booking Options
        policy_type: this.bookingOptions.policyType,
        policy_description: this.bookingOptions.policyDescription,
        policy_description_custom: this.bookingOptions.policyDescriptionCustom,
        booking_anticipation_quantity: this.bookingOptions.bookingAnticipationQuantity,
        booking_anticipation_unit: this.bookingOptions.bookingAnticipationUnit,
        data_requirement: this.bookingOptions.dataRequirementType === 'all' ? 2 : 1, // mapping leader to 1, all to 2
        operational_info_required: this.bookingOptions.operationalInfoRequired,
        personal_info_required: this.bookingOptions.personalInfoRequired,
        enable_meeting_point: this.bookingOptions.enableMeetingPoint,
        enable_hotel_pickup: this.bookingOptions.enableHotelPickup,
        meeting_point_description: this.bookingOptions.meetingPointDescription,
        // Keep legacy lat/lng synced with the first multi-point entry so older
        // consumers (frontend page, emails) still get a valid coordinate.
        meeting_point_lat: this.bookingOptions.meetingPoints[0]?.lat ?? this.bookingOptions.meetingPointLat,
        meeting_point_lng: this.bookingOptions.meetingPoints[0]?.lng ?? this.bookingOptions.meetingPointLng,
        meeting_points: this.bookingOptions.meetingPoints,
        pickup_location_description: this.bookingOptions.pickupLocationDescription,
        pickup_center_lat: this.bookingOptions.pickupCenterLat,
        pickup_center_lng: this.bookingOptions.pickupCenterLng,
        pickup_radius_km: this.bookingOptions.pickupRadiusKm,
        pickup_area_type: this.bookingOptions.pickupAreaType || 'radius',
        pickup_area: this.bookingOptions.pickupAreaType === 'polygon'
          ? (this.bookingOptions.pickupArea || [])
          : null,
        dropoff_location_description: this.bookingOptions.dropoffLocationDescription,
        guide_type: this.bookingOptions.guideType,
        guide_languages: this.bookingOptions.guideLanguages,

        // Variant grouping. Send NULL when not linked so the backend stores
        // null instead of an empty string (DB column is FK, must be int or null).
        parent_tour_id: this.bookingOptions.parentTourId || null,
        option_label: this.bookingOptions.optionLabel || null,
        option_color: this.bookingOptions.optionColor || null,

        // Step 7 Categories
        categories: this.selectedCategories,
        tags: this.selectedTags,

        // Map points from Step 3 (use current language or first available)
        map_points: this.detailedContent[this.currentLanguage]?.mapPoints ||
                   this.detailedContent.es?.mapPoints ||
                   this.detailedContent.en?.mapPoints ||
                   [],

        // Step 8 Availability
        require_availability: this.availability.requireAvailability,
        availability_data: {
          start: this.availability.start,
          // Blank when the tour never expires, so every consumer that already
          // treats "no end" as unlimited keeps working untouched.
          end: this.availability.neverExpires ? '' : this.availability.end,
          neverExpires: !!this.availability.neverExpires,
          activeDays: this.availability.activeDays,
          specialDays: this.availability.specialDays,
          blocks: this.availability.blocks || [],
          offers: this.availability.offers || []
        },

        translations: {} as Record<number, any>
      }


      // 1. First, set the primary translation from basicInfo (Step 1)
      const primaryLangId = this.basicInfo.languageId || 1
      const primarySlug = (this.basicInfo.title || 'tour')
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .substring(0, 150)

      payload.translations[primaryLangId] = {
        language_id: primaryLangId,
        h1_title: this.basicInfo.title,
        short_description: this.basicInfo.subtitle,
        slug: primarySlug
      }

      // Keep legacy bookingTexts.meetingPointDescription in sync with the first
      // multi-point entry, so the public frontend & booking emails (which still
      // read the legacy single field) keep working.
      const firstPoint = this.bookingOptions.meetingPoints[0]
      if (firstPoint) {
        for (const code of Object.keys(this.contentSEO)) {
          const seo = this.contentSEO[code]
          if (!seo) continue
          if (!seo.bookingTexts) {
            seo.bookingTexts = { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' }
          }
          seo.bookingTexts.meetingPointDescription = firstPoint.descriptions[code] || ''
        }
      }

      // 2. Then, override/add with Step 2 data (Content & SEO)
      // We need to map our language codes (en, es, fr, de) to their IDs
      Object.entries(this.contentSEO).forEach(([code, seoData]) => {
        // Skip if everything is empty
        if (!seoData.title && !seoData.shortDescription && !seoData.slug) return

        const langId = this.availableLanguages.find(l => l.code.toLowerCase() === code)?.id
        if (langId) {
          // Generate a fallback slug from the title of this language
          const fallbackSlug = (seoData.title || 'tour')
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .substring(0, 150)

          // Merge with detailed content (Step 3)
          const detailed = this.detailedContent[code]
          
          // Merge with existing if it's the primary language, or create new
          payload.translations[langId] = {
            ...(payload.translations[langId] || { language_id: langId }),
            h1_title: seoData.title,
            short_description: seoData.shortDescription,
            meta_title: seoData.metaTitle,
            meta_description: seoData.metaDescription,
            slug: seoData.slug || payload.translations[langId]?.slug || fallbackSlug,
            keywords: (seoData.keywords || []).filter((k: any) => (k?.keyword || '').trim()),
            faqs: (seoData.faqs || []).filter((f: any) => (f?.question || '').trim() && (f?.answer || '').trim()),

            // Step 3 data
            long_description: detailed?.detailedDescription,
            itinerary: detailed?.itineraryText,
            what_includes: detailed?.inclusions,
            what_not_includes: detailed?.exclusions,
            recommendations: detailed?.recommendations,
            what_to_bring: detailed?.thingsToBring,
            policies: detailed?.generalPolicies,
            cancellation_policy: detailed?.cancellationPolicy,
            custom_sections: (detailed?.customSections || [])
              .filter((s: any) => (s.title || '').trim() || (s.content || '').trim())
              .map((s: any) => ({ title: s.title || '', content: s.content || '' })),

            // Per-language multimedia
            youtube_url: seoData.youtubeUrl || '',
            media_texts: seoData.mediaTexts || [],
            booking_texts: (() => { console.log('[SAVE] lang='+code+' bookingTexts=', JSON.stringify(seoData.bookingTexts)); return seoData.bookingTexts || {} })()
          }
        }
      })

      try {
        const response: any = await $fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${auth.token}`,
            'Accept': 'application/json'
          },
          body: payload
        })

        if (response.success) {
          // The age bands live in their own global table, so the tour payload
          // cannot carry them. Persist them alongside — otherwise "Edad mín /
          // Edad máx" stays a field that looks editable and silently isn't.
          await this.saveAgeStages()
          this.isDirty = false
          this.lastSavedAt = Date.now()
          this.autosaveError = null
          // The write went through, so this status is now the live one — a tour
          // published just now must route its next autosave to the draft buffer.
          this.persistedStatus = (payload.status as string) || 'draft'
          if (isNew) {
            this.tourId = response.data.id
            // Redirect or update route if needed
          }
          console.log('[Store] Tour saved successfully')
        }
      } catch (error: any) {
        const validationErrors = error.data?.errors
        const messages = validationErrors
          ? Object.entries(validationErrors)
              .map(([field, msgs]: [string, any]) => `  ${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
              .join('\n')
          : (error.data?.message || error.message || 'Error desconocido')

        console.error('[Store] Error saving tour (422):\n' + messages)
        console.error('[Store] Full payload sent:', JSON.stringify(payload, null, 2))
        console.error('[Store] Full response:', JSON.stringify(error.data, null, 2))

        if (validationErrors) {
          if (!silent) alert('Errores de validación:\n' + messages)
        } else {
          if (!silent) notifyError('Error al guardar', messages)
        }
      } finally {
        this.loading = false
      }
    },

    // --- Draft buffer ------------------------------------------------------
    // A published tour is live: writing to it publishes instantly. So on a
    // published tour autosave parks the wizard state in tour_revisions and the
    // live row is only touched when the operator hits "Publicar cambios".
    //
    // The draft stores WIZARD STATE, not the API payload, so publishing is just
    // "state is already loaded → run the normal save". One write path, no
    // second apply implementation to keep in sync with the backend.

    /**
     * True when edits must not reach the public site without confirmation.
     * Reads the persisted status, never the form's — see persistedStatus.
     */
    isLiveTour(): boolean {
      return this.persistedStatus === 'published'
    },

    /**
     * The editable state of a tour. Mirrors the slices the edit page
     * deep-watches for dirty tracking. `categories` (the available-category
     * catalog) is deliberately excluded — it's reference data refetched on
     * load, not the operator's work; the selection lives in selectedCategories.
     */
    draftSlices(): Record<string, any> {
      return {
        basicInfo: this.basicInfo,
        contentSEO: this.contentSEO,
        detailedContent: this.detailedContent,
        commercialRules: this.commercialRules,
        multimedia: this.multimedia,
        bookingOptions: this.bookingOptions,
        selectedCategories: this.selectedCategories,
        selectedTags: this.selectedTags,
        availability: this.availability,
      }
    },

    /**
     * The one entry point every "save this work" action should use (Ctrl+S,
     * per-step save buttons, autosave). Routes to the draft buffer on a live
     * tour so no manual save can publish by accident; writes through otherwise.
     * Publishing is deliberately NOT here — that's publishDraft().
     */
    async saveWork(options: { silent?: boolean } = {}): Promise<boolean> {
      if (this.isLiveTour()) return await this.saveDraft()
      await this.saveCurrentProgress(options)
      return !this.isDirty
    },

    /**
     * Stable id for THIS browser tab. The whole team shares one admin login,
     * so user attribution can't distinguish editors — the tab id is what lets
     * a 409 tell "my own save racing itself" (adopt silently) apart from
     * "another tab or computer" (real conflict).
     */
    tabId(): string {
      if (typeof window === 'undefined') return ''
      let id = sessionStorage.getItem('wizard_tab_id')
      if (!id) {
        id = (crypto?.randomUUID?.() || Math.random().toString(36).slice(2)).slice(0, 36)
        sessionStorage.setItem('wizard_tab_id', id)
      }
      return id
    },

    /** Park the current state as a pending draft. Does not touch the tour row. */
    async saveDraft(retryAfterSelfConflict = true): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      const auth = useAuthStore()
      if (!auth.token) return false

      // A conflict is unresolved until the operator acts on it; saving again
      // would be the blind overwrite the check exists to prevent.
      if (this.draftConflict) return false

      const config = useRuntimeConfig()
      try {
        const res: any = await $fetch(`${config.public.apiUrl}/admin/tours/${this.tourId}/revision`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          body: {
            payload: this.draftSlices(),
            schema_version: DRAFT_SCHEMA_VERSION,
            base_version: this.draftVersion,
            tab_id: this.tabId(),
            // Travels with the draft so the tour LIST can name what is
            // pending. It has to be computed here: this is the only place
            // holding the live tour and the draft in the same shape, and the
            // list has neither.
            changed_languages: this.draftChangedLanguages,
            changed_sections: this.draftChangedSections,
          },
        })
        if (!res?.success) throw new Error(res?.message || 'Respuesta inesperada del servidor')

        this.isDirty = false
        this.lastSavedAt = Date.now()
        this.hasPendingDraft = true
        this.draftVersion = res.data?.version ?? this.draftVersion
        this.draftUpdatedAt = res.data?.updated_at || new Date().toISOString()
        this.draftError = null
        return true
      } catch (e: any) {
        // 409: the stored draft moved past our base_version.
        if (e?.status === 409 || e?.response?.status === 409) {
          const data = e?.data ?? e?.response?._data
          const winnerTab = data?.data?.updated_by_tab ?? null

          // Our OWN tab won the race (double-fire, reconnect, replay): nothing
          // to decide — adopt the server's version and save again on top of
          // our own work instead of bothering the operator.
          if (retryAfterSelfConflict && winnerTab && winnerTab === this.tabId()) {
            this.draftVersion = data?.data?.version ?? this.draftVersion
            return await this.saveDraft(false)
          }

          this.draftConflict = {
            message: data?.message || 'Se guardaron cambios en este tour desde otro lugar.',
            updatedByName: data?.data?.updated_by_name ?? null,
            updatedAt: data?.data?.updated_at ?? null,
            // Shared account: a different (or missing) tab id means another
            // tab or another computer — the UI words it that way instead of
            // the useless "Admin guardó cambios".
            otherTab: true,
          }
          this.draftError = this.draftConflict.message
          this.isDirty = true
          return false
        }
        // A 404 here means the backend has no revision endpoint yet (frontend
        // deployed ahead of the API). Deliberately NOT falling back to a live
        // write — that would publish the very edits the buffer exists to hold.
        // Say so plainly instead, because the edits are currently unsaved.
        if (e?.status === 404 || e?.response?.status === 404) {
          this.draftError = 'El servidor no admite borradores todavía. Tus cambios NO se han guardado — no cierres esta pestaña y avisa al equipo técnico.'
        } else {
          this.draftError = e?.data?.message || e?.message || 'Error al guardar el borrador'
        }
        // Still dirty: nothing was persisted.
        this.isDirty = true
        return false
      }
    },

    /**
     * Tell the server which languages/sections the parked draft changes.
     *
     * Only for drafts saved before the wizard reported this. It writes nothing
     * but those two lists — not the payload, not the version, not the
     * timestamp — so it cannot disturb the parked work or look like an edit.
     */
    async backfillDraftSummary(): Promise<void> {
      if (!this.tourId || this.tourId === 'new') return
      const auth = useAuthStore()
      if (!auth.token) return

      const config = useRuntimeConfig()
      try {
        await $fetch(`${config.public.apiUrl}/admin/tours/${this.tourId}/revision/summary`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          body: {
            changed_languages: this.draftChangedLanguages,
            changed_sections: this.draftChangedSections,
          },
        })
      } catch {
        // Deliberately silent. An older API returns 404 here, and a listing
        // badge that stays vague is not worth an error in the operator's face.
      }
    },

    /**
     * Load the pending draft over the freshly-fetched live data. Call AFTER
     * fetchTourData — it overwrites the slices it covers.
     */
    async loadDraft(): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      const auth = useAuthStore()
      if (!auth.token) return false

      const config = useRuntimeConfig()
      try {
        const res: any = await $fetch(
          `${config.public.apiUrl}/admin/tours/${this.tourId}/revision`,
          {
            method: 'GET',
            params: { schema_version: DRAFT_SCHEMA_VERSION },
            headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          }
        )

        const draft = res?.data
        if (!draft?.payload) {
          this.hasPendingDraft = false
          this.draftVersion = null
          this.draftUpdatedAt = null
          this.draftUpdatedByName = null
          return false
        }

        const p = draft.payload

        // Assign per slice rather than wholesale, so a draft written before a
        // slice existed restores what it has and leaves the rest as loaded.
        if (p.basicInfo) this.basicInfo = { ...this.basicInfo, ...p.basicInfo }
        if (p.contentSEO) this.contentSEO = { ...this.contentSEO, ...p.contentSEO }
        if (p.detailedContent) this.detailedContent = { ...this.detailedContent, ...p.detailedContent }
        if (p.commercialRules) this.commercialRules = { ...this.commercialRules, ...p.commercialRules }
        if (p.multimedia) this.multimedia = { ...this.multimedia, ...p.multimedia }
        if (p.bookingOptions) this.bookingOptions = { ...this.bookingOptions, ...p.bookingOptions }
        if (p.availability) this.availability = { ...this.availability, ...p.availability }
        if (Array.isArray(p.selectedCategories)) this.selectedCategories = p.selectedCategories
        if (Array.isArray(p.selectedTags)) this.selectedTags = p.selectedTags

        this.hasPendingDraft = true
        this.draftVersion = draft.version ?? null
        this.draftUpdatedAt = draft.updated_at || null
        this.draftUpdatedByName = draft.updated_by_name || null
        // Now in step with the server, so any earlier conflict is resolved.
        this.draftConflict = null
        this.draftError = null
        // Restoring a saved draft is not an unsaved edit.
        this.isDirty = false

        // Drafts parked before the wizard started reporting what they change
        // carry no summary, so the tour list cannot name their languages. We
        // have just worked it out to render this screen; hand it over so the
        // list stops saying "algo cambió" about work we can already describe.
        // Fire and forget: it is an annotation, and failing to write it must
        // not disturb an editing session.
        if (!Array.isArray(draft.changed_languages)) {
          this.backfillDraftSummary()
        }
        return true
      } catch (e: any) {
        // Reading a draft is best-effort: no endpoint (404) simply means this
        // deployment has no drafts, which must not block opening the editor.
        // saveDraft() is where a missing endpoint gets shouted about.
        if (e?.status === 404 || e?.response?.status === 404) {
          this.hasPendingDraft = false
          return false
        }
        this.draftError = e?.data?.message || e?.message || 'Error al cargar el borrador'
        return false
      }
    },

    /**
     * Silent catch-up for CLEAN tabs. Called when the tab regains focus: if
     * this tab has no unsaved edits and no open conflict, and the server draft
     * moved ahead (someone saved from another tab/computer), just load the
     * newer draft — asking "which version do you want" when you have nothing
     * to lose is the annoying version of a conflict.
     * Returns true when a newer draft was loaded.
     */
    async refreshDraftIfClean(): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      if (this.isDirty || this.draftConflict) return false
      const auth = useAuthStore()
      if (!auth.token) return false

      const config = useRuntimeConfig()
      try {
        const res: any = await $fetch(
          `${config.public.apiUrl}/admin/tours/${this.tourId}/revision`,
          {
            method: 'GET',
            params: { schema_version: DRAFT_SCHEMA_VERSION },
            headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          }
        )
        const server = res?.data
        if (!server?.payload) return false
        const serverVersion = Number(server.version ?? 0)
        const mine = Number(this.draftVersion ?? 0)
        if (serverVersion <= mine) return false
        // Re-check: an autosave may have started while we were fetching.
        if (this.isDirty || this.draftConflict) return false
        return await this.loadDraft()
      } catch {
        return false
      }
    },

    /**
     * Conflict resolution — take THEIRS. Reloads the tour and the newer draft,
     * losing whatever this tab had unsaved. The operator chooses this knowing
     * that, which is the whole point of asking instead of picking a winner.
     */
    async resolveConflictWithTheirs(): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      this.draftConflict = null
      await this.fetchTourData(this.tourId)
      await this.loadDraft()
      return true
    },

    /**
     * Conflict resolution — take MINE. Re-reads the server's version purely to
     * satisfy the check, then writes this tab's state over it. Deliberately
     * explicit: this is the overwrite the version check exists to prevent, so
     * it only ever happens because someone asked for it.
     */
    async resolveConflictWithMine(): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      const auth = useAuthStore()
      if (!auth.token) return false

      const config = useRuntimeConfig()
      try {
        const res: any = await $fetch(
          `${config.public.apiUrl}/admin/tours/${this.tourId}/revision`,
          {
            method: 'GET',
            params: { schema_version: DRAFT_SCHEMA_VERSION },
            headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
          }
        )
        this.draftVersion = res?.data?.version ?? null
      } catch {
        this.draftVersion = null
      }

      this.draftConflict = null
      return await this.saveDraft()
    },

    /** Throw the draft away. The caller reloads the live tour afterwards. */
    async discardDraft(): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false
      const auth = useAuthStore()
      if (!auth.token) return false

      const config = useRuntimeConfig()
      try {
        // POST, not DELETE: mod_security on the cPanel host blocks DELETE.
        await $fetch(`${config.public.apiUrl}/admin/tours/${this.tourId}/revision/delete`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${auth.token}`, Accept: 'application/json' },
        })
        this.hasPendingDraft = false
        this.draftVersion = null
        this.draftUpdatedAt = null
        this.draftUpdatedByName = null
        this.draftConflict = null
        this.draftError = null
        return true
      } catch (e: any) {
        this.draftError = e?.data?.message || e?.message || 'Error al descartar el borrador'
        return false
      }
    },

    /**
     * Push the pending draft live: the state is already in the store, so this
     * is the ordinary save plus dropping the now-applied draft. `status` lets
     * the caller publish a draft tour for the first time as well.
     */
    async publishDraft(status?: 'draft' | 'published' | 'archived'): Promise<boolean> {
      if (!this.tourId || this.tourId === 'new') return false

      this.publishing = true
      try {
        if (status) this.basicInfo.status = status
        await this.saveCurrentProgress()
        // saveCurrentProgress clears isDirty only when the API accepted it.
        if (this.isDirty) return false
        // Applied — the parked copy is now redundant. A failure here leaves a
        // stale draft, which would silently re-apply on next load, so surface it.
        const dropped = await this.discardDraft()
        if (!dropped) {
          this.draftError = 'Los cambios se publicaron, pero el borrador no se pudo borrar. Recarga la página.'
          return false
        }
        // What is on screen is now what the public sees, so it becomes the new
        // baseline — otherwise the editor would keep reporting the changes it
        // just published as still pending.
        this.liveSnapshot = JSON.parse(JSON.stringify(this.draftSlices()))
        return true
      } finally {
        this.publishing = false
      }
    },

    /**
     * Silent autosave. On a published tour it targets the draft buffer instead
     * of the live row — same debounce, same dirty gating, but nothing reaches
     * the public site until the operator publishes.
     */
    async autosave() {
      if (!this.autosaveEnabled) return
      if (!this.isDirty) return
      if (this.loading || this.autosaving) return
      // Don't autosave brand-new tours — the first save creates the row.
      if (!this.tourId || this.tourId === 'new') return

      const auth = useAuthStore()
      if (!auth.token) return

      this.autosaving = true
      this.autosaveError = null
      try {
        if (this.isLiveTour()) {
          const ok = await this.saveDraft()
          if (!ok) this.autosaveError = this.draftError || 'Error al guardar el borrador'
          return
        }

        const wasDirty = this.isDirty
        await this.saveCurrentProgress({ silent: true })
        if (wasDirty && this.isDirty) {
          // Save aborted (validation conflict) — surface it inline.
          this.autosaveError = 'Hay conflictos sin resolver. Guardado manual requerido.'
        }
      } catch (e: any) {
        this.autosaveError = e?.message || 'Error al autoguardar'
      } finally {
        this.autosaving = false
      }
    }
  }
})
