# Gilllda Theme

Gilllda is a custom WordPress and WooCommerce theme developed for **gilllda.ir**. Built on top of a highly optimized, modern tech stack, this theme emphasizes performance, modularity, and an outstanding user experience.

## 🚀 Tech Stack & Core Technologies

*   **WordPress & WooCommerce:** The core CMS and e-commerce engine.
*   **Tailwind CSS (v4):** Used for all styling. The theme leverages a customized Tailwind setup via `_tw` (Tailwind for WordPress) structure, allowing utility-first, highly responsive, and maintainable CSS.
*   **Alpine.js:** Used for lightweight, reactive frontend interactions (e.g., cart modals, interactive elements) without the overhead of heavy JavaScript frameworks.
*   **Vite:** Acts as the build tool for fast asset compilation (Tailwind and JS scripts).

## 🛠 Features & Functionality

This theme is highly customized to support complex e-commerce and editorial requirements. It includes a variety of performance-enhancing hooks and features:

### E-Commerce & WooCommerce (Location: `theme/inc/product/`)
*   **AJAX Cart Modal:** A custom-built cart modal leveraging Alpine.js and WooCommerce cart fragments (`cart-fragments.php`, `cart-modal.php`) for seamless adding and removing of products.
*   **Custom Product Filtering:** Logic for filtering and sorting products, including prioritizing in-stock items and moving out-of-stock items to the end of the loop (`out-stock-product.php`, `product-filters.php`).
*   **Product Comparisons:** Built-in functionality for users to compare products (`compare-function.php`).
*   **Structured Data / SEO:** Injects custom Return Policy Schema and other rich snippets for better search engine visibility (`return-policy-schema.php`).
*   **Shop Adjustments:** Custom actions and loop adjustments specifically tailored for the Gilllda shop layout (`shop-actions.php`).

### Editorial & Content Features (Location: `theme/inc/post-type-functions/`)
*   **Estimated Reading Time:** Automatically calculates and displays the reading time for blog posts (`reading-time.php`).
*   **Post Views Counter:** Tracks and displays how many times a post has been viewed (`post-view.php`).
*   **Post Ratings:** Allows users to rate articles (`post-rating.php`).
*   **Table of Contents (TOC):** Automatically generates a table of contents for long-form content (`toc.php`).
*   **Persian Date Formatting:** Converts standard WordPress dates to the Jalali/Persian calendar format for local audience appropriateness (`persian-date.php`).
*   **Inline Advertisements:** Functionality to inject ads directly into the post content stream (`inline-ads.php`).

### Core & Integration (Location: `theme/inc/`)
*   **OTP Login:** Custom One-Time Password authentication flow replacing or augmenting standard login (`otp-login-form.php`).
*   **AJAX Search:** A high-performance, custom AJAX-powered search route and handler that returns JSON and pre-rendered HTML without reloading the page (`search-route.php`, `ajax-search.php`).
*   **Gravity Forms:** Integration and styling compatibility for Gravity Forms (`gravity-form.php`).
*   **Performance Optimization:** Aggressive cleanup of unnecessary WordPress bloat (emojis, default block CSS, etc.) to ensure lightning-fast load times (`optimize.php`).
*   **Custom Tailwind Menu Walker:** A specialized Walker class to render WordPress navigation menus using Tailwind CSS utility classes (`walker.php`).

## 📁 Directory Structure

*   `javascript/`: Contains the source JavaScript files. `script.js` handles the Alpine.js initialization and global scripts.
*   `tailwind/`: Contains the Tailwind source CSS (`tailwind-theme.css`) and configuration.
*   `theme/`: The core WordPress theme files.
    *   `header.php` & `footer.php`: Global layout structures.
    *   `homepage.php`, `about-us.php`, `contact-us.php`, `faq.php`: Custom page templates.
    *   `functions.php`: The main entry point that bootstraps all the includes.
    *   `inc/`: The modular engine of the theme, containing all custom hooks, features, and functionality separated into logical files.
    *   `template-parts/`: Reusable HTML/PHP snippets (e.g., `layout/cart-modal.php`, `layout/header-content.php`).
*   `package.json` & `vite.config.js`: Configuration for managing dependencies and the Vite build process.

## 💻 Development Workflow

To work on this theme locally:

1.  Navigate to the theme directory: `cd wp-content/themes/Gilllda`
2.  Install Node dependencies: `npm install`
3.  Start the development server (compiles Tailwind and JS on the fly): `npm run dev`
4.  For a production build (minifies and optimizes assets): `npm run build`

---
*This README was automatically generated to document the architecture and feature set of the Gilllda theme for future maintenance and scaling.*
