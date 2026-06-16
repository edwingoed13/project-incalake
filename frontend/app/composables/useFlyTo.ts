// "Fly to counter" micro-interaction: when the user saves a tour or adds it to
// the cart, a small icon arcs from the tapped element up to the header counter
// and the counter pulses. Pure client-side DOM + Web Animations API, no deps.
//
// Usage:
//   const { flyTo } = useFlyTo()
//   flyTo(event.currentTarget, '#nav-wishlist', 'heart')
//
// The target is found by selector at call time, so the navbar just needs a
// stable id on its counters (#nav-wishlist, #nav-cart).

type FlyIcon = 'heart' | 'cart'

const ICON_PATH: Record<FlyIcon, string> = {
  // Material Symbols "favorite" (filled heart)
  heart: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
  // Material Symbols "shopping_cart" (filled)
  cart: 'M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z',
}

export function useFlyTo() {
  function flyTo(source: HTMLElement | null, targetSelector: string, icon: FlyIcon = 'heart', color = '#ef4444', _retry = 0) {
    if (!import.meta.client || !source) return
    // Respect reduced-motion — skip the flight, just pulse the target.
    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches
    const target = document.querySelector(targetSelector) as HTMLElement | null
    if (!target) return

    const pulse = () => {
      target.classList.remove('fly-pulse')
      // force reflow so re-adding restarts the animation
      void target.offsetWidth
      target.classList.add('fly-pulse')
      setTimeout(() => target.classList.remove('fly-pulse'), 500)
    }

    if (reduce) { pulse(); return }

    const from = source.getBoundingClientRect()
    const to = target.getBoundingClientRect()
    // First interaction can fire before layout settles (source/target not yet
    // measured → 0×0 rects). Wait one frame and retry once before giving up,
    // so the very first save still animates instead of silently no-op'ing.
    if ((!from.width || !to.width) && _retry < 1) {
      requestAnimationFrame(() => flyTo(source, targetSelector, icon, color, _retry + 1))
      return
    }
    const size = 26
    const startX = from.left + from.width / 2
    const startY = from.top + from.height / 2
    const endX = to.left + to.width / 2
    const endY = to.top + to.height / 2
    const dx = endX - startX
    const dy = endY - startY

    const node = document.createElement('div')
    node.setAttribute('aria-hidden', 'true')
    node.style.cssText = [
      'position:fixed',
      `left:${startX - size / 2}px`,
      `top:${startY - size / 2}px`,
      `width:${size}px`,
      `height:${size}px`,
      'z-index:9999',
      'pointer-events:none',
      'will-change:transform,opacity',
      `color:${color}`,
      'filter:drop-shadow(0 2px 4px rgba(0,0,0,.25))',
    ].join(';')
    node.innerHTML = `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="currentColor"><path d="${ICON_PATH[icon]}"/></svg>`
    document.body.appendChild(node)

    const anim = node.animate([
      { transform: 'translate(0,0) scale(1)', opacity: 1 },
      { transform: `translate(${dx * 0.5}px, ${dy * 0.5 - 60}px) scale(1.3)`, opacity: 1, offset: 0.55 },
      { transform: `translate(${dx}px, ${dy}px) scale(0.2)`, opacity: 0.2 },
    ], { duration: 680, easing: 'cubic-bezier(0.4, 0, 0.6, 1)' })

    anim.onfinish = () => { node.remove(); pulse() }
    anim.oncancel = () => node.remove()
  }

  return { flyTo }
}
