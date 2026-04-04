import { defineStore } from 'pinia'

export interface CartItem {
  id: string
  tourId: number
  tourTitle: string
  tourSlug: string
  tourImage: string
  selectedDate: string
  selectedTime: string
  timezone?: string
  adults: number
  children: number
  basePrice: number
  childPrice: number
  total: number
  currency: string
  policies?: string
  taxPercentage?: number
  advancePaymentPercentage?: number
  cancellationPolicy?: string
  guideType?: string
  guideLanguages?: string[]
  durationLabel?: string
  hasOffer?: boolean
  originalPrice?: number
  offerDiscount?: number
  offerDiscountType?: string
  offerColor?: string
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [] as CartItem[],
    loading: false,
    error: null as string | null
  }),

  getters: {
    itemCount: (state) => state.items.length,

    subtotal: (state) => {
      return state.items.reduce((sum, item) => sum + item.total, 0)
    },

    totalTax: (state) => {
      return state.items.reduce((sum, item) => {
        const tax = item.taxPercentage || 0
        return sum + (item.total * tax / 100)
      }, 0)
    },

    totalAmount(): number {
      return this.subtotal + this.totalTax
    },

    totalParticipants: (state) => {
      return state.items.reduce((sum, item) => sum + item.adults + item.children, 0)
    },

    isEmpty: (state) => state.items.length === 0
  },

  actions: {
    addItem(item: Omit<CartItem, 'id'>) {
      const cartItem: CartItem = {
        ...item,
        id: `${item.tourId}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
      }
      this.items.push(cartItem)
      this.saveToLocalStorage()
    },

    removeItem(itemId: string) {
      this.items = this.items.filter(item => item.id !== itemId)
      this.saveToLocalStorage()
    },

    updateItem(itemId: string, updates: Partial<CartItem>) {
      const index = this.items.findIndex(item => item.id === itemId)
      if (index !== -1) {
        this.items[index] = { ...this.items[index], ...updates }
        this.saveToLocalStorage()
      }
    },

    clearCart() {
      this.items = []
      this.saveToLocalStorage()
    },

    getItemsByTour(tourId: number) {
      return this.items.filter(item => item.tourId === tourId)
    },

    saveToLocalStorage() {
      if (typeof window !== 'undefined' && window.localStorage) {
        try {
          localStorage.setItem('voyager_cart', JSON.stringify(this.items))
          console.log('Cart saved to localStorage:', this.items.length, 'items')
        } catch (error) {
          console.error('Error saving cart to localStorage:', error)
        }
      }
    },

    loadFromLocalStorage() {
      if (typeof window !== 'undefined' && window.localStorage) {
        try {
          const stored = localStorage.getItem('voyager_cart')
          if (stored) {
            this.items = JSON.parse(stored)
            console.log('Cart loaded from localStorage:', this.items.length, 'items')
          } else {
            console.log('No cart found in localStorage')
          }
        } catch (error) {
          console.error('Error loading cart from localStorage:', error)
          this.items = []
        }
      }
    }
  }
})
