// Promise-based confirm dialog replacing native window.confirm() (which is
// unstyled, blocks the main thread and can't be translated consistently).
// Call `confirmDialog({...})` from anywhere; <CommonConfirmDialog /> (mounted
// once in app.vue) renders the active request.
interface ConfirmDialogOptions {
  title: string
  description?: string
  confirmLabel?: string
  cancelLabel?: string
  danger?: boolean
}

interface ConfirmDialogState extends ConfirmDialogOptions {
  open: boolean
  resolver: ((value: boolean) => void) | null
}

export function useConfirmDialog() {
  const state = useState<ConfirmDialogState>('confirm-dialog', () => ({
    open: false,
    title: '',
    description: '',
    confirmLabel: '',
    cancelLabel: '',
    danger: false,
    resolver: null,
  }))

  function confirmDialog(options: ConfirmDialogOptions): Promise<boolean> {
    return new Promise((resolve) => {
      // A pending dialog being replaced counts as cancelled.
      state.value.resolver?.(false)
      state.value = { ...options, open: true, resolver: resolve }
    })
  }

  function settle(value: boolean) {
    state.value.resolver?.(value)
    state.value.open = false
    state.value.resolver = null
  }

  return { state, confirmDialog, settle }
}
