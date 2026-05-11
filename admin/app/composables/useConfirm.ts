interface ConfirmOptions {
  title: string
  description?: string
  confirmLabel?: string
  cancelLabel?: string
  confirmColor?: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'neutral'
  confirmIcon?: string
  icon?: string
  iconColor?: 'primary' | 'success' | 'error' | 'warning' | 'info' | 'neutral'
}

interface ConfirmState extends ConfirmOptions {
  open: boolean
  loading: boolean
  resolver: ((value: boolean) => void) | null
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
  resolver: null,
})

export const useConfirm = () => {
  const state = useState<ConfirmState>('confirm-dialog', defaultState)

  const confirm = (options: ConfirmOptions): Promise<boolean> => {
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

  return { state, confirm, accept, cancel }
}
