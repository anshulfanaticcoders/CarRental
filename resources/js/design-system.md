# Vrooem Design System Reference

> **Use this file when creating any new page, component, or UI element to maintain visual consistency.**

## Color Palette

### Primary Brand
| Token | Hex | Usage |
|---|---|---|
| `--custom-primary` | `#153b4f` | Buttons, headers, links, active states |
| `--primary-50` | `#f0f8fc` | Lightest bg tint |
| `--primary-100` | `#dceef6` | Light bg |
| `--primary-200` | `#b0d4e6` | Borders, dividers |
| `--primary-500` | `#2d7294` | Hover states |
| `--primary-700` | `#1c4d66` | Dark variant |
| `--primary-900` | `#0f2936` | Very dark |
| `--primary-950` | `#0a1d28` | Darkest (hero bg base) |
| `--custom-light-primary` | `#153b4f1a` | 10% opacity for subtle backgrounds |
| `--custom-overlay` | `#153b4f99` | 60% opacity for overlays |

### Accent (Cyan)
| Token | Hex | Usage |
|---|---|---|
| `accent-600` | `#0891b2` | Links hover |
| `accent-500` | `#06b6d4` | Accent mid |
| `accent-400` | `#22d3ee` | Badges, highlights, accent elements |
| `accent-100` | `#cffafe` | Light accent bg |

### Status Colors
| Status | Hex | Tailwind |
|---|---|---|
| Success | `#10b981` | `bg-emerald-500` |
| Warning | `#d97706` | `bg-amber-600` |
| Error | `#dc2626` | `bg-red-600` |

### Neutrals (Slate Scale)
| Token | Hex | Usage |
|---|---|---|
| `gray-50` | `#f8fafc` | Page bg, section bg |
| `gray-100` | `#f1f5f9` | Card bg, input bg |
| `gray-200` | `#e2e8f0` | Borders |
| `gray-300` | `#cbd5e1` | Disabled borders |
| `gray-400` | `#94a3b8` | Placeholder text |
| `gray-500` | `#64748b` | Secondary text |
| `gray-600` | `#475569` | Body text |
| `gray-700` | `#334155` | Strong text |
| `gray-800` | `#1e293b` | Headings |
| `gray-900` | `#0f172a` | Darkest text |

---

## Typography

### Fonts
- **Headings & UI:** `"Plus Jakarta Sans", sans-serif` (`--jakarta-font-family`)
- **Body:** `"IBM Plex Sans", serif`

### Scale
| Element | Size | Weight | Line Height |
|---|---|---|---|
| h1 | `5rem` (80px) | 600 | `6rem` |
| h2 | `4rem` (64px) | 600 | `6rem` |
| h3 | `3rem` (48px) | 600 | `4rem` |
| h4 | `2.25rem` (36px) | 600 | - |
| body | `1rem` (16px) | 400 | 1.5 |
| small | `0.875rem` (14px) | 400 | - |
| label | `0.75rem` (12px) | 500 | uppercase |

### Mobile (max-width: 768px)
| Element | Size | Line Height |
|---|---|---|
| h1 | `2.5rem` | 1.3em |
| h2 | `1.75rem` | 1.1em |
| h3 | `1.5rem` | 1.1em |

---

## Buttons

### Primary (Custom CSS)
```css
.button-primary {
  background: var(--custom-primary);  /* #153b4f */
  color: white;
  border-radius: 100px;              /* pill shape */
  font-weight: 600;
  border: 1px solid var(--custom-primary);
}
```

### Secondary (Custom CSS)
```css
.button-secondary {
  background: white;
  color: var(--custom-primary);
  border: 1px solid var(--custom-primary);
  border-radius: 100px;
  font-weight: 500;
}
```

### Shadcn Button Variants (for admin/dashboard)
```
default:     bg-primary text-primary-foreground shadow hover:bg-primary/90
destructive: bg-destructive text-destructive-foreground
outline:     border border-input bg-background shadow-sm
secondary:   bg-secondary text-secondary-foreground
ghost:       hover:bg-accent hover:text-accent-foreground
link:        text-primary underline-offset-4 hover:underline
```

### Button Sizes
```
default: h-9 px-4 py-2
xs:      h-7 rounded px-2
sm:      h-8 rounded-md px-3 text-xs
lg:      h-10 rounded-md px-8
icon:    h-9 w-9
```

---

## Backgrounds & Gradients

### Hero / Dark Sections
```css
background: linear-gradient(135deg, #0b2230 0%, #153b4f 45%, #0b1b26 100%);
```
With overlay:
```css
radial-gradient(circle at 18% 12%, rgba(46, 167, 173, 0.35), transparent 45%),
radial-gradient(circle at 80% 78%, rgba(15, 23, 42, 0.35), transparent 55%);
```

### Light Sections
```css
background: linear-gradient(180deg, #f8fafc 0%, #ffffff 45%, #f1f5f9 100%);
```

### Footer
```css
background: linear-gradient(135deg, #0c1f2b 0%, #153b4f 45%, #0a1822 100%);
```

### CTA / Subscribe Gradient
```css
background: linear-gradient(135deg, #153b4f, #2ea7ad);
```

---

## Spacing

### CSS Variables
```
--space-1:  4px     --space-6:  24px
--space-2:  8px     --space-8:  32px
--space-3:  12px    --space-10: 40px
--space-4:  16px    --space-12: 48px
--space-5:  20px    --space-16: 64px
```

### Container
```css
.full-w-container {
  width: min(92%, 1440px);
  margin-inline: auto;
}
```

### Section Padding
```css
.home-section {
  padding-block: clamp(3rem, 6vw, 6rem);
}
```

---

## Border Radius

| Token | Value | Usage |
|---|---|---|
| `--radius-sm` | `6px` | Small inputs |
| `--radius-md` | `10px` | Cards, panels |
| `--radius-lg` | `14px` | Large cards |
| `--radius-xl` | `20px` | Destination cards, search bar |
| `--radius-2xl` | `28px` | Hero elements |
| `--radius-full` | `9999px` / `100px` | Buttons (pill), badges |

---

## Shadows

```css
--shadow-xs: 0 1px 2px rgba(21, 59, 79, 0.04);
--shadow-sm: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
--shadow-md: 0 4px 12px rgba(21, 59, 79, 0.08), 0 2px 4px rgba(21, 59, 79, 0.04);
--shadow-lg: 0 12px 32px rgba(21, 59, 79, 0.12), 0 4px 8px rgba(21, 59, 79, 0.06);
--shadow-xl: 0 24px 48px rgba(21, 59, 79, 0.16);
```
All shadows use `#153b4f` (brand color) at low opacity - NOT generic gray.

---

## Cards

### Destination Card (Public Pages)
```css
border-radius: 20px;
box-shadow: 0 4px 24px rgba(10,29,40,0.06);
overflow: hidden;
/* Hover */
transform: translateY(-6px);
box-shadow: 0 16px 48px rgba(10,29,40,0.12);
```

### Feature Card (Dark Sections)
```css
background: rgba(255,255,255,0.04);
border: 1px solid rgba(255,255,255,0.07);
border-radius: 20px;
padding: 2rem;
backdrop-filter: blur(8px);
/* Hover */
background: rgba(255,255,255,0.07);
transform: translateY(-4px);
```

### Shadcn Card (Admin/Dashboard)
```
rounded-xl border bg-card text-card-foreground shadow
```

---

## Form Inputs

### Search Bar Inputs
```css
background: #f3f4f6;
border: 1px solid #e5e7eb;
border-radius: 0.75rem;
padding: 0.75rem 1rem;
font-size: 0.9rem;
```

### Label
```css
font-size: 0.75rem;
font-weight: 600;
text-transform: uppercase;
letter-spacing: 0.04em;
color: var(--custom-light-gray);  /* #2b2b2b99 */
```

### Checkbox
```css
accent-color: #153B4F;
```

### Date Picker (VueDatePicker)
```css
--dp-primary-color: #153b4f;
--dp-primary-text-color: #ffffff;
```

---

## Badges

### Hero Badge (Public)
```css
background: rgba(255, 255, 255, 0.07);
border: 1px solid rgba(255, 255, 255, 0.1);
color: #22d3ee;
border-radius: 999px;
padding: 0.4rem 0.9rem;
font-size: 0.85rem;
```

### Shadcn Badge Variants
```
default:     bg-[#009900] text-primary-foreground   /* green */
secondary:   bg-[#FFC633] text-secondary-foreground  /* gold */
destructive: bg-destructive text-destructive-foreground
outline:     border text-foreground
```

---

## Animations & Transitions

### Easing
```css
--ease-out: cubic-bezier(0.22, 1, 0.36, 1);   /* most animations */
--duration-fast: 150ms;
--duration-base: 250ms;
```

### Hover Lift Pattern
```css
transition: all 0.35s cubic-bezier(0.22, 1, 0.36, 1);
/* Hover */
transform: translateY(-4px) to translateY(-6px);
```

---

## Breakpoints

| Name | Width | Usage |
|---|---|---|
| `sm` | 640px | Mobile → Tablet |
| `md` | 768px | Tablet → Desktop |
| `lg` | 1024px | Desktop |
| `xl` | 1280px | Large desktop |
| `2xl` | 1400px | Container max |

---

## Component Library

| Component | Location | Pattern |
|---|---|---|
| Button | `Components/ui/button/` | CVA variants |
| Input | `Components/ui/input/` | Tailwind |
| Card | `Components/ui/card/` | Composable slots |
| Dialog | `Components/ui/dialog/` | Radix Vue |
| Badge | `Components/ui/badge/` | CVA variants |
| Alert | `Components/ui/alert/` | CVA variants |
| Select | `Components/ui/select/` | Radix Vue |
| Toast | `Components/ui/toast/` | Radix Vue |
| Table | `Components/ui/table/` | Composable slots |

### Legacy Components (still used)
| Component | File | Notes |
|---|---|---|
| PrimaryButton | `Components/PrimaryButton.vue` | `bg-customPrimaryColor`, pill shape |
| SecondaryButton | `Components/SecondaryButton.vue` | White bg, gray border |
| DangerButton | `Components/DangerButton.vue` | `bg-red-600` |
| Modal | `Components/Modal.vue` | CSS transitions |

---

## Logo

- **Component:** `Components/ApplicationLogo.vue`
- **Default color:** `#153b4f` (`:logoColor` prop)
- **On dark bg:** Use `logoColor="#ffffff"`
- **Dimensions:** 200x24 SVG
