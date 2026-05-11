import { onMounted, onBeforeUnmount, nextTick } from 'vue'

interface FocusSnapshot {
  path: string
  scrollY: number
  selectionStart?: number
  selectionEnd?: number
  ts: number
}

/**
 * Tracks the user's currently focused input/textarea and the wizard scroll
 * position, then restores them on next mount (e.g. after F5).
 *
 * Strategy: on focus, walk up the DOM tree until reaching the given root and
 * record the nth-child path. Persists to sessionStorage (cleared on tab close,
 * survives F5). On mount, query the same path and re-focus.
 *
 * The path-based approach is brittle if DOM structure changes between mounts,
 * but works well when combined with our existing step + collapsibles persistence
 * (those keep the DOM identical across reloads).
 */
export const useFocusMemory = (storageKey: string, rootSelector = 'main') => {
  let saveTimer: any = null

  const buildPath = (el: Element, root: Element): string => {
    const path: string[] = []
    let current: Element | null = el
    while (current && current !== root) {
      const parent = current.parentElement
      if (!parent) break
      const tag = current.tagName.toLowerCase()
      const sameTagSiblings = Array.from(parent.children).filter(c => c.tagName === current!.tagName)
      const index = sameTagSiblings.indexOf(current) + 1
      path.unshift(`${tag}:nth-of-type(${index})`)
      current = parent
    }
    return path.join(' > ')
  }

  const onFocusIn = (e: FocusEvent) => {
    const target = e.target as HTMLElement
    if (!target) return
    // Only track text-editable fields. Skip checkboxes, buttons, etc.
    const isTextInput =
      (target instanceof HTMLInputElement && /^(text|email|password|search|tel|url|number|date)$/i.test(target.type || 'text')) ||
      target instanceof HTMLTextAreaElement ||
      target.isContentEditable

    if (!isTextInput) return

    const root = document.querySelector(rootSelector) as HTMLElement | null
    if (!root || !root.contains(target)) return

    const snapshot: FocusSnapshot = {
      path: buildPath(target, root),
      scrollY: root.scrollTop || 0,
      ts: Date.now(),
    }

    if (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) {
      try {
        snapshot.selectionStart = target.selectionStart ?? undefined
        snapshot.selectionEnd = target.selectionEnd ?? undefined
      } catch {
        // some input types throw on selectionStart access (e.g. number)
      }
    }

    clearTimeout(saveTimer)
    saveTimer = setTimeout(() => {
      try {
        sessionStorage.setItem(storageKey, JSON.stringify(snapshot))
      } catch {
        // sessionStorage may be unavailable
      }
    }, 200)
  }

  const onScroll = () => {
    if (!import.meta.client) return
    const root = document.querySelector(rootSelector) as HTMLElement | null
    if (!root) return
    try {
      const raw = sessionStorage.getItem(storageKey)
      if (!raw) return
      const snap = JSON.parse(raw) as FocusSnapshot
      snap.scrollY = root.scrollTop
      sessionStorage.setItem(storageKey, JSON.stringify(snap))
    } catch {
      // ignore
    }
  }

  /**
   * Call after data is loaded and the DOM is ready (after nextTick).
   * Restores scroll position and focus.
   */
  const restore = async () => {
    if (!import.meta.client) return
    try {
      const raw = sessionStorage.getItem(storageKey)
      if (!raw) return
      const snap = JSON.parse(raw) as FocusSnapshot

      // Wait for the DOM to settle (renders + collapsibles hydrating from localStorage + transitions)
      await nextTick()
      await new Promise(r => setTimeout(r, 250))
      await nextTick()

      const root = document.querySelector(rootSelector) as HTMLElement | null
      if (!root) return

      // Restore scroll first so the target is in view before focusing
      if (snap.scrollY > 0) root.scrollTop = snap.scrollY

      if (!snap.path) return

      let target: HTMLElement | null = null
      try {
        target = root.querySelector(snap.path) as HTMLElement | null
      } catch {
        // invalid selector (DOM changed)
        return
      }

      if (!target) return

      target.focus({ preventScroll: true })

      // Restore selection if possible
      if (
        (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement) &&
        typeof snap.selectionStart === 'number'
      ) {
        try {
          target.setSelectionRange(snap.selectionStart, snap.selectionEnd ?? snap.selectionStart)
        } catch {
          // some input types throw on setSelectionRange
        }
      }
    } catch {
      // never break the page over focus restoration
    }
  }

  onMounted(() => {
    if (!import.meta.client) return
    document.addEventListener('focusin', onFocusIn, { capture: true })

    const root = document.querySelector(rootSelector)
    root?.addEventListener('scroll', onScroll, { passive: true })
  })

  onBeforeUnmount(() => {
    if (!import.meta.client) return
    document.removeEventListener('focusin', onFocusIn, { capture: true } as any)
    const root = document.querySelector(rootSelector)
    root?.removeEventListener('scroll', onScroll)
    clearTimeout(saveTimer)
  })

  return { restore }
}
