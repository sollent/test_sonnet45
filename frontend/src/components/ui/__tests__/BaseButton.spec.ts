import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import BaseButton from '../BaseButton.vue'

describe('BaseButton', () => {
  describe('rendering', () => {
    it('should render with label prop', () => {
      render(BaseButton, {
        props: {
          label: 'Click me',
        },
      })

      expect(screen.getByRole('button', { name: 'Click me' })).toBeInTheDocument()
    })

    it('should render with slot content', () => {
      render(BaseButton, {
        slots: {
          default: '<span>Custom Content</span>',
        },
      })

      expect(screen.getByText('Custom Content')).toBeInTheDocument()
    })

    it('should use slot content when provided', () => {
      render(BaseButton, {
        props: {
          label: 'Label Prop',
        },
        slots: {
          default: 'Slot Content',
        },
      })

      // Slot content replaces label
      expect(screen.getByText('Slot Content')).toBeInTheDocument()
    })
  })

  describe('button types', () => {
    it('should render as button type by default', () => {
      render(BaseButton, {
        props: { label: 'Button' },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveAttribute('type', 'button')
    })

    it('should render as submit type', () => {
      render(BaseButton, {
        props: {
          label: 'Submit',
          type: 'submit',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveAttribute('type', 'submit')
    })

    it('should render as reset type', () => {
      render(BaseButton, {
        props: {
          label: 'Reset',
          type: 'reset',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveAttribute('type', 'reset')
    })
  })

  describe('variants', () => {
    it('should apply primary variant class by default', () => {
      render(BaseButton, {
        props: { label: 'Primary' },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--primary')
    })

    it('should apply secondary variant class', () => {
      render(BaseButton, {
        props: {
          label: 'Secondary',
          variant: 'secondary',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--secondary')
    })

    it('should apply outline variant class', () => {
      render(BaseButton, {
        props: {
          label: 'Outline',
          variant: 'outline',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--outline')
    })

    it('should apply text variant class', () => {
      render(BaseButton, {
        props: {
          label: 'Text',
          variant: 'text',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--text')
    })
  })

  describe('sizes', () => {
    it('should apply medium size class by default', () => {
      render(BaseButton, {
        props: { label: 'Medium' },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--medium')
    })

    it('should apply small size class', () => {
      render(BaseButton, {
        props: {
          label: 'Small',
          size: 'small',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--small')
    })

    it('should apply large size class', () => {
      render(BaseButton, {
        props: {
          label: 'Large',
          size: 'large',
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--large')
    })
  })

  describe('full width', () => {
    it('should not be full width by default', () => {
      render(BaseButton, {
        props: { label: 'Normal' },
      })

      const button = screen.getByRole('button')
      expect(button).not.toHaveClass('base-button--full-width')
    })

    it('should apply full width class', () => {
      render(BaseButton, {
        props: {
          label: 'Full Width',
          fullWidth: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--full-width')
    })
  })

  describe('disabled state', () => {
    it('should not be disabled by default', () => {
      render(BaseButton, {
        props: { label: 'Enabled' },
      })

      const button = screen.getByRole('button')
      expect(button).not.toBeDisabled()
    })

    it('should be disabled when disabled prop is true', () => {
      render(BaseButton, {
        props: {
          label: 'Disabled',
          disabled: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toBeDisabled()
    })

    it('should not emit click event when disabled', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Disabled',
          disabled: true,
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      await user.click(button)

      expect(handleClick).not.toHaveBeenCalled()
    })
  })

  describe('loading state', () => {
    it('should not show loading spinner by default', () => {
      render(BaseButton, {
        props: { label: 'Not Loading' },
      })

      expect(screen.queryByRole('img', { hidden: true })).not.toBeInTheDocument()
    })

    it('should show loading spinner when loading is true', () => {
      render(BaseButton, {
        props: {
          label: 'Loading',
          loading: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button--loading')
      expect(button.querySelector('.base-button__spinner')).toBeInTheDocument()
    })

    it('should be disabled when loading', () => {
      render(BaseButton, {
        props: {
          label: 'Loading',
          loading: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toBeDisabled()
    })

    it('should not emit click event when loading', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Loading',
          loading: true,
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      await user.click(button)

      expect(handleClick).not.toHaveBeenCalled()
    })
  })

  describe('click events', () => {
    it('should emit click event on click', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Clickable',
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      await user.click(button)

      expect(handleClick).toHaveBeenCalledTimes(1)
    })

    it('should emit click event with mouse event', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Clickable',
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      await user.click(button)

      expect(handleClick).toHaveBeenCalledWith(expect.any(MouseEvent))
    })

    it('should handle multiple clicks', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Multi Click',
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      await user.click(button)
      await user.click(button)
      await user.click(button)

      expect(handleClick).toHaveBeenCalledTimes(3)
    })
  })

  describe('combined states', () => {
    it('should apply multiple classes correctly', () => {
      render(BaseButton, {
        props: {
          label: 'Combined',
          variant: 'secondary',
          size: 'large',
          fullWidth: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toHaveClass('base-button')
      expect(button).toHaveClass('base-button--secondary')
      expect(button).toHaveClass('base-button--large')
      expect(button).toHaveClass('base-button--full-width')
    })

    it('should handle disabled and loading together', () => {
      render(BaseButton, {
        props: {
          label: 'Disabled and Loading',
          disabled: true,
          loading: true,
        },
      })

      const button = screen.getByRole('button')
      expect(button).toBeDisabled()
      expect(button).toHaveClass('base-button--loading')
    })
  })

  describe('accessibility', () => {
    it('should be accessible by role', () => {
      render(BaseButton, {
        props: { label: 'Accessible Button' },
      })

      expect(screen.getByRole('button')).toBeInTheDocument()
    })

    it('should be keyboard accessible', async () => {
      const user = userEvent.setup()
      const handleClick = vi.fn()

      render(BaseButton, {
        props: {
          label: 'Keyboard',
          onClick: handleClick,
        },
      })

      const button = screen.getByRole('button')
      button.focus()
      await user.keyboard('{Enter}')

      expect(handleClick).toHaveBeenCalled()
    })
  })
})

