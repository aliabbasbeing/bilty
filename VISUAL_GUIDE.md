# Visual Guide - Bilty Management System

## Application Screenshots & Descriptions

### Dashboard (/)

**URL**: `http://localhost:8000`

**Layout**:
```
┌────────────────────────────────────────────────────────────┐
│ Navigation Bar (Gradient #97113a)                          │
│ [🚚 Bilty Management] Dashboard | Consignments | ...       │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│        Bilty Management System (Gradient Hero)              │
│   Welcome back! Manage your bilties, track shipments...    │
└────────────────────────────────────────────────────────────┘

┌─────────────┬─────────────┬─────────────┬─────────────────┐
│ 📄 Total    │ 💵 Total    │ ⏰ Pending  │ 📅 This Month   │
│ Bilties     │ Revenue     │ Balance     │                  │
│    X        │  Rs. XXX    │  Rs. XXX    │       X          │
└─────────────┴─────────────┴─────────────┴─────────────────┘

┌─────────────┬─────────────┬─────────────┬─────────────────┐
│ ➕ Create   │ 📋 View All │ 💰 Manage   │ 🏢 Manage       │
│ New Bilty   │ Bilties     │ Bills       │ Companies        │
│ Quick add.. │ Browse...   │ Generate... │ View and...      │
└─────────────┴─────────────┴─────────────┴─────────────────┘

┌────────────────────────────────────────────────────────────┐
│ 🔍 Quick Bilty Search                                      │
│ ┌────────────────────┐  ┌────────┐                        │
│ │ Enter bilty number │  │ Search │                        │
│ └────────────────────┘  └────────┘                        │
└────────────────────────────────────────────────────────────┘
```

**Features**:
- 4 animated KPI cards with hover effects
- 4 quick action cards (clickable)
- Search functionality with results display
- Gradient theme throughout
- Mobile responsive

---

### Consignments List (/consignments)

**URL**: `http://localhost:8000/consignments`

**Layout**:
```
┌────────────────────────────────────────────────────────────┐
│ 📄 Consignments              [➕ Create New Bilty]         │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ 🔍 Filter Consignments                                     │
│ ┌─────────────┬─────────────┬─────────────┬─────────────┐ │
│ │ Company ▼   │ From Date   │ To Date     │ Search      │ │
│ │ All Comp... │ YYYY-MM-DD  │ YYYY-MM-DD  │ Bilty no... │ │
│ └─────────────┴─────────────┴─────────────┴─────────────┘ │
│ [Apply Filters]  [Clear Filters]                          │
└────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ 📋 Consignments List (X total)                            │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ Bilty No │ Date │ Company │ Vehicle │ Route │ ... │⚙│ │
│ ├──────────────────────────────────────────────────────┤  │
│ │ 1001     │ ... │ ABC Ltd │ ABC-123 │ LHE→MLT │...│👁✏│ │
│ │ 1002     │ ... │ XYZ Ltd │ XYZ-456 │ KHI→ISB │...│👁✏│ │
│ └──────────────────────────────────────────────────────┘  │
│ « Previous  1  2  3  Next »                               │
└────────────────────────────────────────────────────────────┘
```

**Features**:
- Filter card with 4 inputs (company, dates, search)
- Responsive table with badges (Own/Rental, Fixed/PerKM)
- Color-coded balance (red if pending, green if paid)
- Pagination
- Action buttons (View 👁, Edit ✏)
- Empty state with "Create First Bilty" button

---

### Consignment Details (/consignments/{id})

**URL**: `http://localhost:8000/consignments/1`

**Layout**:
```
┌────────────────────────────────────────────────────────────┐
│ 📄 Consignment Details - 1001   [✏ Edit] [← Back to List] │
└────────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────────┐
│ 🏢 Company Information   │ 📦 Shipment Details              │
│                          │                                   │
│ Company: ABC Transport   │ Bilty No: 1001                   │
│ Address: 123 Main St...  │ Date: 13 Jan 2026                │
│                          │ Sender: Sender 1                  │
│                          │ Route: Lahore → Multan            │
│                          │ Quantity: 50                      │
└──────────────────────────┴──────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────────┐
│ 🚚 Vehicle & Driver Info │ 💰 Financial Details             │
│                          │                                   │
│ Vehicle: ABC-1234        │ Rate Type: [Per KM]              │
│ Ownership: [Own]         │ Distance: 300 km                 │
│ Vehicle Type: Truck      │ Rate: Rs. 100 per km             │
│ Driver: Ahmed Ali        │ Amount: Rs. 30,000               │
│ Contact: +92 3XX...      │ Advance: Rs. 15,000              │
│                          │ Balance: Rs. 15,000 (red)        │
└──────────────────────────┴──────────────────────────────────┘

┌────────────────────────────────────────────────────────────┐
│ 📝 Additional Notes                                        │
│ Sample consignment details                                 │
└────────────────────────────────────────────────────────────┘
```

**Features**:
- 4 main information cards (Company, Shipment, Vehicle, Financial)
- Conditional displays (notes, payment history)
- Badge system for status
- Color-coded balance (red if pending)
- Edit and Back buttons
- Professional card layout

---

## Color Scheme

### Primary Colors
- **Main**: `#97113a` (Burgundy/Maroon)
- **Hover**: `#b31547` (Lighter Burgundy)
- **Gradient**: `linear-gradient(135deg, #97113a 0%, #c91f4f 100%)`

### Background Colors
- **Page Background**: `#f0f2f5` (Light Gray)
- **Card Background**: `#ffffff` (White)
- **Table Header**: `#f9fafb` (Very Light Gray)

### Status Colors
- **Success**: `#10b981` (Green) - Own vehicles, Paid status
- **Warning**: `#f59e0b` (Orange) - Rental vehicles
- **Danger**: `#ef4444` (Red) - Pending balance
- **Info**: `#3b82f6` (Blue) - Fixed rate type
- **Primary**: `#97113a` (Burgundy) - PerKM rate type

### Badges
```
[Own]          - Green badge
[Rental]       - Yellow/Orange badge
[Fixed]        - Blue badge
[Per KM]       - Purple/Primary badge
[Paid]         - Green text
Rs. 15,000     - Red text (if balance > 0)
```

---

## Responsive Breakpoints

### Desktop (> 1200px)
- 4 columns for KPI cards
- 4 columns for action cards
- Full table display

### Tablet (768px - 1200px)
- 2 columns for cards
- Horizontal scrolling for tables
- Collapsible navigation

### Mobile (< 768px)
- 1 column for all cards
- Stacked layout
- Hamburger menu
- Touch-friendly buttons

---

## Icon Usage

### Navigation
- 🚚 Bilty Management (Brand)
- 🏠 Dashboard
- 📄 Consignments
- 🏢 Companies
- 💰 Bills

### KPI Cards
- 📄 Total Bilties
- 💵 Total Revenue
- ⏰ Pending Balance
- 📅 This Month

### Actions
- ➕ Create/Add
- 👁 View
- ✏ Edit
- 🗑 Delete
- 🔍 Search
- 🔽 Filter
- ← Back
- 📊 Reports
- 🖨 Print

### Status
- ✓ Success
- ⚠ Warning
- ✕ Error
- ℹ Info

---

## Typography

### Font Family
```css
-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif
```

### Font Sizes
- **Hero Title**: 2.5rem (40px) - Bold 800
- **Page Title**: 2rem (32px) - Bold 700
- **Card Title**: 1.25rem (20px) - Bold 600
- **Body Text**: 1rem (16px) - Regular 400
- **Small Text**: 0.875rem (14px) - Regular 400
- **Labels**: 0.875rem (14px) - Medium 500

### Text Colors
- **Primary**: `#111827` (Almost Black)
- **Secondary**: `#6b7280` (Gray)
- **Muted**: `#9ca3af` (Light Gray)
- **White**: `#ffffff`

---

## Animation Effects

### Hover Effects
```css
/* Cards */
transform: translateY(-4px);
box-shadow: 0 8px 20px rgba(0,0,0,0.12);

/* Buttons */
transform: translateY(-2px);
box-shadow: 0 4px 12px rgba(151, 17, 58, 0.3);
```

### Transitions
```css
transition: all 0.2s ease-in-out;
```

---

## UI Components

### Cards
- **Border Radius**: 16px
- **Padding**: 1.5rem (24px)
- **Shadow**: `0 2px 8px rgba(0,0,0,0.08)`
- **Hover Shadow**: `0 8px 20px rgba(0,0,0,0.12)`

### Buttons
- **Primary**: Gradient background, white text
- **Secondary**: Light gray background, dark text
- **Warning**: Orange background, dark text
- **Padding**: 0.875rem 2rem
- **Border Radius**: 10px

### Tables
- **Border**: 1px solid #f3f4f6
- **Header Background**: #f9fafb
- **Row Hover**: #f9fafb
- **Cell Padding**: 1rem

### Badges
- **Border Radius**: 4px
- **Padding**: 0.25rem 0.5rem
- **Font Size**: 0.75rem
- **Font Weight**: 500

---

## Sample Data

When seeded, the application includes:
- **3 Companies**: ABC Transport Ltd, XYZ Logistics, Fast Cargo Services
- **6-9 Consignments**: Realistic Pakistani data (names, cities, phone numbers)
- **5 Maintenance Records**: Oil change, tire replacement, etc.
- **Cities**: Lahore, Karachi, Islamabad, Multan, Faisalabad, Rawalpindi
- **Driver Names**: Ahmed Ali, Muhammad Hassan, Ali Raza, Usman Khan

---

## Browser Support

### Tested & Supported
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### Features Used
- CSS Grid
- Flexbox
- CSS Variables
- Modern JavaScript (ES6+)
- Bootstrap 5

---

This visual guide provides a comprehensive overview of the application's appearance and user interface without requiring actual screenshots.
