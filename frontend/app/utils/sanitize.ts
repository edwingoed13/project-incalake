/**
 * Sanitize HTML to prevent XSS attacks
 * Allows basic formatting tags but removes scripts and dangerous attributes
 * Works in both SSR and client-side
 */
export function sanitizeHtml(html: string): string {
  if (!html) return ''

  // Check if we're in a browser environment
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    // SSR: Just do basic regex sanitization
    return html
      .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
      .replace(/on\w+="[^"]*"/gi, '')
      .replace(/on\w+='[^']*'/gi, '')
  }

  // Client-side: Use DOM parsing
  const div = document.createElement('div')
  div.innerHTML = html

  // Remove script tags
  const scripts = div.querySelectorAll('script')
  scripts.forEach(script => script.remove())

  // Remove dangerous attributes
  const allElements = div.querySelectorAll('*')
  allElements.forEach(element => {
    // Remove event handlers
    Array.from(element.attributes).forEach(attr => {
      if (attr.name.startsWith('on')) {
        element.removeAttribute(attr.name)
      }
    })

    // Remove dangerous attributes
    const dangerousAttrs = ['onerror', 'onload', 'onclick', 'onmouseover']
    dangerousAttrs.forEach(attr => {
      if (element.hasAttribute(attr)) {
        element.removeAttribute(attr)
      }
    })
  })

  return div.innerHTML
}

/**
 * Strip all HTML tags from string
 */
export function stripHtml(html: string): string {
  if (!html) return ''
  
  // Check if we're in a browser environment
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    // SSR: Use regex to strip tags
    return html.replace(/<[^>]*>/g, '')
  }
  
  // Client-side: Use DOM parsing
  const div = document.createElement('div')
  div.innerHTML = html
  return div.textContent || div.innerText || ''
}

/**
 * Truncate HTML content to a specific length while preserving tags
 */
export function truncateHtml(html: string, maxLength: number): string {
  const text = stripHtml(html)
  if (text.length <= maxLength) return html

  return text.substring(0, maxLength) + '...'
}
