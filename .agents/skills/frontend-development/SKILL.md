---
name: frontend-development
description: Frontend engineering standards for Vue.js and Laravel applications
---

# Frontend Development Standards

This project uses Vue.js with clean scalable frontend architecture.

## Rules

- Use Composition API.
- Prefer reusable components.
- Avoid duplicated UI logic.
- Separate business logic from UI.
- Use composables for shared state and logic.
- Use TypeScript when possible.

## Component Structure

- components/base → reusable UI components
- components/features → business feature components
- pages/ → route pages
- composables/ → reusable logic
- services/ → API calls
- stores/ → state management

## UI Rules

- Responsive on mobile/tablet/desktop.
- Avoid inline styles.
- Use consistent spacing system.
- Use loading states everywhere.
- Use empty states and validation states.
- Prefer accessibility-friendly components.

## API Integration

- All API calls go through service layer.
- Handle errors globally.
- Use interceptors for auth/token refresh.

## Performance

- Lazy load pages.
- Avoid unnecessary watchers.
- Use computed over watchers when possible.
- Split heavy components.
