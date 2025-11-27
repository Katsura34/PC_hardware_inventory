# UI/UX Documentation - HCI Improvements

This document describes the UI changes made to follow Human-Computer Interaction (HCI) principles, using a simple color palette and improved layout.

## Color Tokens

The system uses CSS custom properties (variables) for easy customization. Edit these in `assets/css/theme-hci.css`:

```css
:root {
    /* Primary Blue - matches logo/header */
    --primary: #1e6fb8;
    --primary-hover: #1a5f9e;
    --primary-contrast: #ffffff;
    
    /* Neutral Grays */
    --muted: #6b7280;
    --bg-page: #f5f7f9;
    --card-bg: #ffffff;
    --border: #e6e8eb;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    
    /* Semantic Colors */
    --danger: #e04646;
    --success: #2ea44f;
    --warning: #d97706;
    --accent: #0b74d1;
    
    /* Component Tokens */
    --radius: 10px;
    --shadow: 0 6px 20px rgba(16,24,40,0.06);
}
```

## How to Customize Colors

1. Open `assets/css/theme-hci.css`
2. Locate the `:root` section at the top
3. Change the hex color values to match your branding
4. Save and refresh the page

### Example: Changing Primary Color to Green
```css
--primary: #059669;
--primary-hover: #047857;
--accent: #10b981;
```

## HCI Principles Applied

### 1. Visibility of System Status
- Loading spinners during data operations
- Toast notifications for success/error feedback
- Progress indicators on forms

### 2. Match Between System and Real World
- Familiar labels (Add User, Edit, Delete, Search)
- Icons paired with text labels
- Natural language in confirmations

### 3. Consistency and Standards
- Unified color palette across all pages
- Consistent button shapes and sizes
- Same spacing and typography everywhere

### 4. Recognition Over Recall
- Icons + labels for all actions
- Visible search box (toggle to show/hide)
- Clear page titles and breadcrumbs

### 5. Error Prevention and Recovery
- Confirmation dialogs for destructive actions
- Form validation with helpful messages
- Undo toast for delete operations

### 6. Aesthetic and Minimalist Design
- Clean topbar navigation
- White cards with subtle shadows
- Collapsible search to reduce clutter

### 7. Accessibility (WCAG AA)
- Color contrast meets 4.5:1 ratio
- Keyboard navigation support
- ARIA labels on interactive elements
- Skip to main content link

### 8. Flexibility and Efficiency
- Keyboard shortcuts (/ for search, Escape to close)
- Responsive design for all screen sizes
- Quick action buttons

## File Structure

```
assets/
├── css/
│   ├── style.css          # Main styles
│   └── theme-hci.css      # HCI enhancements & color tokens
├── js/
│   ├── main.js            # Core functionality
│   └── ui-enhancements.js # HCI behaviors
└── images/
    └── logo.png           # System logo

includes/
├── header.php             # Topbar navigation
└── footer.php             # Footer with scripts

pages/
├── users.php              # User management (enhanced)
├── hardware.php           # Hardware management (enhanced)
└── history.php            # Activity history
```

## Navigation Layout

The system uses a **topbar navigation** instead of sidebar:

- **Desktop**: Full horizontal navbar with all menu items visible
- **Tablet/Mobile**: Collapsible hamburger menu

## Search Functionality

The search/filter feature is hidden by default for a cleaner interface:

1. Click the **Search** button to show the search panel
2. Type to filter results in real-time
3. Press **Escape** or click **Close** to hide
4. Use **/** keyboard shortcut to quickly open search

## Components

### Buttons
- `.btn-primary-cta` - Primary call-to-action with gradient
- `.btn-action` - Compact action buttons for tables
- Standard Bootstrap buttons with enhanced styling

### Cards
- `.table-card` - Card container for tables
- `.card-header-primary` - Primary colored header

### Badges
- `.badge-role-admin` - Admin role badge (red dot)
- `.badge-role-staff` - Staff role badge (gray dot)

### Tables
- `.table-hci` - Enhanced table with better spacing
- Hover effects and row selection highlight

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `/` | Focus search input |
| `Escape` | Close search panel |
| `Alt + A` | Open Add modal |
| `Enter` on table row | Edit item |
| `Delete` on table row | Delete item |

## Responsive Breakpoints

- **Desktop**: ≥992px - Full topbar
- **Tablet**: 768-991px - Collapsible menu
- **Mobile**: <768px - Compact mobile menu

## Browser Support

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Accessibility Checklist

- [x] Skip to main content link
- [x] Keyboard navigation for all interactive elements
- [x] ARIA labels on icon-only buttons
- [x] Focus visible styles
- [x] Color contrast ≥4.5:1
- [x] Reduced motion support
- [x] High contrast mode support

## Performance Notes

- CSS variables enable instant theme switching
- Minimal JavaScript with vanilla JS (no jQuery dependency)
- Bootstrap 5 for responsive utilities
- Inter font for optimal readability
