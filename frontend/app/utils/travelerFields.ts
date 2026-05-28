// Per-traveler field registry shared by the traveler form (rendering) and the
// confirmation page (validation messages). Keys match the tour's
// personal_info_required / operational_info_required codes. first_name/last_name
// are intentionally excluded — they're covered by the always-collected full_name;
// nationality is stored in its own column, the rest live in extra_data.
export interface TravelerFieldDef {
  label: string
  type: 'date' | 'country' | 'tel' | 'email' | 'text' | 'gender' | 'number'
}

export const TRAVELER_FIELD_DEFS: Record<string, TravelerFieldDef> = {
  birthdate: { label: 'Fecha de nacimiento', type: 'date' },
  nationality: { label: 'Nacionalidad', type: 'country' },
  phone_whatsapp: { label: 'WhatsApp', type: 'tel' },
  email: { label: 'Correo electrónico', type: 'email' },
  dietary_restrictions: { label: 'Restricciones alimentarias', type: 'text' },
  gender: { label: 'Género', type: 'gender' },
  peru_entry_date: { label: 'Fecha de ingreso al Perú', type: 'date' },
  hotel_name: { label: 'Nombre de su hotel', type: 'text' },
  passport_copy: { label: 'N° de pasaporte o ID', type: 'text' },
  arrival_flight: { label: 'Vuelo de llegada', type: 'text' },
  departure_flight: { label: 'Vuelo de salida', type: 'text' },
  weight_kg: { label: 'Peso (kg)', type: 'number' },
  height_m: { label: 'Altura (m)', type: 'number' },
  arrival_bus_company: { label: 'Cía de bus de llegada', type: 'text' },
  arrival_train: { label: 'Tren de llegada', type: 'text' },
}

// Renderable, known fields the admin asked for (drops name parts + unknowns).
export function travelerFieldsFor(src: any): string[] {
  const personal = Array.isArray(src?.personal_info_required) ? src.personal_info_required : []
  const operational = Array.isArray(src?.operational_info_required) ? src.operational_info_required : []
  return [...personal, ...operational].filter((k: string) => TRAVELER_FIELD_DEFS[k])
}

// data_requirement: 1 = lead traveler only, 2 = every passenger.
export function travelerApplyAll(src: any): boolean {
  return Number(src?.data_requirement) === 2
}

// Read a field's stored value from a traveler (nationality is its own column).
export function travelerFieldValue(traveler: any, key: string): string {
  if (key === 'nationality') return traveler?.nationality || ''
  return traveler?.extra_data?.[key] || ''
}
