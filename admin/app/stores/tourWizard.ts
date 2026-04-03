import { defineStore } from 'pinia'
import { nextTick } from 'vue'
import { useAuthStore } from '~/stores/auth'

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
  durationUnit: 'hours' | 'days'
  startTime: string
  timezone: string
  nearestCity: string
  nearestAirport: string
  cityId?: number
  cityCoordinates?: {
    lat: number
    lng: number
  }
  languageId?: number
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
    totalSteps: 8,
    isDirty: false,
    loading: false,
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
      startTime: '08:00',
      timezone: 'America/Lima',
      nearestCity: '',
      nearestAirport: '',
      cityId: undefined,
      languageId: undefined
    } as TourStep1,

    // Step 2 Data (Multi-language)
    contentSEO: {
      en: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      es: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      fr: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      de: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      pt: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
      it: { title: '', shortDescription: '', metaTitle: '', metaDescription: '', slug: '', youtubeUrl: '', mediaTexts: [], bookingTexts: { policyDescription: '', policyDescriptionCustom: '', meetingPointDescription: '', pickupLocationDescription: '', dropoffLocationDescription: '' } },
    } as Record<string, any>,

    // Step 3 Data (Multi-language)
    detailedContent: {
      en: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
      es: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
      fr: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
      de: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
      pt: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
      it: { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] },
    } as Record<string, TourStep3Content>,

    commercialRules: {
      paymentMethod: 'all',
      ageStages: [
        { 
          id: '1', 
          description: 'Adulto', 
          minAge: 18, 
          maxAge: 99, 
          active: true, 
          nationalities: [
            { 
              id: 'n1', 
              nationalityId: 'general', 
              ageMin: 18, 
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
            duration: data.duration_days > 0 ? data.duration_days : (data.duration_hours || 1),
            durationUnit: data.duration_days > 0 ? 'days' : 'hours',
            startTime: data.departure_time || '08:00',
            timezone: data.timezone || 'America/Lima',
            nearestCity: data.city?.name || '',
            nearestAirport: '',
            cityId: data.city?.id,
            languageId: data.primary_language?.id
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
                this.detailedContent[langCode] = { itinerary: [], itineraryText: '', inclusions: '', exclusions: '', detailedDescription: '', recommendations: '', thingsToBring: '', generalPolicies: '', cancellationPolicy: '', mapPoints: [] }
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
            guideLanguages: (data.guide_languages || [1, 2]).map((id: any) => Number(id))
          }

          // Map Step 7 Categories
          this.selectedCategories = data.categories?.map((c: any) => c.id) || []

          // Map Step 8 Availability
          if (data.availability_data) {
             const defaultEnd = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0]
             this.availability = {
               requireAvailability: data.require_availability || false,
               start: data.availability_data.start || new Date().toISOString().split('T')[0],
               end: data.availability_data.end || defaultEnd,
               activeDays: data.availability_data.activeDays || [1, 2, 3, 4, 5, 6, 0],
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

    async saveCurrentProgress() {
      const auth = useAuthStore()
      if (!auth.token) {
        alert('Sesión expirada. Por favor vuelve a loguearte.')
        return
      }

      this.loading = true
      const config = useRuntimeConfig()
      const defaultApiUrl = config.public.apiUrl
      const isNew = !this.tourId || this.tourId === 'new'
      const method = isNew ? 'POST' : 'PUT'
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
        difficulty: this.basicInfo.difficulty,
        target_audience: this.basicInfo.targetAudience,
        capacity: this.basicInfo.capacityMax,
        departure_time: (this.basicInfo.startTime || '08:00').substring(0, 5),
        timezone: this.basicInfo.timezone,
        duration_quantity: this.basicInfo.duration,
        duration_unit: this.basicInfo.durationUnit,
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
        meeting_point_lat: this.bookingOptions.meetingPointLat,
        meeting_point_lng: this.bookingOptions.meetingPointLng,
        pickup_location_description: this.bookingOptions.pickupLocationDescription,
        pickup_center_lat: this.bookingOptions.pickupCenterLat,
        pickup_center_lng: this.bookingOptions.pickupCenterLng,
        pickup_radius_km: this.bookingOptions.pickupRadiusKm,
        dropoff_location_description: this.bookingOptions.dropoffLocationDescription,
        guide_type: this.bookingOptions.guideType,
        guide_languages: this.bookingOptions.guideLanguages,

        // Step 7 Categories
        categories: this.selectedCategories,

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

            // Per-language multimedia
            youtube_url: seoData.youtubeUrl || '',
            media_texts: seoData.mediaTexts || [],
            booking_texts: seoData.bookingTexts || {}
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
          if (isNew) {
            this.tourId = response.data.id
            // Redirect or update route if needed
          }
          console.log('[Store] Tour saved successfully')
        }
      } catch (error: any) {
        console.error('[Store] Error saving tour:', error)
        console.error('[Store] Error response data:', error.data)
        console.error('[Store] Validation errors:', error.data?.errors)
        const validationErrors = error.data?.errors
        if (validationErrors) {
          const messages = Object.entries(validationErrors)
            .map(([field, msgs]: [string, any]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
            .join('\n')
          alert(`Errores de validación:\n${messages}`)
        } else {
          const errorMsg = error.data?.message || 'Error al conectar con el servidor'
          alert(`Error al guardar: ${errorMsg}`)
        }
      } finally {
        this.loading = false
      }
    }
  }
})
