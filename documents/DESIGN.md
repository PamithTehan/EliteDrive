---
name: Executive Fleet Narrative
colors:
  surface: '#f7f9fb'
  on-surface: '#0f172a'
  primary: '#0f172a'
  on-primary: '#ffffff'
  secondary: '#334155'
  accent: '#3b82f6'
  outline: '#e2e8f0'
  error: '#ba1a1a'
  success: '#1a7f37'
typography:
  fontFamily: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif
  headline-xl: 48px / 700 / -0.02em
  headline-lg: 32px / 600 / -0.01em
  headline-md: 20px / 600
  body-lg: 18px / 400
  body-md: 16px / 400
  label-md: 14px / 500 / uppercase
  label-sm: 12px / 600
radiuses:
  sm: 4px
  md: 8px
  lg: 12px
  full: 9999px
shadows:
  sm: 0 1px 2px rgba(0, 0, 0, 0.04)
  md: 0 4px 12px rgba(15, 23, 42, 0.06)
  lg: 0 10px 25px -5px rgba(15, 23, 42, 0.08)
---

# EliteDrive Design System Specification

## 1. Brand Identity & Aesthetic Direction

The **EliteDrive** design system is engineered to provide a high-performance, executive car rental marketplace experience. 

- **Personality**: Authoritative, sleek, trustworthy, and frictionless.
- **Aesthetic Direction**: *Executive Fleet Narrative* — deep midnight navy contrasts against clean off-white surfaces, accented by crisp electric blue calls-to-action and refined slate typography.

## 2. Color System (Tokens)

The foundational color tokens defined in `base/tokens.css` dictate the UI's look:

| Token | Hex | Usage |
| :--- | :--- | :--- |
| `--color-primary` | `#0f172a` | Deep Navy: Primary buttons, active tab borders, active text, primary headings. |
| `--color-secondary` | `#334155` | Slate Gray: Secondary body text, subtext, inactive icons, helper text. |
| `--color-accent` | `#3b82f6` | Electric Blue: Interactive focus rings, specific CTAs. |
| `--color-surface` | `#f7f9fb` | Off-White Background: Base page background to make white cards pop. |
| `--color-on-surface`| `#0f172a` | Deep Navy: Default body text color on surface. |
| `--color-outline` | `#e2e8f0` | Subtle Border: Used for cards, inputs, table borders, dividers. |
| `--color-success` | `#1a7f37` | Forest Green: Success messages, verified badges, confirmations. |
| `--color-error` | `#ba1a1a` | Deep Crimson: Form validation errors, rejections, cancel buttons. |

## 3. Typography System

EliteDrive utilizes the **Inter** typeface for a modern, clean reading experience (`base/typography.css`).

- **`.headline-xl`**: `48px` / `700` weight. Used for Hero titles.
- **`.headline-lg`**: `32px` / `600` weight. Used for page headings and modal titles.
- **`.headline-md`**: `20px` / `600` weight. Used for card headers and sub-sections.
- **`.body-lg`**: `18px` / `400` weight. Used for intro texts.
- **`.body-md`**: `16px` / `400` weight. The standard body text size.
- **`.label-md`**: `14px` / `500` weight / Uppercase. Used for category tags and small overlines.
- **`.label-sm`**: `12px` / `600` weight. Used for fine print, sub-labels, and small metadata.

## 4. Components & Elevation

### Buttons (`components/buttons.css`)
- **`.btn`**: Base class giving buttons a height of `44px` (`12px 20px` padding), `var(--radius-sm)` corners, and bold `600` text.
- **`.btn-primary`**: Fills with `--color-primary` (Navy) and white text. Hovers with `brightness(0.95)`.
- **`.btn-secondary`**: Transparent background with a Navy border and Navy text.
- **`.btn-ghost`**: Transparent background with Slate text, used for less prominent actions.

### Elevation & Radiuses (`base/tokens.css`)
- **Radiuses**: Ranging from sharp `4px` (`--radius-sm`) for inputs/buttons to completely rounded `9999px` (`--radius-full`) for pills.
- **Shadows**: 
  - `--shadow-sm`: Subtle inset depth.
  - `--shadow-md`: Standard card elevation.
  - `--shadow-lg`: Elevated modals, dropdowns, and floating widgets.

## 5. Architectural Guidelines
- **Form Controls**: Inputs and selects should use a 1px solid `--color-outline` border with `--radius-sm`, focusing to a clear ring.
- **Cards**: All distinct UI groups (e.g., dashboard panels, vehicle listings) should be housed in white containers with `--color-outline` borders and `--radius-md` or `--radius-lg` corners resting on the `--color-surface` background.
- **Tables**: Use wide, airy tables (`width: 100%`) with top headers and bottom cell borders. Actions should utilize icon buttons where appropriate for a cleaner look.