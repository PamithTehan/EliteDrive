---
name: Executive Fleet Narrative
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#45464d'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#76777d'
  outline-variant: '#c6c6cd'
  surface-tint: '#565e74'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#131b2e'
  on-primary-container: '#7c839b'
  inverse-primary: '#bec6e0'
  secondary: '#515f74'
  on-secondary: '#ffffff'
  secondary-container: '#d5e3fd'
  on-secondary-container: '#57657b'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#001a42'
  on-tertiary-container: '#3980f4'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dae2fd'
  primary-fixed-dim: '#bec6e0'
  on-primary-fixed: '#131b2e'
  on-primary-fixed-variant: '#3f465c'
  secondary-fixed: '#d5e3fd'
  secondary-fixed-dim: '#b9c7e0'
  on-secondary-fixed: '#0d1c2f'
  on-secondary-fixed-variant: '#3a485c'
  tertiary-fixed: '#d8e2ff'
  tertiary-fixed-dim: '#adc6ff'
  on-tertiary-fixed: '#001a42'
  on-tertiary-fixed-variant: '#004395'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  headline-xl:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.01em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  container-max: 1280px
  gutter: 24px
  margin-desktop: 64px
  margin-mobile: 20px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
---

## Brand & Style

The design system is engineered for a premium car rental experience that balances high-end sophistication with seamless utility. The brand personality is authoritative yet accessible, targeting discerning travelers and professionals who value efficiency and transparency. 

The aesthetic direction is **Corporate / Modern** with a focus on high-performance precision. It leverages generous whitespace and a rigid structural alignment to evoke a sense of reliability and luxury. The emotional goal is to provide the user with absolute confidence in the quality of the fleet and the ease of the booking process.

## Colors

The palette is anchored by a deep navy (Primary) and slate gray (Secondary), providing a stable, high-contrast foundation that feels institutional and secure. 

- **Primary (#0F172A):** Used for navigation, headings, and high-level containers to establish authority.
- **Secondary (#334155):** Reserved for subtext, icons, and supporting structural elements.
- **Accent (#3B82F6):** An electric blue used exclusively for primary calls-to-action (CTAs) and interactive states to drive conversion.
- **Neutral (#F8FAFC):** A clean, off-white background color that prevents eye strain and provides a canvas for the darker brand colors to pop.

## Typography

The design system utilizes **Inter** exclusively to leverage its exceptional legibility and systematic feel. 

Headlines use tight letter-spacing and bold weights to convey a sense of premium precision. Body text is optimized for readability with generous line heights. Labels utilize uppercase tracking for "Trust Badges" and technical specifications of the vehicles, creating a clear distinction between editorial content and data points.

## Layout & Spacing

This design system follows a **Fluid Grid** model with a maximum container width of 1280px for desktop. 

- **Desktop:** 12-column grid with 24px gutters. Content is centered with 64px outer margins to ensure a premium, spacious feel.
- **Tablet:** 8-column grid with 24px gutters and 40px margins.
- **Mobile:** 4-column grid with 16px gutters and 20px margins. 

Vertical spacing (stacking) follows an 8px base unit. Hero sections and car listings should use `stack-lg` to separate distinct car categories, while form inputs use `stack-sm` for tight logical grouping.

## Elevation & Depth

To maintain a clean and professional look, depth is achieved through **Tonal Layers** and **Low-contrast Outlines** rather than heavy shadows.

- **Level 0 (Base):** Neutral background (#F8FAFC).
- **Level 1 (Cards/Forms):** White surfaces with a 1px border in a very light slate (#E2E8F0).
- **Level 2 (Interactive/Hover):** A subtle, ultra-diffused ambient shadow (0px 4px 20px rgba(15, 23, 42, 0.05)) is applied only when a car listing card is hovered to indicate interactivity.
- **Dividers:** Use 1px solid lines in #F1F5F9 for subtle separation within lists.

## Shapes

The design system uses **Soft** roundedness (4px - 12px) to reflect the sleek lines of modern automotive design.

- **Inputs and Buttons:** 4px (default) for a crisp, professional look.
- **Car Listing Cards:** 8px (large) to soften the layout and create a sophisticated container for photography.
- **Badges/Tags:** 12px (extra-large) to differentiate status indicators (e.g., "Available", "Premium") from structural elements.

## Components

### Search Forms
Search bars should be treated as high-priority "Hero" components. Use a horizontal layout on desktop with integrated iconography (calendar, location pin) and a prominent Electric Blue CTA button. Use a 1px slate border and white background for input fields.

### Car Listing Cards
Cards feature a high-resolution vehicle image on a clean white background. Price should be highlighted using `headline-md` in Navy. Specifications (transmission, fuel, seats) should be displayed using `label-sm` with subtle monochrome icons.

### Buttons
- **Primary:** Electric Blue background, white text, 4px corner radius. Bold weight.
- **Secondary:** Transparent background, Navy 1px border, Navy text.
- **Ghost:** No border or background, Slate text, used for "View Details" or "Cancel".

### Trust Badges
Small, horizontal layout items featuring a verified icon and `label-md` text. These should be placed near booking CTAs and in the footer to reinforce security and service quality.

### Lists & Filters
Filters use a "Sidebar" approach with checkboxes. Checkboxes are custom-styled: Navy when active with a 2px stroke, ensuring they feel integrated into the premium theme.