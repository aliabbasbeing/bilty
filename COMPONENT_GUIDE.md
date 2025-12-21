# Bilty Management System - Visual Component Guide

This guide provides quick reference examples for using the modernized UI components.

## Color Palette

### Primary Colors
- **Primary**: `#97113a` - Main brand color (Maroon/Burgundy)
- **Primary Hover**: `#b31547` - Hover state
- **Primary Light**: `#fff0f5` - Light backgrounds
- **Primary Dark**: `#7a0e2f` - Dark accents

### Status Colors
- **Success**: `#10b981` (Green) - Successful operations
- **Warning**: `#f59e0b` (Orange) - Warnings
- **Danger**: `#ef4444` (Red) - Errors/Delete actions
- **Info**: `#3b82f6` (Blue) - Information

## Button Examples

```html
<!-- Primary Button -->
<button class="btn-modern btn-primary">
  <i class="fa-solid fa-plus"></i>
  Add New Bilty
</button>

<!-- Secondary Button -->
<button class="btn-modern btn-secondary">
  Cancel
</button>

<!-- Success Button -->
<button class="btn-modern btn-success">
  <i class="fa-solid fa-check"></i>
  Confirm
</button>

<!-- Danger Button -->
<button class="btn-modern btn-danger">
  <i class="fa-solid fa-trash"></i>
  Delete
</button>
```

## Form Components

```html
<!-- Text Input -->
<div>
  <label class="form-label-modern">Company Name</label>
  <input type="text" class="form-control-modern" placeholder="Enter company name">
</div>

<!-- Select Dropdown -->
<div>
  <label class="form-label-modern">Select Company</label>
  <select class="form-control-modern">
    <option value="">-- Select --</option>
    <option value="1">Company A</option>
    <option value="2">Company B</option>
  </select>
</div>

<!-- Textarea -->
<div>
  <label class="form-label-modern">Notes</label>
  <textarea class="form-control-modern" rows="4" placeholder="Enter notes"></textarea>
</div>
```

## Table Component

```html
<div class="table-wrapper">
  <table class="table-modern">
    <thead>
      <tr>
        <th>Bilty No</th>
        <th>Date</th>
        <th>Company</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>#12345</td>
        <td>2024-12-21</td>
        <td>ABC Transport</td>
        <td>Rs. 50,000</td>
        <td><span class="badge-modern badge-success">Paid</span></td>
        <td>
          <button class="btn-modern btn-primary">View</button>
        </td>
      </tr>
      <tr>
        <td>#12346</td>
        <td>2024-12-20</td>
        <td>XYZ Logistics</td>
        <td>Rs. 35,000</td>
        <td><span class="badge-modern badge-warning">Pending</span></td>
        <td>
          <button class="btn-modern btn-primary">View</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

## Badge Components

```html
<!-- Success Badge -->
<span class="badge-modern badge-success">
  <i class="fa-solid fa-check"></i>
  Paid
</span>

<!-- Warning Badge -->
<span class="badge-modern badge-warning">
  <i class="fa-solid fa-clock"></i>
  Pending
</span>

<!-- Danger Badge -->
<span class="badge-modern badge-danger">
  <i class="fa-solid fa-exclamation"></i>
  Overdue
</span>

<!-- Info Badge -->
<span class="badge-modern badge-info">
  <i class="fa-solid fa-info"></i>
  Processing
</span>

<!-- Primary Badge -->
<span class="badge-modern badge-primary">
  New
</span>
```

## Card Components

```html
<!-- Modern Card -->
<div class="modern-card" style="padding: 1.5rem;">
  <h3 style="margin-bottom: 1rem;">Card Title</h3>
  <p>Card content goes here with consistent padding and styling.</p>
</div>

<!-- Theme Surface (for main content areas) -->
<div class="theme-surface" style="padding: 2rem;">
  <h1 style="color: var(--primary); margin-bottom: 1rem;">Main Section</h1>
  <p>Primary content area with enhanced gradient background.</p>
</div>
```

## Stat/KPI Cards

```html
<!-- Single KPI Card -->
<div class="stat-card">
  <div class="stat-icon">
    <i class="fa-solid fa-truck"></i>
  </div>
  <div class="stat-label">Total Bilties</div>
  <div class="stat-value">1,234</div>
</div>

<!-- KPI Grid Layout -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
  <div class="stat-card">
    <div class="stat-icon">
      <i class="fa-solid fa-file-invoice-dollar"></i>
    </div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">Rs. 5.2M</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon">
      <i class="fa-solid fa-check-circle"></i>
    </div>
    <div class="stat-label">Paid Bills</div>
    <div class="stat-value">856</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-icon">
      <i class="fa-solid fa-clock"></i>
    </div>
    <div class="stat-label">Pending</div>
    <div class="stat-value">378</div>
  </div>
</div>
```

## Alert Messages

```html
<!-- Success Alert -->
<div class="alert-modern alert-success">
  <i class="fa-solid fa-check-circle"></i>
  <div>
    <strong>Success!</strong> Your changes have been saved.
  </div>
</div>

<!-- Warning Alert -->
<div class="alert-modern alert-warning">
  <i class="fa-solid fa-exclamation-triangle"></i>
  <div>
    <strong>Warning!</strong> Please review the information before proceeding.
  </div>
</div>

<!-- Danger Alert -->
<div class="alert-modern alert-danger">
  <i class="fa-solid fa-times-circle"></i>
  <div>
    <strong>Error!</strong> Something went wrong. Please try again.
  </div>
</div>

<!-- Info Alert -->
<div class="alert-modern alert-info">
  <i class="fa-solid fa-info-circle"></i>
  <div>
    <strong>Info:</strong> Here's some helpful information.
  </div>
</div>
```

## Loading Spinner

```html
<div style="text-align: center; padding: 2rem;">
  <div class="loading-spinner"></div>
  <p style="margin-top: 1rem; color: var(--text-secondary);">Loading...</p>
</div>
```

## Pagination

```html
<div class="pagination-modern">
  <a href="?page=1" class="page-link disabled">
    <i class="fa-solid fa-chevron-left"></i>
  </a>
  <a href="?page=1" class="page-link active">1</a>
  <a href="?page=2" class="page-link">2</a>
  <a href="?page=3" class="page-link">3</a>
  <span class="page-link disabled">...</span>
  <a href="?page=10" class="page-link">10</a>
  <a href="?page=2" class="page-link">
    <i class="fa-solid fa-chevron-right"></i>
  </a>
</div>
```

## Filter Section

```html
<div class="filter-section">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
    <div>
      <label class="form-label-modern">Start Date</label>
      <input type="date" class="form-control-modern">
    </div>
    <div>
      <label class="form-label-modern">End Date</label>
      <input type="date" class="form-control-modern">
    </div>
    <div>
      <label class="form-label-modern">Company</label>
      <select class="form-control-modern">
        <option>All Companies</option>
        <option>Company A</option>
      </select>
    </div>
    <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
      <button class="btn-modern btn-primary" style="flex: 1;">
        <i class="fa-solid fa-filter"></i>
        Apply Filters
      </button>
      <button class="btn-modern btn-secondary">
        <i class="fa-solid fa-times"></i>
        Clear
      </button>
    </div>
  </div>
</div>
```

## Action Button Group

```html
<div class="action-group">
  <button class="btn-modern btn-primary">
    <i class="fa-solid fa-plus"></i>
    Add New
  </button>
  <button class="btn-modern btn-secondary">
    <i class="fa-solid fa-download"></i>
    Export CSV
  </button>
  <button class="btn-modern btn-secondary">
    <i class="fa-solid fa-print"></i>
    Print
  </button>
</div>
```

## Responsive Grid Layout

```html
<!-- 2-column on desktop, 1-column on mobile -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
  <div class="modern-card" style="padding: 1.5rem;">
    <h3>Section 1</h3>
    <p>Content here</p>
  </div>
  <div class="modern-card" style="padding: 1.5rem;">
    <h3>Section 2</h3>
    <p>Content here</p>
  </div>
</div>
```

## CSS Variables Reference

You can use these CSS variables throughout your custom styles:

```css
/* Colors */
var(--primary)
var(--primary-hover)
var(--primary-light)
var(--primary-dark)
var(--success)
var(--warning)
var(--danger)
var(--info)

/* Backgrounds */
var(--page-bg)
var(--surface-bg)
var(--surface-alt)

/* Text */
var(--text-primary)
var(--text-secondary)
var(--text-light)

/* Borders & Shadows */
var(--border-color)
var(--shadow-sm)
var(--shadow-md)
var(--shadow-lg)

/* Spacing */
var(--spacing-xs) /* 0.25rem */
var(--spacing-sm) /* 0.5rem */
var(--spacing-md) /* 1rem */
var(--spacing-lg) /* 1.5rem */
var(--spacing-xl) /* 2rem */

/* Border Radius */
var(--radius-sm)
var(--radius-md)
var(--radius-lg)
var(--radius-xl)
var(--radius-2xl)

/* Typography */
var(--font-size-xs)
var(--font-size-sm)
var(--font-size-base)
var(--font-size-lg)
var(--font-size-xl)
```

## Custom Styling Example

```css
/* Create a custom component using theme variables */
.my-custom-component {
  background: var(--surface-bg);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  color: var(--text-primary);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
}

.my-custom-component:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
```

## Responsive Breakpoints

```css
/* Mobile First Approach */
/* Default styles apply to mobile */

/* Tablet (768px and up) */
@media (min-width: 768px) {
  /* Tablet styles */
}

/* Desktop (1024px and up) */
@media (min-width: 1024px) {
  /* Desktop styles */
}
```

## Tips & Best Practices

1. **Consistency**: Always use the theme CSS classes for common components
2. **Variables**: Use CSS variables instead of hardcoded colors
3. **Responsive**: Test on mobile, tablet, and desktop
4. **Accessibility**: Include proper ARIA labels and focus states
5. **Performance**: Avoid inline styles when possible, use classes
6. **Maintainability**: Follow the existing patterns in theme.css

---

For more details, see [MODERNIZATION.md](MODERNIZATION.md)
