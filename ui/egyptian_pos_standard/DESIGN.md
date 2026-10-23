---
name: Egyptian POS Standard
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
  on-surface-variant: '#47464f'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#787680'
  outline-variant: '#c8c5d0'
  surface-tint: '#5b598c'
  primary: '#070235'
  on-primary: '#ffffff'
  primary-container: '#1e1b4b'
  on-primary-container: '#8683ba'
  inverse-primary: '#c4c1fb'
  secondary: '#006c4a'
  on-secondary: '#ffffff'
  secondary-container: '#82f5c1'
  on-secondary-container: '#00714e'
  tertiary: '#170700'
  on-tertiary: '#ffffff'
  tertiary-container: '#381a00'
  on-tertiary-container: '#cf7100'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e3dfff'
  primary-fixed-dim: '#c4c1fb'
  on-primary-fixed: '#181445'
  on-primary-fixed-variant: '#444173'
  secondary-fixed: '#85f8c4'
  secondary-fixed-dim: '#68dba9'
  on-secondary-fixed: '#002114'
  on-secondary-fixed-variant: '#005137'
  tertiary-fixed: '#ffdcc3'
  tertiary-fixed-dim: '#ffb77d'
  on-tertiary-fixed: '#2f1500'
  on-tertiary-fixed-variant: '#6e3900'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: IBM Plex Sans Arabic
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.01em
  numeric-pos:
    fontFamily: IBM Plex Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 24px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 32px
  container-max: 1440px
  gutter: 16px
---

## Brand & Style
This design system is engineered for high-velocity retail and hospitality environments in Egypt. The brand personality is **authoritative, dependable, and efficient**. It balances a global corporate aesthetic with local usability requirements, ensuring that cashiers and business owners feel a sense of stability and institutional trust.

The visual style is **Corporate Modern**, leaning into high-density information display without sacrificing clarity. It utilizes a structured grid, purposeful color application for operational status, and a tactile interface optimized for both touch-screen POS terminals and desktop administrative back-offices. The emotional response should be one of "calm control" during peak business hours.

## Colors
The palette is rooted in **Deep Indigo**, providing a professional and "banking-grade" foundation. 

- **Primary (Deep Indigo):** Used for navigation, primary actions, and branding to evoke security.
- **Success (Emerald Green):** Reserved for completed transactions, "Add to Cart" actions, and positive growth metrics.
- **Warning (Amber):** High-visibility usage for low-stock alerts or pending payments.
- **Background (Slate Gray):** Specifically #F8FAFC is used to provide a "soft-white" environment that reduces blue-light strain for users working 8-12 hour shifts.

For RTL/Arabic contexts, color associations remain consistent, but visual weight is carefully balanced to ensure that the primary action—often located on the left in LTR—is mirrored effectively to the right.

## Typography
The system utilizes **IBM Plex Sans** (and its Arabic counterpart) to maintain a technical, structured feel. 

- **Bilingual Harmony:** The Arabic glyphs in IBM Plex Sans align perfectly with the x-height of the Latin characters, preventing "baseline jump" when English and Arabic text appear in the same row.
- **Numeric Clarity:** For transaction amounts and quantities, use the standard Latin glyphs (Western Arabic numerals) as is common in Egyptian financial software, ensuring maximum legibility.
- **Scaling:** Headings scale down by 15% on mobile devices to ensure long Arabic words do not break awkwardly.

## Layout & Spacing
This design system uses a **12-column fluid grid** for administrative dashboards and a **fixed-panel layout** for the POS terminal interface.

- **POS Layout:** A 2/3 and 1/3 split. The larger area (right in LTR, left in RTL) hosts the product catalog. The narrower area (left in LTR, right in RTL) hosts the active cart and checkout controls.
- **Density:** High-density spacing (8px/16px) is prioritized to minimize scrolling. Data tables should use a "compact" vertical padding (8px) to maximize the number of visible rows on standard 1080p monitors.
- **RTL Mirroring:** When switching to Arabic, the entire layout mirrors. Global navigation moves to the right, and the "Back" action points to the right.

## Elevation & Depth
To maintain the **Corporate Modern** feel, the system avoids heavy shadows, favoring **tonal layers** and **precise outlines**.

- **Level 0 (Background):** Slate Gray (#F8FAFC) for the canvas.
- **Level 1 (Cards/Panels):** Pure White (#FFFFFF) with a 1px border in #E2E8F0.
- **Level 2 (Dropdowns/Modals):** Pure White with a soft, medium-spread shadow (Alpha 0.08) to separate the element from the primary UI.
- **Interactive Depth:** Buttons use a subtle 1px bottom border (inner shadow) to provide a "tactile" feel, indicating they are pressable on touch screens.

## Shapes
The shape language is **Soft (0.25rem/4px - 8px)**. This avoids the playfulness of fully rounded "bubble" designs while remaining more modern and approachable than sharp 90-degree corners.

- **Inputs & Fields:** Use a 4px radius for a crisp, professional appearance.
- **Action Buttons:** Use a 6px radius to make them stand out slightly from input fields.
- **Containers:** 8px radius for main cards and dashboard panels to frame content cleanly.

## Components
- **Tactile POS Buttons:** Product tiles in the POS view must be at least 80x80px for touch accuracy. They should include a subtle "press" state where the background color darkens by 5%.
- **High-Density Tables:** Rows must alternate with a very subtle background tint (#F1F5F9) to help the eye track across data. Columns containing currency should be right-aligned (in LTR) or left-aligned (in RTL).
- **Status Badges:** Use a "Pill" shape with a low-opacity background of the status color and a high-contrast text color (e.g., Emerald Green text on a 10% Emerald Green background).
- **Dual-Language Input Fields:** Labels must always be top-aligned to accommodate varying text lengths in Arabic vs. English without breaking the horizontal alignment of the input boxes.
- **Cart Component:** A persistent vertical container that calculates totals in real-time, featuring a high-contrast Deep Indigo "Pay" button at the bottom.