# TV Streaming Platform

Indonesian TV streaming platform with SQLite database, modern UI/UX, and complete features.

---

## Features

- **Modern Design** - Dark theme elegant
- **Real-time Search** - Fast and accurate channel search
- **Responsive** - Optimized for desktop, tablet, and mobile
- **HLS Support** - Support .m3u8 streaming
- **YouTube Embed** - Support YouTube live streams
- **SQLite** - Easy channel database management
- **Fast** - Optimized for speed
- **Smooth** - Smooth transitions
- **Admin** - Interface to add/edit/delete channels

---

## File Structure

```
tv/
├── index.php           # Main streaming page
├── admin/              # Admin panel
│   └── index.php       # Admin page
├── data.php            # Fetch data from database
├── api.php             # API for IPTV channels
├── script.js           # JavaScript player & search
├── styles.css          # Main styling
├── layout.css          # Layout fixes
├── tv.db               # Database
├── .htaccess           # Apache configuration
└── .gitignore          # Git ignore rules
```

---

## Setup

### 1. Open Website

Open `index.php` in your browser or web server.

### 2. Admin Panel

Open `admin/` to add/edit/delete channels:
```
http://localhost/tv/admin/
```

---

## Database Structure

File: `tv.db`

```sql
CREATE TABLE channels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    url TEXT NOT NULL,
    image TEXT NOT NULL,
    category TEXT DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

---

## Channel Categories

- `entertainment` - General entertainment
- `news` - News
- `business` - Business and economics
- `sports` - Sports
- `general` - General

---

## Channel Management

### Add New Channel

1. Open `admin/`
2. Fill "Add New Channel" form
3. Click "Add Channel"

### Or via SQL:

```php
<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/tv.db');
$stmt = $pdo->prepare("
    INSERT INTO channels (name, url, image, category)
    VALUES (:name, :url, :image, :category)
");
$stmt->execute([
    ':name' => 'Channel Name',
    ':url' => 'https://example.com/stream',
    ':image' => 'https://example.com/logo.png',
    ':category' => 'entertainment'
]);
?>
```

---

## Customization

### Change Colors

Edit `styles.css`, change CSS variables in `:root`:

```css
:root {
    --bg: #000000;
    --text: #e5e5e5;
    /* ... */
}
```

---

## Dependencies

- **HLS.js** - For .m3u8 streaming (CDN)
- **SQLite3** - For database (PHP extension)

No additional PHP dependencies required.

---

## Breakpoints

- **Desktop**: > 768px
- **Tablet**: 481px - 768px
- **Mobile**: <= 480px

---

## Video Support

- HLS streams (.m3u8)
- YouTube live streams
- Direct video files (.mp4, .webm, etc.)
- iframe embeds

---

## Security

- Database not committed to git (.gitignore)
- Input sanitization with htmlspecialchars()
- Prepared statements for SQL queries
- XSS protection

---

## Notes

- Make sure PHP extension `sqlite3` is installed
- For production, use password protection for `admin/`

---

## License

Free to use for personal and commercial projects.

---

## Developer

**OGIE NURDIANA**
- Website: https://ogienurdiana.com

---

## Credits

- HLS.js - https://github.com/video-dev/hls.js
- Icons from Unicode/SVG
- Channel logos from respective broadcasters

---

## Support

For issues or questions, please contact the developer or open an issue in the repository.
