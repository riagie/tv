<?php
require_once __DIR__ . '/loader.php';

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

$path = dirname(__FILE__);

$channels = [];
if (file_exists($path . '/data.php')) {
    $channels = require $path . '/data.php';
}

if (!is_array($channels)) {
    $channels = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <meta name="description" content="<?php echo APP_DESCRIPTION; ?>">

    <!-- Error filter untuk extension, favicon, dan third-party errors -->
    <script>
        (function() {
            // Keywords untuk filter
            const filterKeywords = [
                'xraywrapper', 'ns_binding_aborted', 'not allowed to define cross-origin',
                'male-technologist.png', 'favicon',
                '_pk_testcookie_domain', '_pk_ref', '_pk_id', '_pk_ses',
                'tam.js', 'thetracker-detik',
                'doubleclick.net', 'securepubads', 'ima_ppub_config',
                'cookie.*has been rejected', 'cross-site context', 'same site',
                'detik.com', 'livestreaming-',
                'gtm.js', 'detikvideo.core.js', 'detikbigdata', 'detikliveusercounter',
                'impression:v7', 'adsloader error', 'no ads vast',
                'can\'t write cookie', 'iframe loaded successfully',
                'securityerror: failed to read', 'cookie\' property from \'document\'',
                'allow-same-origin', 'escape.*sandboxing',
                'cannot read properties of undefined', 'typeerror: $ is not a function',
                'uncaught typeerror', 'uncaught securityerror'
            ];

            // Source files untuk filter (iframe dari detik.com)
            const filterSources = [
                '20.detik.com', 'detik.com',
                'gtm.js', 'tam.js', 'detikvideo', 'detikbigdata',
                'detikliveusercounter', 'videojs.', 'monolib',
                'livestreaming-', 'impression:v7'
            ];

            function shouldFilter(message) {
                const msgStr = (message || '').toLowerCase();
                return filterKeywords.some(keyword => msgStr.includes(keyword.toLowerCase()));
            }

            function shouldFilterSource(source) {
                const srcStr = (source || '').toLowerCase();
                return filterSources.some(keyword => srcStr.includes(keyword.toLowerCase()));
            }

            // Override window.onerror untuk filter global errors
            window.onerror = function(message, source, lineno, colno, error) {
                const msgStr = (message || '').toLowerCase();
                const srcStr = (source || '').toLowerCase();
                if (shouldFilter(msgStr) || shouldFilterSource(srcStr)) {
                    return true; // Prevent error from showing in console
                }
                return false; // Let default error handler work
            };

            // Filter unhandled promise rejections
            window.addEventListener('unhandledrejection', function(event) {
                const reason = event.reason?.toString() || '';
                const reasonStr = reason.toLowerCase();
                if (shouldFilter(reasonStr)) {
                    event.preventDefault();
                }
            });
        })();
    </script>

    <!-- Favicon - gunakan APP_ICON sebagai data URI atau fallback emoji -->
    <?php
    $faviconData = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📺</text></svg>";

    // Coba dapatkan icon dari APP_ICON dan convert ke data URI
    if (!empty(APP_ICON) && filter_var(APP_ICON, FILTER_VALIDATE_URL)) {
        $iconPath = __DIR__ . '/favicon.png';
        // Jika icon lokal belum ada, coba download sekali saja
        if (!file_exists($iconPath)) {
            try {
                $iconData = @file_get_contents(APP_ICON, false, stream_context_create([
                    'http' => ['timeout' => 5],
                    'https' => ['timeout' => 5]
                ]));
                if ($iconData !== false) {
                    file_put_contents($iconPath, $iconData);
                }
            } catch (Exception $e) {
                // Silent fail, gunakan fallback
            }
        }

        // Gunakan icon lokal jika ada
        if (file_exists($iconPath)) {
            $faviconData = './favicon.png?v=' . filemtime($iconPath);
        }
    }
    ?>
    <link rel="icon" href="<?php echo $faviconData; ?>">
    <link rel="stylesheet" href="./styles.css?v=<?php echo filemtime(__DIR__ . '/styles.css'); ?>">
    <link rel="preload" href="./layout.css?v=<?php echo filemtime(__DIR__ . '/layout.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="./layout.css?v=<?php echo filemtime(__DIR__ . '/layout.css'); ?>"></noscript>
    <link rel="preconnect" href="https://www.youtube.com">
    <link rel="preconnect" href="https://i.ytimg.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://static.rctiplus.id">
    <link rel="dns-prefetch" href="https://cdnjktbpid01.transvision.co.id">
    <link rel="dns-prefetch" href="https://cdn.detik.net.id">
</head>
<body>
    <div class="app-container">
        <header class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1 class="app-title"><?php echo APP_TITLE; ?></h1>
                </div>
                <nav class="header-tabs">
                    <button class="tab-btn active" onclick="switchTab('local')" data-tab="local" tabindex="0">
                        <span class="tab-label">Favorites</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('indonesia')" data-tab="indonesia" tabindex="0">
                        <span class="tab-label">Indonesia</span>
                    </button>
                    <button class="tab-btn" onclick="switchTab('global')" data-tab="global" tabindex="0">
                        <span class="tab-label">Worldwide</span>
                    </button>
                </nav>
            </div>
        </header>

        <main class="main-content">
            <div class="search-bar">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Cari channel..." autofocus>
                <button class="search-clear" id="searchClear" style="display: none;">✕</button>
            </div>

            <div class="channel-list-wrapper">
                <div class="channel-count"><span id="channelCount"><?php echo count($channels); ?></span> channel</div>

                <div class="channel-list" id="channelList">
                    <?php if (!empty($channels)): ?>
                        <?php foreach ($channels as $index => $channel): ?>
                            <?php
                                $url = htmlspecialchars($channel['url'] ?? '', ENT_QUOTES, 'UTF-8');
                                $img = htmlspecialchars($channel['img'] ?? '', ENT_QUOTES, 'UTF-8');
                                $name = htmlspecialchars($channel['name'] ?? $channel['alt'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
                                $category = htmlspecialchars($channel['category'] ?? 'TV', ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="channel-item" data-url="<?php echo $url; ?>" data-name="<?php echo $name; ?>" tabindex="0">
                                <div class="channel-thumb">
                                    <img
                                        src="<?php echo $img; ?>"
                                        alt="<?php echo $name; ?>"
                                        loading="lazy"
                                        decoding="async"
                                        fetchpriority="low"
                                        onerror="this.outerHTML='<svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23888\' stroke-width=\'2\' style=\'width:32px;height:32px;\'><rect x=\'2\' y=\'7\' width=\'20\' height=\'15\' rx=\'2\' ry=\'2\'></rect><polyline points=\'17 2 12 7 7 2\'></polyline></svg>'"
                                    >
                                </div>
                                <div class="channel-details">
                                    <div class="channel-name"><?php echo $name; ?></div>
                                    <div class="channel-category"><?php echo $category; ?></div>
                                </div>
                                <div class="channel-play">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">⚠️</div>
                            <div class="empty-text">Tidak ada channel tersedia</div>
                            <div class="empty-hint">Buka admin panel untuk menambah channel</div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="no-results" id="noResults" style="display: none;">
                    <div class="no-results-icon">🔍</div>
                    <div class="no-results-text">Channel tidak ditemukan</div>
                </div>
            </div>

            <div class="iptv-section" id="iptvSection" style="display: none;">
                <div class="iptv-loading" id="iptvLoading">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">Memuat channels...</div>
                </div>
                <div class="iptv-channels-list" id="iptvChannelsList"></div>
            </div>
        </main>
    </div>

    <div class="player-modal" id="playerModal">
        <div class="player-modal-content">
            <div class="player-modal-header">
                <div class="player-channel-info">
                    <div class="player-channel-name" id="playerChannelName">Channel</div>
                    <div class="player-live-badge">LIVE</div>
                </div>
                <button class="player-close" id="playerClose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="player-modal-body">
                <div class="player-container" id="playerContainer">
                    <div class="player-loading">
                        <div class="loading-spinner"></div>
                        <div class="loading-text">Memuat stream...</div>
                    </div>
                </div>
            </div>
            <div class="player-modal-footer">
                <button class="player-btn" id="playerPrev">
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="19 20 9 12 19 4 19 20"></polygon><line x1="5" y1="19" x2="5" y2="5" stroke="currentColor" stroke-width="2"></line></svg>
                    <span>Sebelumnya</span>
                </button>
                <button class="player-btn" id="playerNext">
                    <span>Selanjutnya</span>
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 4 15 12 5 20 5 4"></polygon><line x1="19" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="2"></line></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.APP_CONFIG = {
            secretKeyPrefix: '<?php echo addslashes(SECRET_KEY_PREFIX); ?>',
            appIcon: '<?php echo addslashes(APP_ICON); ?>'
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script src="./script.js?v=<?php echo filemtime(__DIR__ . '/script.js'); ?>"></script>
</body>
</html>
