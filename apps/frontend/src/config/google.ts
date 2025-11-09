/**
 * Google OAuth Configuration
 */
export const GOOGLE_CONFIG = {
  // Google Client ID from Google Cloud Console
  CLIENT_ID: import.meta.env.VITE_GOOGLE_CLIENT_ID || '1084991394082-upgn45i5u4g8jc3u1p9n8h9i1sldpsa1.apps.googleusercontent.com',
  
  // Button configuration
  BUTTON_CONFIG: {
    theme: 'outline' as const,
    size: 'large' as const,
    width: '100%',
    text: 'signin_with' as const,
    shape: 'rectangular' as const,
    logo_alignment: 'left' as const
  },
  
  // UX mode
  UX_MODE: 'popup' as const // 'popup' or 'redirect'
}

