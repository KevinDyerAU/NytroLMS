# KeyLMS Nytro UI Rewrite: Comprehensive Plan

**Version:** 1.0
**Date:** February 8, 2026

## 1. Introduction

This document outlines the comprehensive plan for rewriting the KeyLMS Nytro UI to match the modern look and feel of the NytroAI application. The goal is to create a fast, responsive, and visually appealing user interface using a modern technology stack while leveraging the existing Laravel backend APIs. This phased approach will allow for a gradual and controlled migration, minimizing disruption and risk.

## 2. High-Level Strategy

The core strategy is to build a new React-based Single-Page Application (SPA) that will replace the current Laravel Blade-rendered frontend. This new frontend will be developed within the `KeyLMSNytro` repository and will communicate with the existing Laravel backend via its API routes. This approach allows us to modernize the user experience without immediately rewriting the entire backend, which can be migrated to Supabase Edge Functions in a later phase.

## 3. Technology Stack

The new frontend will be built using the following modern technologies, mirroring the successful stack of NytroAI:

-   **Framework:** React
-   **Language:** TypeScript
-   **Build Tool:** Vite
-   **Styling:** Tailwind CSS
-   **UI Components:** shadcn/ui
-   **Routing:** React Router
-   **State Management:** React Context API (for now, can be upgraded to a more robust solution if needed)

## 4. Design System Adoption

To achieve the desired NytroAI look and feel, we will adopt its design system:

-   **Colors:** We will use the same color palette, including the Nytro mint and blue, as well as the neutral grays for backgrounds and text.
-   **Typography:** We will use the same fonts: "Outfit" for headings and "Inter" for body text.
-   **Layout:** We will replicate the spacious, clean layouts with consistent padding and margins.
-   **Components:** We will use `shadcn/ui` to create a component library that mirrors the NytroAI components, ensuring visual consistency.

## 5. Phased Implementation Plan

The UI rewrite will be implemented in the following phases:

### Phase 1: Project Scaffolding and Setup

1.  **Initialize Project:** Use `webdev_init_project` to scaffold a new `web-static` React project within the `KeyLMSNytro` repository, in a new `frontend` directory.
2.  **Install Dependencies:** Install necessary dependencies, including `react-router-dom`, `lucide-react`, and `sonner` for toast notifications.
3.  **Configure Tailwind CSS:** Configure `tailwind.config.js` with the NytroAI color palette and fonts.
4.  **Setup `shadcn/ui`:** Initialize `shadcn/ui` to create the UI component library.

### Phase 2: Core Layout and Navigation

1.  **Create Main Layout:** Build the main application layout, including the sidebar navigation and main content area, replicating the NytroAI dashboard layout.
2.  **Implement Sidebar:** Create the sidebar navigation component, including the Nytro logo, navigation links, and user profile section.
3.  **Implement Routing:** Set up the basic routing structure using React Router.

### Phase 3: Authentication

1.  **Create Login Page:** Build the login page with the same design as the NytroAI login page, including the split-screen layout with branding on the left and the form on the right.
2.  **Implement Login Logic:** Connect the login form to the existing Laravel authentication API endpoints.
3.  **Create Auth Context:** Create a React context to manage the user's authentication state.
4.  **Implement Protected Routes:** Create a protected route component to restrict access to authenticated users.

### Phase 4: Dashboard and Core Pages

1.  **Build Dashboard:** Replicate the NytroAI dashboard, including the KPI widgets and recent activity feed. This will require creating new API endpoints in Laravel to provide the necessary data.
2.  **Replicate Key Pages:** Re-implement the most critical pages from the existing KeyLMS application, such as the course list, course details, and user profile pages, using the new design system.

### Phase 5: Feature Parity

1.  **Inventory Existing Features:** Create a detailed inventory of all features and pages in the current KeyLMS application.
2.  **Prioritize and Implement:** Prioritize the remaining features and implement them in the new React frontend, ensuring full feature parity with the old UI.

## 6. Laravel API Requirements

While we will leverage existing API routes as much as possible, some new endpoints will be required to support the new UI. These include:

-   An endpoint to fetch data for the new dashboard widgets.
-   Endpoints to support any new features or interactions introduced in the new UI.
-   Potentially, modifications to existing endpoints to better suit the needs of the React frontend.

## 7. Next Steps

With this plan in place, the next immediate steps are:

1.  Create a new branch for the UI rewrite.
2.  Scaffold the new React project.
3.  Begin implementation of Phase 2: Core Layout and Navigation.
4.  Create a Pull Request to track the progress of the UI rewrite.
