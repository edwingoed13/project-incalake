// Normalize the legacy "what_includes / what_not_includes" content.
// Migrated tours store CKEditor HTML where the INCLUDES field often carries its
// own "INCLUYE …" header AND an embedded "NO INCLUYE …" section, plus HTML
// entities (&uacute;, &nbsp;…). These helpers produce clean bullet lists:
// decode entities, treat block tags as line breaks, drop the leading header,
// and split the excludes back out — used by both the public tour page and the
// booking confirmation. Entity decoding is DOM-free so SSR and client agree
// (no hydration mismatch).

const NAMED_ENTITIES: Record<string, string> = {
  nbsp: ' ', amp: '&', quot: '"', apos: "'", lt: '<', gt: '>',
  aacute: 'á', eacute: 'é', iacute: 'í', oacute: 'ó', uacute: 'ú', uuml: 'ü', ntilde: 'ñ',
  Aacute: 'Á', Eacute: 'É', Iacute: 'Í', Oacute: 'Ó', Uacute: 'Ú', Uuml: 'Ü', Ntilde: 'Ñ',
  ordf: 'ª', ordm: 'º', deg: '°', euro: '€', hellip: '…', ndash: '–', mdash: '—',
  rsquo: '’', lsquo: '‘', ldquo: '“', rdquo: '”',
}

function decodeEntities(s: string): string {
  if (!s) return ''
  return s
    .replace(/&#x([0-9a-f]+);/gi, (_m, h) => { try { return String.fromCodePoint(parseInt(h, 16)) } catch { return _m } })
    .replace(/&#(\d+);/g, (_m, d) => { try { return String.fromCodePoint(parseInt(d, 10)) } catch { return _m } })
    .replace(/&([a-z]+);/gi, (m, name) => NAMED_ENTITIES[name] ?? NAMED_ENTITIES[String(name).toLowerCase()] ?? m)
}

// HTML / rich text → clean lines (block tags become breaks, rest stripped).
export function htmlToLines(raw: any): string[] {
  let s = String(raw ?? '')
  if (!s.trim()) return []
  s = s.replace(/<\/(p|li|div|h[1-6]|tr)>/gi, '\n').replace(/<br\s*\/?>/gi, '\n').replace(/<[^>]+>/g, '')
  s = decodeEntities(s).replace(/[ ]/g, ' ')
  return s.split('\n').map(x => x.trim()).filter(Boolean)
}

// A line that marks the start of the "not included" section.
const EXCLUDE_MARKER = /^\s*(no\s+inclu|not\s+includ|no\s+est[aá]\s+inclu)/i
// A leading "INCLUYE …" header line that duplicates the section title.
const INCLUDE_HEADER = /^\s*(el\s+tour\s+)?(incluye|includes?|what'?s?\s+included|qu[eé]\s+incluye)\b/i

function dropLeadingHeader(lines: string[], re: RegExp): string[] {
  return (lines.length > 1 && re.test(lines[0])) ? lines.slice(1) : lines
}

// Included items: lines before any "NO INCLUYE" marker, minus a leading header.
export function tourIncludesList(rawIncludes: any): string[] {
  const lines = htmlToLines(rawIncludes)
  const cut = lines.findIndex(l => EXCLUDE_MARKER.test(l))
  const inc = cut >= 0 ? lines.slice(0, cut) : lines
  return dropLeadingHeader(inc, INCLUDE_HEADER)
}

// Excluded items: explicit what_not_includes ∪ whatever follows a "NO INCLUYE"
// marker embedded in what_includes (incl. text on the marker line itself),
// de-duplicated case-insensitively.
export function tourExcludesList(rawIncludes: any, rawExcludes: any): string[] {
  const incLines = htmlToLines(rawIncludes)
  const cut = incLines.findIndex(l => EXCLUDE_MARKER.test(l))
  let fromIncludes: string[] = []
  if (cut >= 0) {
    const tail = incLines[cut].replace(EXCLUDE_MARKER, '').replace(/^[:\s.\-–—]+/, '').trim()
    fromIncludes = [...(tail ? [tail] : []), ...incLines.slice(cut + 1)]
  }
  const explicit = dropLeadingHeader(htmlToLines(rawExcludes), EXCLUDE_MARKER)
  const seen = new Set<string>()
  const out: string[] = []
  for (const item of [...explicit, ...fromIncludes]) {
    const key = item.toLowerCase()
    if (!seen.has(key)) { seen.add(key); out.push(item) }
  }
  return out
}
