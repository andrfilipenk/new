# Quick Start Guide - Advanced Backend UI Kits

## 🚀 Get Started in 5 Minutes

### Option 1: View Kit Delta Dashboard

1. **Open the dashboard** directly in your browser:
   ```
   File: c:\xampp\htdocs\new\public\html\kit-delta\dashboard.html
   ```

2. **Or serve via local server**:
   ```bash
   cd c:\xampp\htdocs\new\public\html
   # Python
   python -m http.server 8000
   
   # PHP
   php -S localhost:8000
   
   # Node.js (with http-server)
   npx http-server -p 8000
   ```

3. **Visit**: `http://localhost:8000/kit-delta/dashboard.html`

### Option 2: Integrate into Your Project

1. **Copy kit folder**:
   ```bash
   cp -r kit-delta /your/project/public/
   ```

2. **Add to your HTML**:
   ```html
   <!DOCTYPE html>
   <html lang="en">
   <head>
       <!-- Bootstrap & Icons -->
       <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
       <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
       
       <!-- Kit Delta -->
       <link rel="stylesheet" href="kit-delta/assets/css/kit-delta.css">
       <link rel="stylesheet" href="kit-delta/assets/css/animations.css">
   </head>
   <body class="neural-interface" data-theme="dark">
       
       <!-- Your dashboard content here -->
       
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
       <script src="kit-delta/assets/js/kit-delta.js"></script>
       <script src="kit-delta/assets/js/interactions.js"></script>
   </body>
   </html>
   ```

## ⌨️ Essential Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+K` | Open Command Palette |
| `Ctrl+B` | Toggle Sidebar |
| `Esc` | Close Modal/Panel |
| `?` | Show Help |

## 🎨 Quick Customization

### Change Colors

Edit `kit-delta/assets/css/kit-delta.css`:

```css
:root {
    --accent-primary: #00f5ff;     /* Change to your brand color */
    --accent-secondary: #b026ff;   /* Secondary accent */
    --bg-primary: #0a0e27;         /* Background */
}
```

### Change Fonts

```css
:root {
    --font-mono: 'Your Mono Font', monospace;
    --font-sans: 'Your Sans Font', sans-serif;
}
```

## 🔌 Backend Integration

### WebSocket Setup

Edit `kit-delta/assets/js/kit-delta.js`:

```javascript
const CONFIG = {
    websocket: {
        url: 'ws://localhost:8080',  // Your WebSocket server
        reconnectInterval: 5000,
        maxReconnectAttempts: 10
    }
};
```

### Expected Message Format

```json
{
    "type": "metric|task|notification|event",
    "data": {
        "id": "unique-id",
        "value": "content",
        "message": "Description"
    }
}
```

## 📱 Test Responsive Design

1. **Desktop**: Open normally
2. **Mobile**: Open DevTools (F12) → Toggle Device Toolbar
3. **Test breakpoints**: 576px, 768px, 992px, 1200px

## 🔍 Troubleshooting

**Styles not loading?**
- Check file paths in HTML
- Verify Bootstrap CDN is accessible
- Open browser console for errors

**Command palette not opening?**
- Press `Ctrl+K` or `Cmd+K`
- Check JavaScript console for errors
- Ensure scripts are loaded

**WebSocket errors?**
- System automatically falls back to polling
- Check WebSocket server is running
- Verify URL in config

## 📚 Next Steps

1. ✅ Explore the dashboard components
2. ✅ Read the full [README](./README.md)
3. ✅ Check [Kit Delta Documentation](./kit-delta/README.md)
4. ✅ Review [Implementation Summary](./IMPLEMENTATION_SUMMARY.md)
5. 🚧 Customize for your project
6. 🚧 Integrate with your backend

## 🎯 What You Can Do Right Now

### With Kit Delta (✅ Complete)
- View fully functional dashboard
- Test command palette (Ctrl+K)
- Try keyboard shortcuts
- Interact with task interface
- See live countdown timers
- Test responsive design
- Customize colors and fonts

### Coming Soon (🚧 In Progress)
- Kit Epsilon - Quantum Workspace
- Kit Zeta - Holographic Command
- Component showcase pages
- More integration examples

## 💡 Tips

1. **Use Command Palette**: Fastest way to navigate (Ctrl+K)
2. **Keyboard First**: Most actions have keyboard shortcuts
3. **Customize Early**: Set your brand colors from the start
4. **Test Mobile**: Always check responsive behavior
5. **Read Docs**: Full documentation in README files

## 🆘 Get Help

- Check the [README](./README.md) for detailed docs
- Review [Implementation Summary](./IMPLEMENTATION_SUMMARY.md)
- Read kit-specific docs in each kit folder
- Check browser console for errors

---

**Quick Links**:
- [Main README](./README.md)
- [Kit Delta README](./kit-delta/README.md)
- [Implementation Summary](./IMPLEMENTATION_SUMMARY.md)

**Ready to start?** Open `kit-delta/dashboard.html` in your browser! 🚀
