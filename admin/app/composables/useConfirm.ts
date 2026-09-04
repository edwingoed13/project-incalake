interface ConfirmOptions {
  title: string
  description?: string
  confirmLabel?: string
  cancelLabel?: string
  confirmColor?: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'neutral'
  confirmIcon?: string
  icon?: string
  iconColor?: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'neutral'
  // When set, the dialog shows a text input and keeps the confirm button
  // disabled until the user types this exact value. Replaces the old
  // "double confirmation" friction with one deliberate, GitHub-style gate
  // for destructive actions (e.g. require the tour code).
  requireText?: string
  requireTextLabel?: string
  // Optional third way out, for the questions that are not yes/no: "save and
  // continue" / "continue without saving" / "stay here". Only dialogs that ask
  // for it get the button, so every existing caller keeps its boolean.
  altLabel?: string
  altIcon?: string
}

export type ConfirmOutcome = boolean | 'alt'

interface ConfirmState extends ConfirmOptions {
  open: boolean
  loading: boolean
  inputValue: string
  resolver: ((value: ConfirmOutcome) => void) | null
}

const defaultState = (): ConfirmState => ({
  open: false,
  loading: false,
  title: '',
  description: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  confirmColor: 'primary',
  confirmIcon: undefined,
  icon: 'i-lucide-circle-alert',
  iconColor: 'warning',
  requireText: undefined,
  requireTextLabel: undefined,
  altLabel: undefined,
  altIcon: undefined,
  inputValue: '',
  resolver: null,
})

export const useConfirm = () => {
  const state = useState<ConfirmState>('confirm-dialog', defaultState)

  const confirm = (options: ConfirmOptions): Promise<ConfirmOutcome> => {
    return new Promise((resolve) => {
      state.value = {
        ...defaultState(),
        ...options,
        open: true,
        resolver: resolve,
      }
    })
  }

  const accept = async () => {
    state.value.loading = true
    state.value.resolver?.(true)
    state.value.open = false
    state.value.loading = false
    state.value.resolver = null
  }

  const cancel = () => {
    state.value.resolver?.(false)
    state.value.open = false
    state.value.resolver = null
  }

  /** The third button, when the dialog declared one. */
  const alt = () => {
    state.value.resolver?.('alt')
    state.value.open = false
    state.value.resolver = null
  }

  return { state, confirm, accept, cancel, alt }
}
