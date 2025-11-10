/**
 * Google Identity Services API Type Definitions
 */

interface GoogleCredentialResponse {
  credential: string
  select_by: string
}

interface GoogleButtonConfig {
  type?: 'standard' | 'icon'
  theme?: 'outline' | 'filled_blue' | 'filled_black'
  size?: 'large' | 'medium' | 'small'
  text?: 'signin_with' | 'signup_with' | 'continue_with' | 'signin'
  shape?: 'rectangular' | 'pill' | 'circle' | 'square'
  logo_alignment?: 'left' | 'center'
  width?: string | number
  locale?: string
}

interface GoogleInitConfig {
  client_id: string
  callback: (response: GoogleCredentialResponse) => void
  auto_select?: boolean
  cancel_on_tap_outside?: boolean
  context?: 'signin' | 'signup' | 'use'
  ux_mode?: 'popup' | 'redirect'
  login_uri?: string
  native_callback?: (response: GoogleCredentialResponse) => void
  intermediate_iframe_close_callback?: () => void
  itp_support?: boolean
}

interface GoogleAccounts {
  id: {
    initialize: (config: GoogleInitConfig) => void
    renderButton: (parent: HTMLElement, options: GoogleButtonConfig) => void
    prompt: (momentListener?: (notification: any) => void) => void
    disableAutoSelect: () => void
    storeCredential: (credential: { id: string; password: string }) => void
    cancel: () => void
    onGoogleLibraryLoad: () => void
    revoke: (hint: string, callback: (done: any) => void) => void
  }
}

interface Window {
  google?: {
    accounts: GoogleAccounts
  }
}

declare const google: {
  accounts: GoogleAccounts
}

export {}

