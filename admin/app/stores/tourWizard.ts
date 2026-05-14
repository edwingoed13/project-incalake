import { defineStore } from 'pinia'
import { nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'

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

export interface TourStep2Content {
  title: string
  shortDescription: string
  metaTitle: string
  metaDescription: string
  slug: string
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
  dropoffLocationDescription: string
  guideType: 'live_guide' | 'audio_guide' | 'informative_brochures' | 'no_guide' | 'none'
  guideLanguages: number[]
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
  activeDays: number[]
  specialDays: string[]
  blocks?: AvailabilityBlock[]
  offers?: AvailabilityOffer[]
}

export const useTourWizardStore = defineStore('tourWizard', {
  state: () => ({
    currentStep: 1,
    // 9 steps: Info, SEO, Detalle, Precios, Multimedia, Reservas, Categorías, Calendario, Revisión final

    totalSteps: 9,
    isDirty: false,
    loading: false,
    autosaveEnabled: true,
    autosaving: false,
    autosaveError: null as string | null,
    lastSavedAt: null as number | null,
    tourId: null as string | null,
    currentLanguage: 'en',
    availableLanguages: [] as any[],
    
    // Step 1 Data ...
    basicInfo: {
      title: '',
      subtitle: '',
      code: '',
      serviceType: 'tour',
      targetAudience: 'all',
      difficulty: 'easy',
      capacityMin: 1,
      capacityMax: 20,
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
      en: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.en, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      es: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.es, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      fr: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.fr, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      de: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.de, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      pt: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.pt, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      it: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: STANDARD_POLICY.it, policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
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
      dropoffLocationDescription: '',
      guideType: 'live_guide',
      guideLanguages: [1, 2] // Spanish, English
    } as TourStep6,

    selectedCategories: [] as number[],
    selectedTags: [] as number[],
    categories: [] as Category[],

    availability: {
      requireAvailability: false,
      start: new Date().toISOString().split('T')[0],
      end: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0],
      activeDays: [1, 2, 3, 4, 5, 6, 0], // Monday to Sunday
      specialDays: [],
      blocks: [],
      offers: []
    } as TourStep8Availability,
  }),

  actions: {
    setTourId(id: string) {
      this.tourId = id
    },
    
    nextStep() {
      if (this.currentStep < this.totalSteps) {
        this.currentStep++
      }
    },
    
    prevStep() {
      if (this.currentStep > 1) {
        this.currentStep--
      }
    },

    goToStep(step: number) {
      if (step >= 1 && step <= this.totalSteps) {
        this.currentStep = step
      }
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

    async fetchTourData(id: string) {
      this.loading = true
      console.log(`[Store] Fetching data for tour ID: ${id}...`)
      try {
        const config = useRuntimeConfig()
        const defaultApiUrl = config.public.apiUrl
        const response: any = await $fetch(`${defaultApiUrl}/tours/${id}`)
        
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
            capacityMax: data.max_capacity || 20,
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
          if (data.translations && Array.isArray(data.translations)) {
            data.translations.forEach((trans: any) => {
              const langCode = trans.language?.code?.toLowerCase() || 'es'
              // Initialize language key if it doesn't exist
              if (!this.contentSEO[langCode]) {
                this.contentSEO[langCode] = { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [] }
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

          // Map Step 5: Multimedia
          this.multimedia = {
            youtubeUrl: data.youtube_url || '',
            galleryLayout: data.gallery_layout || 'featured',
            images: (data.media_gallery || []).map((img: any) => ({
              id: img.id,
              url: img.url,
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
            dropoffLocationDescription: data.dropoff_location_description || '',
            guideType: data.guide_type || 'live_guide',
            guideLanguages: (data.guide_languages || [1, 2]).map((id: any) => Number(id)),
            meetingPoints: this.normalizeMeetingPoints(data),
          }

          // Map Step 7 Categories
          this.selectedCategories = data.categories?.map((c: any) => c.id) || []
          this.selectedTags = data.tags?.map((t: any) => t.id) || []

          // Map Step 8 Availability
          if (data.availability_data) {
             const defaultEnd = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]
             this.availability = {
               requireAvailability: data.require_availability || false,
               start: data.availability_data.start || new Date().toISOString().split('T')[0],
               end: data.availability_data.end || defaultEnd,
               activeDays: (data.availability_data.activeDays || [1, 2, 3, 4, 5, 6, 0]).map((d: any) => Number(d)),
               specialDays: data.availability_data.specialDays || [],
               blocks: data.availability_data.blocks || [],
               offers: data.availability_data.offers || []
             }
          }

          // Important: Reset isDirty after initial load
          nextTick(() => {
            this.isDirty = false
          })
          
          console.log('[Store] Updated basicInfo:', this.basicInfo)
          console.log('[Store] Updated contentSEO:', this.contentSEO)
          console.log('[Store] Updated multimedia:', this.multimedia)
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
        if (!silent) alert('Sesión expirada. Por favor vuelve a loguearte.')
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
        if (!silent) alert('No se puede guardar — hay conflictos en los rangos de precios:\n\n' + priceErrors.join('\n'))
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
            const days = Number(item?.days) || 0
            const hours = Number(item?.hours) || 0
            const minutes = Number(item?.minutes) || 0
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
        duration_days: Number(this.basicInfo.durationDays) || 0,
        duration_hours: Number(this.basicInfo.durationHours) || 0,
        duration_minutes: Number(this.basicInfo.durationMinutes) || 0,
        youtube_url: this.multimedia.youtubeUrl,
        gallery_layout: this.multimedia.galleryLayout,
        media_gallery: this.multimedia.images.map(img => ({
          id: img.id,
          url: img.url, // Base64 or existing URL
          alt_text: img.altText,
          title_text: img.titleText,
          description: img.description,
          is_primary: img.isPrimary,
          order: img.order
        })).filter(img => typeof img.id === 'number'), // Only send existing images (IDs are numbers from DB, newly uploaded tempImages have UUIDs)
        temp_images: this.tempImages,

        // Step 4 Commercial Rules / Pricing
        tax_percentage: this.commercialRules.taxPercentage,
        advance_payment_percentage: this.commercialRules.advancePaymentPercentage,
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
        dropoff_location_description: this.bookingOptions.dropoffLocationDescription,
        guide_type: this.bookingOptions.guideType,
        guide_languages: this.bookingOptions.guideLanguages,

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
          end: this.availability.end,
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
          this.isDirty = false
          this.lastSavedAt = Date.now()
          this.autosaveError = null
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
          if (!silent) alert('Error al guardar: ' + messages)
        }
      } finally {
        this.loading = false
      }
    },

    /**
     * Silent autosave — same payload as saveCurrentProgress but never alerts the
     * user. Skipped on new tours (the first save must be manual to create the
     * record), when there's nothing dirty, or while a save is already running.
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
