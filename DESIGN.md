# Design System

## Visual direction

Sertifikasi Academy uses a soft-modern EdTech interface for students, instructors, and campus administrators. Page structure and controls remain conventional and task-focused; the visual language comes through a clear type hierarchy, calm violet-led color, generous spacing, and a single recurring course-progress path.

## Tokens

The shared tokens are defined in `sertifikasi/asset/css/theme-soft.css` under `:root`.

- Page ground: very light neutral gray; surfaces: white.
- Primary: violet with a lighter violet for gradients and a darker violet for readable emphasis.
- Secondary: soft yellow for supporting information; mint and peach remain status accents.
- Typography: Plus Jakarta Sans, weights 400–700.
- Cards: 22px radius and a soft violet-tinted elevation shadow.
- Controls: pill buttons and rounded form fields with visible violet focus rings.

## Shared components

- Top navigation with a dark pill for the active destination.
- White cards for courses, profile sections, tables, dashboard panels, and dialogs.
- Violet primary actions; outlined neutral secondary actions.
- Empty, disabled, error, success, and warning states use distinct labels and accessible contrast.
- The course outline uses a violet progress path to show movement through modules without replacing familiar navigation controls.

## Surface behavior

- Authentication, setup, account recovery, and account restriction pages share the same typography, controls, and surface treatment.
- Student home, course overview, lesson/quiz, profile, and certificate pages share the same tokens while retaining their task-specific content and structure.
- Instructor and administrator dashboards use the same card, form, table, and navigation language.
- The certificate print layout uses the same violet and neutral palette while preserving its landscape print format.
- Responsive layouts stack content for narrow screens; wide tables remain horizontally scrollable within their container.
- Backend behavior, routes, form fields, and SQL are outside the visual redesign scope and must remain unchanged.
