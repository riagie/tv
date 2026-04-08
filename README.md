# TV Streaming Platform

Indonesian TV streaming platform with SQLite database and modern UI.

## Features

- Modern dark theme design
- Real-time channel search
- Responsive (desktop, tablet, mobile)
- HLS (.m3u8) streaming support
- YouTube embed support
- SQLite database
- Admin panel for channel management

## File Structure

```
tv/
├── index.php       # Main page
├── admin/          # Admin panel
├── data.php        # Fetch data from database
├── api.php         # IPTV API
├── script.js       # JavaScript player
├── styles.css      # Main styles
├── layout.css      # Layout fixes
├── tv.db           # Database
└── .htaccess       # Apache config
```

## Setup

1. Open `index.php` in browser
2. Open `admin/` to manage channels

## Database

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

## Channel Categories

- `entertainment` - Entertainment
- `news` - News
- `business` - Business
- `sports` - Sports
- `general` - General

## Add Channel via SQL

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

## Dependencies

- **HLS.js** - For .m3u8 streaming (CDN)
- **SQLite3** - For database (PHP extension)

## Video Support

- HLS streams (.m3u8)
- YouTube live streams
- Direct video files (.mp4, .webm)
- iframe embeds

## Customization

Edit CSS variables in `styles.css`:

```css
:root {
    --bg: #000000;
    --text: #e5e5e5;
}
```

## Security

- Database not in git (.gitignore)
- Input sanitization with htmlspecialchars()
- Prepared statements for SQL
- XSS protection

## Notes

- Requires PHP sqlite3 extension
- Use password protection for admin/ in production
