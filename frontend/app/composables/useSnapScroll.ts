// Horizontal scroll-snap slider: swipe on mobile, arrow buttons on desktop.
// Bind `container` to the scroll element and call `scroll(±1)` from arrows;
// each step advances ~85% of a screenful so the next card stays peeking.
export function useSnapScroll() {
  const container = ref<HTMLElement | null>(null)

  function scroll(dir: number) {
    const el = container.value
    if (!el) return
    el.scrollBy({ left: dir * el.clientWidth * 0.85, behavior: 'smooth' })
  }

  return { container, scroll }
}
