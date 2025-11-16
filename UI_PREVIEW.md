# PC Hardware Inventory System - UI Preview

## Visual Design Overview

This document describes the visual appearance of the PC Hardware Inventory System.

---

## 🎨 Color Scheme

### Primary Colors
- **Primary Gradient**: `#667eea` → `#764ba2` (Purple gradient)
- **Bootstrap Primary**: `#0d6efd` (Blue)
- **Success**: `#198754` (Green)
- **Warning**: `#ffc107` (Yellow/Orange)
- **Danger**: `#dc3545` (Red)
- **Info**: `#0dcaf0` (Cyan)
- **Secondary**: `#6c757d` (Gray)

### UI Elements
- **Card Background**: White (`#ffffff`)
- **Page Background**: Light gray (`#f8f9fa`)
- **Text Primary**: Dark (`#0f172a`)
- **Text Muted**: Gray (`#6b7280`)

---

## 📱 Page Layouts

### 1. Login Page

```
┌─────────────────────────────────────────────┐
│                                             │
│        [Purple Gradient Background]        │
│                                             │
│  ┌───────────────────────────────────┐    │
│  │     Purple Gradient Header        │    │
│  │     [Computer Icon]               │    │
│  │   PC Hardware Inventory           │    │
│  │   Sign in to your account         │    │
│  └───────────────────────────────────┘    │
│  ┌───────────────────────────────────┐    │
│  │   White Card Body                 │    │
│  │                                   │    │
│  │   [Username Icon] Username        │    │
│  │   [___________________]           │    │
│  │                                   │    │
│  │   [Lock Icon] Password            │    │
│  │   [___________________] [Eye]     │    │
│  │                                   │    │
│  │   ☐ Remember me                   │    │
│  │                                   │    │
│  │   [    Sign In Button    ]        │    │
│  │                                   │    │
│  │   Demo Credentials:               │    │
│  │   Admin: admin / password123      │    │
│  │   Staff: staff01 / password123    │    │
│  └───────────────────────────────────┘    │
│                                             │
└─────────────────────────────────────────────┘
```

**Features:**
- Full-screen gradient background
- Centered white card with shadow
- Purple gradient header
- Input fields with icons
- Password visibility toggle
- Demo credentials display
- Responsive design

---

### 2. Dashboard

```
┌─────────────────────────────────────────────────────────────┐
│ [Primary Blue Navigation Bar]                               │
│ 🖥️ PC Inventory | Dashboard | Hardware | History | Users   │
│                                    👤 John Admin [Logout]   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 📊 Dashboard                                                │
│ Welcome back, John Admin!                                   │
└─────────────────────────────────────────────────────────────┘

┌──────────┬──────────┬──────────┬──────────┬──────────┬──────────┐
│ 📦 Total │ 📚 Total │ ✅ Avail │ ⚠️ In   │ ⛔ Damag │ 🔧 In   │
│   Items  │ Quantity │   able   │   Use   │   aged   │  Repair  │
│    6     │    35    │    15    │    14   │    4     │    2     │
│  (Blue)  │  (Cyan)  │ (Green)  │(Yellow) │  (Red)   │  (Gray)  │
└──────────┴──────────┴──────────┴──────────┴──────────┴──────────┘

┌─────────────────────────────────┬─────────────────────────────────┐
│ 🕐 Recent Hardware              │ ⚠️ Low Stock Alert              │
│ [View All]                      │                                 │
├─────────────────────────────────┼─────────────────────────────────┤
│ Name         Category   Qty Avl │ Name         Category   Avl Sts│
│ ────         ────────   ─── ─── │ ────         ────────   ─── ───│
│ Dell Monitor [Monitor]  7   5   │ Seagate HDD  [HDD]      1   ⚠️ │
│ NVIDIA GTX   [GPU]      3   1   │ Intel i5     [CPU]      2   ⚠️ │
│ Samsung SSD  [SSD]      6   3   │                                 │
│ Seagate HDD  [HDD]      4   1   │ ✅ All items in stock!         │
│ Corsair RAM  [RAM]     10   4   │                                 │
└─────────────────────────────────┴─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 📂 Categories Summary                                       │
├────────────┬────────────┬────────────┬────────────┬────────┤
│    CPU     │    RAM     │    SSD     │    HDD     │  GPU   │
│  Items: 1  │  Items: 1  │  Items: 1  │  Items: 1  │ Items:1│
│  Total: 5  │  Total:10  │  Total: 6  │  Total: 4  │ Total:3│
└────────────┴────────────┴────────────┴────────────┴────────┘
```

**Features:**
- 6 statistics cards with icons and colors
- Two-column layout for recent items and alerts
- Category summary with multiple cards
- Color-coded badges for categories
- Responsive grid layout

---

### 3. Hardware Management

```
┌─────────────────────────────────────────────────────────────┐
│ 🖥️ Hardware Management                [+ Add Hardware]      │
│ Manage your hardware inventory                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 📋 All Hardware              [Search: _______] [📥 Export]  │
├───┬──────┬────────┬────────┬───┬────┬────┬────┬────┬──────┤
│Nm │ Cat  │ Brand  │ Serial │Tot│Avl │Use │Dmg │Rep │Action│
├───┼──────┼────────┼────────┼───┼────┼────┼────┼────┼──────┤
│i5 │[CPU] │Intel   │SNC001  │ 5 │ 2  │ 2  │ 1  │ 0  │✏️🗑️ │
│RAM│[RAM] │Corsair │SNR001  │10 │ 4  │ 3  │ 2  │ 1  │✏️🗑️ │
│SSD│[SSD] │Samsung │SNS001  │ 6 │ 3  │ 2  │ 1  │ 0  │✏️🗑️ │
│HDD│[HDD] │Seagate │SNH001  │ 4 │ 1  │ 2  │ 1  │ 0  │✏️🗑️ │
│GPU│[GPU] │NVIDIA  │SNG001  │ 3 │ 1  │ 2  │ 0  │ 0  │✏️🗑️ │
│MON│[MON] │Dell    │SNM001  │ 7 │ 5  │ 2  │ 0  │ 0  │✏️🗑️ │
└───┴──────┴────────┴────────┴───┴────┴────┴────┴────┴──────┘
```

**Modal Form (Add/Edit):**
```
┌─────────────────────────────────────┐
│ ➕ Add New Hardware            [×]  │
├─────────────────────────────────────┤
│ Hardware Name *: [____________]     │
│ Category *:      [▼ Select     ]    │
│                                     │
│ Type:            [____________]     │
│ Brand:           [____________]     │
│ Model:           [____________]     │
│                                     │
│ Serial Number:   [____________]     │
│ Location:        [____________]     │
│                                     │
│ ────────────────────────────────    │
│ Available:  [ 0 ]   In Use:  [ 0 ]  │
│ Damaged:    [ 0 ]   Repair:  [ 0 ]  │
│                                     │
│ ℹ️ Total Quantity: 0                │
│                                     │
│        [Cancel] [Add Hardware]      │
└─────────────────────────────────────┘
```

**Features:**
- Comprehensive table with all hardware
- Search filters in real-time
- CSV export button
- Modal forms for add/edit
- Color-coded quantity badges
- Inline edit/delete buttons
- Responsive table with scroll

---

### 4. Inventory History

```
┌─────────────────────────────────────────────────────────────┐
│ 🕐 Inventory History                                        │
│ Track all changes made to hardware inventory               │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 📋 Recent Activities                    [Search: _______]   │
├──────┬──────┬─────┬──────┬──────┬──────┬────────┬─────────┤
│Date  │HW    │Cat  │Action│User  │Change│ Before │ After   │
├──────┼──────┼─────┼──────┼──────┼──────┼────────┼─────────┤
│Nov14 │i5    │[CPU]│Added │Admin │ +5   │0|0|0|0 │2|2|1|0 │
│Nov14 │RAM   │[RAM]│Update│Staff │ +2   │2|2|2|1 │4|3|2|1 │
│Nov13 │SSD   │[SSD]│Added │Admin │ +6   │0|0|0|0 │3|2|1|0 │
└──────┴──────┴─────┴──────┴──────┴──────┴────────┴─────────┘

Legend: A = Available | U = In Use | D = Damaged | R = Repair
```

**Features:**
- Complete audit trail
- Color-coded action badges
- Before/after values shown
- User attribution
- Searchable records
- Timestamp for each action

---

### 5. User Management (Admin Only)

```
┌─────────────────────────────────────────────────────────────┐
│ 👥 User Management                      [+ Add User]        │
│ Manage system users and permissions                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ 📋 All Users                            [Search: _______]   │
├──────────────┬─────────────┬──────┬─────────────┬─────────┤
│ Username     │ Full Name   │ Role │ Date Created│ Actions │
├──────────────┼─────────────┼──────┼─────────────┼─────────┤
│ admin [You]  │ John Admin  │[Admin]│ Nov 10, 2024│  ✏️    │
│ staff01      │ Mary Staff  │[Staff]│ Nov 10, 2024│ ✏️🗑️  │
└──────────────┴─────────────┴──────┴─────────────┴─────────┘
```

**Modal Form (Add/Edit User):**
```
┌─────────────────────────────────────┐
│ ➕ Add New User               [×]   │
├─────────────────────────────────────┤
│ Username *:      [____________]     │
│ Full Name *:     [____________]     │
│ Password *:      [____________]     │
│                  Min 6 characters   │
│ Role *:          [▼ Select     ]    │
│                  ○ Staff            │
│                  ○ Admin            │
│                                     │
│        [Cancel] [Add User]          │
└─────────────────────────────────────┘
```

**Features:**
- User listing table
- Role badges (red for Admin, gray for Staff)
- "You" indicator for current user
- Cannot delete own account
- Password reset option
- Modal forms for add/edit

---

## 🎯 UI Components

### Navigation Bar
- **Color**: Primary blue gradient
- **Style**: Fixed top, shadow
- **Contents**: Logo, menu items, user dropdown
- **Mobile**: Hamburger menu (collapsible)

### Cards
- **Style**: White background, rounded corners, shadow
- **Header**: Gradient background (purple)
- **Hover**: Lift effect (translateY)
- **Animation**: Smooth transitions

### Buttons
- **Primary**: Blue gradient, white text
- **Secondary**: Light blue, blue text
- **Success**: Green
- **Danger**: Red
- **Info**: Cyan
- **Style**: Rounded, hover lift effect

### Badges
- **Primary**: Blue (categories)
- **Success**: Green (available)
- **Warning**: Yellow (in use)
- **Danger**: Red (damaged)
- **Secondary**: Gray (repair)
- **Info**: Cyan (total)

### Tables
- **Header**: Light gray background
- **Rows**: White, hover gray
- **Border**: Light gray lines
- **Style**: Clean, minimal
- **Responsive**: Horizontal scroll on mobile

### Forms
- **Inputs**: Rounded, border, shadow on focus
- **Labels**: Bold, gray text
- **Validation**: Red border, error messages
- **Style**: Clean, spacious

### Modals
- **Header**: Purple gradient
- **Body**: White, padded
- **Footer**: Light gray
- **Animation**: Fade in/out
- **Backdrop**: Semi-transparent dark

---

## 📱 Responsive Breakpoints

### Desktop (≥992px)
- Full navigation bar
- Multi-column layouts
- Large cards
- Full table view

### Tablet (768px - 991px)
- Collapsible navigation
- 2-column layouts
- Medium cards
- Scrollable tables

### Mobile (<768px)
- Hamburger menu
- Single column layout
- Stacked cards
- Horizontal scroll tables
- Touch-friendly buttons

---

## 🎨 Visual Hierarchy

### Size Hierarchy
1. **H1**: Page titles (28px)
2. **H2**: Section titles (24px)
3. **H5**: Card headers (20px)
4. **Body**: Regular text (16px)
5. **Small**: Helper text (13px)

### Color Hierarchy
1. **Primary Actions**: Blue buttons
2. **Success States**: Green badges
3. **Warnings**: Yellow/Orange alerts
4. **Errors**: Red messages
5. **Info**: Cyan highlights
6. **Neutral**: Gray text/borders

### Spacing Hierarchy
- **Page padding**: 24px
- **Card padding**: 28px
- **Section gaps**: 16px
- **Element gaps**: 8-12px

---

## ✨ Animation Effects

### Hover Effects
- **Cards**: Scale up slightly, shadow increase
- **Buttons**: Lift (translateY -2px), shadow
- **Links**: Color change
- **Table rows**: Background color change

### Transitions
- **Duration**: 0.2s - 0.3s
- **Easing**: ease-out, ease-in-out
- **Properties**: transform, box-shadow, background-color

### Page Loads
- **Alerts**: Slide in from top
- **Modals**: Fade in with backdrop
- **Tables**: Instant render (no animation)

---

## 🖼️ Icons

**Library**: Bootstrap Icons 1.10+

**Common Icons Used**:
- 🖥️ `bi-pc-display` - Logo, hardware
- 📊 `bi-speedometer2` - Dashboard
- 🔧 `bi-cpu` - Hardware
- 🕐 `bi-clock-history` - History
- 👥 `bi-people` - Users
- 👤 `bi-person-circle` - User profile
- ➕ `bi-plus-circle` - Add action
- ✏️ `bi-pencil` - Edit action
- 🗑️ `bi-trash` - Delete action
- 🔒 `bi-lock` - Password
- 👁️ `bi-eye` - Show password
- 📤 `bi-box-arrow-right` - Logout
- 📥 `bi-download` - Export
- 🔍 `bi-search` - Search
- ⚠️ `bi-exclamation-triangle` - Warning
- ✅ `bi-check-circle` - Success
- ℹ️ `bi-info-circle` - Information

---

## 🎯 Design Principles

### 1. Consistency
- Same colors throughout
- Consistent spacing
- Uniform button styles
- Standard icon usage

### 2. Clarity
- Clear labels
- Obvious actions
- Visual feedback
- Helpful messages

### 3. Simplicity
- Clean layouts
- Minimal clutter
- Focus on content
- Easy navigation

### 4. Accessibility
- High contrast text
- Large touch targets
- Keyboard navigation
- Screen reader support

### 5. Responsiveness
- Mobile-first approach
- Flexible layouts
- Scalable components
- Touch-friendly

---

**Note**: This is a text-based representation of the UI. The actual implementation uses HTML, CSS (Bootstrap 5), and JavaScript for interactive features.

For the best experience, access the system in a web browser after installation.
