import { defineStore } from 'pinia'

export interface CheckoutData {
  tour_id: number
  tour_date: string
  tour_time?: string
  adults: number
  children: number
  infants: number
  customer_name: string
  customer_email: string
  customer_phone: string
  customer_country: string
  customer_notes: string
  pickup_location?: string
  payment_method: 'culqi' | 'paypal'
}

export interface Booking {
  id: number
  booking_code: string
  tour: {
    id: number
    title: string
    slug: string
  }
  tour_date: string
  tour_time?: string
  customer: {
    name: string
    email: string
    phone: string
    country: string
    notes: string
  }
  participants: {
    adults: number
    children: number
    infants: number
    total: number
  }
  pricing: {
    currency: string
    subtotal: number
    discount: number
    total: number
  }
  payment: {
    method: string
    status: string
    id?: string
    paid_at?: string
  }
  status: string
  pickup_location?: string
  created_at: string
  updated_at: string
}

export const useBookingStore = defineStore('booking', {
  state: () => ({
    currentBooking: null as Booking | null,
    loading: false,
    error: null as string | null,
    paymentConfig: null as any,
    // Form data for checkout
    checkoutForm: {
      customer_name: '',
      customer_email: '',
      customer_phone: '',
      customer_country: '',
      customer_notes: '',
      pickup_location: '',
      payment_method: 'culqi' as 'culqi' | 'paypal'
    }
  }),

  getters: {
    hasBooking: (state) => state.currentBooking !== null,
    isLoading: (state) => state.loading,
    bookingCode: (state) => state.currentBooking?.booking_code || '',
    totalAmount: (state) => state.currentBooking?.pricing.total || 0
  },

  actions: {
    async createBooking(data: CheckoutData) {
      this.loading = true
      this.error = null

      try {
        const { api } = useApi()
        const response = await api('/bookings', {
          method: 'POST',
          body: data
        })

        this.currentBooking = response.booking
        this.paymentConfig = response.payment_config

        return response
      } catch (err: any) {
        this.error = err.response?.data?.message || err.message || 'Error creating booking'
        throw err
      } finally {
        this.loading = false
      }
    },

    async confirmCulqiPayment(bookingId: number, token: string, paymentData: any) {
      this.loading = true
      this.error = null

      try {
        const { api } = useApi()
        const response = await api(`/bookings/${bookingId}/payment/culqi`, {
          method: 'POST',
          body: {
            token,
            payment_data: paymentData
          }
        })

        this.currentBooking = response.booking
        return response
      } catch (err: any) {
        this.error = err.response?.data?.message || err.message || 'Error processing payment'
        throw err
      } finally {
        this.loading = false
      }
    },

    async confirmPayPalPayment(bookingId: number, orderId: string, paymentData: any) {
      this.loading = true
      this.error = null

      try {
        const { api } = useApi()
        const response = await api(`/bookings/${bookingId}/payment/paypal`, {
          method: 'POST',
          body: {
            order_id: orderId,
            payment_data: paymentData
          }
        })

        this.currentBooking = response.booking
        return response
      } catch (err: any) {
        this.error = err.response?.data?.message || err.message || 'Error processing payment'
        throw err
      } finally {
        this.loading = false
      }
    },

    async getBooking(bookingCode: string, email?: string) {
      this.loading = true
      this.error = null

      try {
        const { api } = useApi()
        const params = email ? `?email=${encodeURIComponent(email)}` : ''
        const response = await api(`/bookings/${bookingCode}${params}`)

        this.currentBooking = response.booking
        return response
      } catch (err: any) {
        this.error = err.response?.data?.message || err.message || 'Booking not found'
        throw err
      } finally {
        this.loading = false
      }
    },

    async getBookingByToken(token: string) {
      this.loading = true
      this.error = null

      try {
        const { api } = useApi()
        const response = await api(`/bookings/confirmation/${token}`)

        this.currentBooking = response.booking
        return response
      } catch (err: any) {
        this.error = err.response?.data?.message || err.message || 'Invalid or expired confirmation token'
        throw err
      } finally {
        this.loading = false
      }
    },

    updateCheckoutForm(field: string, value: any) {
      (this.checkoutForm as any)[field] = value
    },

    clearBooking() {
      this.currentBooking = null
      this.error = null
    },

    clearCheckoutForm() {
      this.checkoutForm = {
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_country: '',
        customer_notes: '',
        pickup_location: '',
        payment_method: 'culqi'
      }
    },

    reset() {
      this.currentBooking = null
      this.loading = false
      this.error = null
      this.paymentConfig = null
      this.clearCheckoutForm()
    }
  }
})
