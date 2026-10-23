---
name: inertia-frontend
description: Standards for Laravel + Inertia.js + Vue frontend architecture
---

# Inertia.js Frontend Standards

This project uses Laravel + Inertia.js + Vue 3.

## Architecture Rules

- Use Inertia pages instead of traditional SPA routing.
- Backend remains the source of truth.
- Keep routing inside Laravel routes.
- Use Vue only for UI rendering and interactions.

## Pages Structure

resources/js/
├── Pages/
├── Components/
├── Layouts/
├── Composables/
├── Services/
├── Types/
└── Utils/

## Inertia Rules

- Every page must use a layout.
- Use shared props carefully.
- Avoid sending unnecessary data from backend.
- Use partial reloads when possible.
- Prefer server-side filtering/pagination.

## Forms

- Use `useForm()` from Inertia.
- Handle validation using Laravel validation.
- Show validation errors clearly.
- Use processing/loading states.

Example:

```js
const form = useForm({
    name: '',
    email: '',
})

form.post(route('users.store'))
