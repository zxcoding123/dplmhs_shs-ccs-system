# Favicon Setup Guide

## Current Favicon Files

Your CSMS system includes a professional favicon featuring:
- **Graduation Cap** (gold #c2a74d) - Representing academic excellence
- **Computer Monitor** (white with gold trim) - Representing computer science
- **Color Scheme**: Dark blue (#061E4E) background with gold (#c2a74d) accent

### Files Created:
- `favicon.svg` - Scalable vector favicon (main file)
- `site.webmanifest` - Web app manifest for PWA support

### Files Referenced (Need to be generated):
- `favicon-32x32.png` - 32x32 pixel PNG
- `favicon-16x16.png` - 16x16 pixel PNG  
- `apple-touch-icon.png` - 180x180 pixel PNG for Apple devices

## How to Generate PNG Versions

### Option 1: Using Online Tool (Recommended)
1. Visit: https://convertio.co/svg-png/ or https://cloudconvert.com/svg-to-png
2. Upload `favicon.svg`
3. Download PNG versions at different sizes:
   - 16x16 pixels → Save as `favicon-16x16.png`
   - 32x32 pixels → Save as `favicon-32x32.png`
   - 180x180 pixels → Save as `apple-touch-icon.png`
4. Place files in the root directory

### Option 2: Using ImageMagick (Command Line)
```bash
# Install ImageMagick if not already installed

# Generate 16x16 favicon
convert -background none favicon.svg -define icon:auto-resize=16x16 favicon-16x16.png

# Generate 32x32 favicon
convert -background none favicon.svg -define icon:auto-resize=32x32 favicon-32x32.png

# Generate Apple touch icon (180x180)
convert -background none -resize 180x180 favicon.svg apple-touch-icon.png
```

### Option 3: Using Python (PIL/Pillow)
```python
from PIL import Image
import io
import requests

# Load SVG and convert to PNG
# You may need to use svglib or cairosvg libraries for SVG support
# Alternatively, use an online converter
```

## Installation Status

✅ **Favicon SVG** - Ready to use
✅ **Web Manifest** - Configured for PWA support
⏳ **PNG Versions** - Need to be generated using the methods above

## Browser Support

Once all files are in place, your favicon will work on:
- ✅ Modern browsers (Chrome, Firefox, Safari, Edge)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)
- ✅ Desktop apps (Slack, Teams, etc.)
- ✅ PWA functionality with web manifest

## Quick HTML Integration

The favicon is already integrated in:
- `index.php` - Main landing page
- `admin/admin_management.php` - Admin dashboard

To add to other pages, include this in the `<head>` section:

```html
<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
```

## Customization

To modify the favicon design, edit `favicon.svg` with:
- Inkscape (free, open-source)
- Adobe Illustrator
- Online SVG editors (https://www.svgeditor.io/)

Key colors in use:
- Primary Blue: `#061E4E`
- Gold Accent: `#c2a74d`
- Highlight Red: `#e92b2d`
- White: `#ffffff`

## Testing

After setting up PNG versions:
1. Clear browser cache (Ctrl+Shift+Del or Cmd+Shift+Del)
2. Refresh the page
3. Check browser tab to see favicon displayed
4. Test on mobile devices to verify apple-touch-icon works

## Additional Notes

- The SVG favicon works on most modern browsers and provides the best quality
- PNG versions provide fallback support for older browsers
- The web manifest enables PWA features and proper app installation
- Favicon is cached by browsers, so changes may take time to appear

For more information, visit: https://www.favicon.cc/ or https://evilmartians.com/chronicles/how-to-favicon-in-2021
