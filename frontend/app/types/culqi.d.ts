// Culqi Custom Checkout Type Definitions

declare global {
  interface Window {
    CulqiCheckout: typeof CulqiCheckout
    culqi?: () => void
  }

  class CulqiCheckout {
    constructor(publicKey: string, config: CulqiConfig)

    token?: CulqiToken
    order?: CulqiOrder
    error?: CulqiError
    culqi?: () => void

    open(): void
    close(): void
  }

  interface CulqiConfig {
    settings: CulqiSettings
    client?: CulqiClient
    options?: CulqiOptions
    appearance?: CulqiAppearance
  }

  interface CulqiSettings {
    title: string
    currency: string
    amount: number
    description?: string
    order?: string
    xculqirsaid?: string
    rsapublickey?: string
  }

  interface CulqiClient {
    email: string
  }

  interface CulqiOptions {
    lang?: string
    installments?: boolean
    modal?: boolean
    container?: string
    paymentMethods?: {
      tarjeta?: boolean
      yape?: boolean
      billetera?: boolean
      bancaMovil?: boolean
      agente?: boolean
      cuotealo?: boolean
    }
    paymentMethodsSort?: string[]
  }

  interface CulqiAppearance {
    theme?: string
    hiddenCulqiLogo?: boolean
    hiddenBannerContent?: boolean
    hiddenBanner?: boolean
    hiddenToolBarAmount?: boolean
    hiddenEmail?: boolean
    menuType?: string
    buttonCardPayText?: string
    logo?: string | null
    defaultStyle?: {
      bannerColor?: string
      buttonBackground?: string
      menuColor?: string
      linksColor?: string
      buttonTextColor?: string
      priceColor?: string
    }
  }

  interface CulqiToken {
    id: string
    email: string
    card_number?: string
    card_brand?: string
    card_type?: string
  }

  interface CulqiOrder {
    id: string
  }

  interface CulqiError {
    user_message: string
    merchant_message?: string
  }
}

export {}
