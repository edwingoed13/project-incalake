// plugins/culqi.client.ts
export default defineNuxtPlugin(() => {
  // Only run on client side
  if (process.server) return

  // Load Culqi Custom Checkout script
  const loadCulqiScript = () => {
    return new Promise((resolve, reject) => {
      // Check if already loaded
      if (window.CulqiCheckout) {
        console.log('✅ CulqiCheckout already available')
        resolve(true)
        return
      }

      // Check if script already exists
      const existingScript = document.querySelector('script[src*="js.culqi.com/checkout-js"]')
      if (existingScript) {
        console.log('⏳ Script exists, waiting for CulqiCheckout...')

        // Wait for CulqiCheckout to be available
        let attempts = 0
        const checkInterval = setInterval(() => {
          attempts++
          if (window.CulqiCheckout) {
            clearInterval(checkInterval)
            console.log('✅ CulqiCheckout loaded')
            resolve(true)
          } else if (attempts > 30) {
            clearInterval(checkInterval)
            console.error('❌ CulqiCheckout timeout')
            reject(new Error('CulqiCheckout timeout'))
          }
        }, 100)
        return
      }

      // Create script tag
      console.log('📦 Loading Culqi Custom Checkout script...')
      const script = document.createElement('script')
      script.src = 'https://js.culqi.com/checkout-js'
      script.async = true
      script.defer = true

      script.onload = () => {
        console.log('✅ Script loaded')

        // Wait for CulqiCheckout to be available
        let attempts = 0
        const checkInterval = setInterval(() => {
          attempts++
          if (window.CulqiCheckout) {
            clearInterval(checkInterval)
            console.log('✅ CulqiCheckout class available')
            resolve(true)
          } else if (attempts > 20) {
            clearInterval(checkInterval)
            console.error('❌ CulqiCheckout not available after load')
            reject(new Error('CulqiCheckout not available'))
          }
        }, 100)
      }

      script.onerror = (error) => {
        console.error('❌ Failed to load script:', error)
        reject(new Error('Failed to load Culqi script'))
      }

      document.head.appendChild(script)
    })
  }

  // Load script on plugin initialization
  loadCulqiScript().catch(error => {
    console.error('Failed to load Culqi:', error)
  })

  // Provide helper for components
  return {
    provide: {
      loadCulqi: loadCulqiScript
    }
  }
})

// Declare global types
declare global {
  interface Window {
    CulqiCheckout: any
    Culqi: any
    culqi: any
  }
}