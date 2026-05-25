class Player {
    constructor() {
        this.hls = null;
        this.index = -1;
        this.channels = [];
        this.local = [];
        this.iptv = [];
        this.tab = 'local';
        this.interacted = false;
        this.init();
    }

    init() {
        this.cacheElements();
        this.loadChannels();
        this.bindEvents();
        this.initSearch();
    }

    cacheElements() {
        this.modal = document.getElementById('playerModal');
        this.container = document.getElementById('playerContainer');
        this.channelName = document.getElementById('playerChannelName');
        this.closeBtn = document.getElementById('playerClose');
        this.prevBtn = document.getElementById('playerPrev');
        this.nextBtn = document.getElementById('playerNext');
        this.search = document.getElementById('searchInput');
        this.clearBtn = document.getElementById('searchClear');
        this.list = document.getElementById('channelList');
        this.noResults = document.getElementById('noResults');
        this.count = document.getElementById('channelCount');
    }

    loadChannels() {
        this.local = Array.from(document.querySelectorAll('.channel-item'));
        this.channels = this.local;
        if (this.count) this.count.textContent = this.channels.length;
    }

    bindEvents() {
        if (!this.channels.length) return;

        this.channels.forEach((channel, index) => {
            channel.addEventListener('click', (e) => {
                e.preventDefault();
                this.openChannel(index);
            });
            channel.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.openChannel(index);
                }
            });
        });

        this.closeBtn?.addEventListener('click', () => this.closeModal());
        this.prevBtn?.addEventListener('click', () => this.playPrevious());
        this.nextBtn?.addEventListener('click', () => this.playNext());

        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) this.closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (this.modal?.classList.contains('active')) {
                this.handleModalNavigation(e);
            } else {
                this.handleListNavigation(e);
            }
        });

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    switchTab(btn.dataset.tab);
                }
            });
        });
    }

    handleListNavigation(e) {
        const visibleChannels = this.channels.filter(ch => ch.style.display !== 'none');
        const currentIndex = visibleChannels.indexOf(document.activeElement);
        const activeElement = document.activeElement;

        switch(e.key) {
            case 'ArrowRight':
                e.preventDefault();
                if (activeElement.classList.contains('tab-btn')) {
                    const tabs = Array.from(document.querySelectorAll('.tab-btn'));
                    const currentTab = tabs.indexOf(activeElement);
                    if (currentTab < tabs.length - 1) tabs[currentTab + 1].focus();
                } else if (activeElement.classList.contains('channel-item')) {
                    this.focusChannel(currentIndex + 1, visibleChannels);
                }
                break;
            case 'ArrowLeft':
                e.preventDefault();
                if (activeElement.classList.contains('tab-btn')) {
                    const tabs = Array.from(document.querySelectorAll('.tab-btn'));
                    const currentTab = tabs.indexOf(activeElement);
                    if (currentTab > 0) tabs[currentTab - 1].focus();
                } else if (activeElement.classList.contains('channel-item')) {
                    this.focusChannel(currentIndex - 1, visibleChannels);
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (activeElement === this.search || activeElement.classList.contains('tab-btn')) {
                    if (visibleChannels.length > 0) visibleChannels[0].focus();
                } else if (activeElement.classList.contains('channel-item')) {
                    this.focusChannel(currentIndex + this.getGridColumns(), visibleChannels);
                }
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (activeElement.classList.contains('channel-item')) {
                    if (this.search && currentIndex === 0) {
                        this.search.focus();
                    } else {
                        this.focusChannel(currentIndex - this.getGridColumns(), visibleChannels);
                    }
                } else if (activeElement === this.search) {
                    const activeTab = document.querySelector('.tab-btn.active');
                    if (activeTab) activeTab.focus();
                }
                break;
            case 'Enter':
                if (activeElement.classList.contains('channel-item')) {
                    e.preventDefault();
                    this.openChannel(this.channels.indexOf(activeElement));
                } else if (activeElement.classList.contains('tab-btn')) {
                    e.preventDefault();
                    switchTab(activeElement.dataset.tab);
                }
                break;
            case 'Escape':
                e.preventDefault();
                this.clearSearch();
                break;
        }
    }

    handleModalNavigation(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            this.closeModal();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            this.playPrevious();
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            this.playNext();
        }
    }

    getGridColumns() {
        if (this.list) {
            const containerWidth = this.list.offsetWidth;
            const channelWidth = this.channels[0]?.offsetWidth || 110;
            return Math.floor(containerWidth / channelWidth) || 6;
        }
        return 6;
    }

    focusChannel(index, channels) {
        if (index >= 0 && index < channels.length) {
            channels[index].focus();
            channels[index].scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
        }
    }

    initSearch() {
        if (!this.search) return;
        this.search.addEventListener('input', (e) => this.filterChannels(e.target.value.toLowerCase().trim()));
        if (this.clearBtn) this.clearBtn.addEventListener('click', () => this.clearSearch());
    }

    clearSearch() {
        if (!this.search) return;
        this.search.value = '';
        this.filterChannels('');
        this.search.focus();
    }

    filterChannels(query) {
        let visibleCount = 0;
        this.channels.forEach((channel) => {
            const channelName = channel.dataset.name?.toLowerCase() || '';
            const isMatch = channelName.includes(query);
            channel.style.setProperty('display', isMatch ? 'flex' : 'none', 'important');
            if (isMatch) visibleCount++;
        });

        if (this.noResults) {
            this.noResults.style.setProperty('display', visibleCount === 0 ? 'flex' : 'none', 'important');
        }
        if (this.clearBtn) {
            this.clearBtn.style.setProperty('display', query ? 'flex' : 'none', 'important');
        }
    }

    openChannel(index) {
        const channel = this.channels[index];
        if (!channel || !channel.dataset.url) return;

        this.index = index;
        const url = channel.dataset.url;
        const name = channel.dataset.name;

        if (this.channelName) this.channelName.textContent = name || 'Unknown Channel';

        this.showLoading();
        this.modal?.classList.add('active');
        if (document.body) document.body.style.overflow = 'hidden';

        // Mark user interaction untuk autoplay
        this.interacted = true;

        // Enter fullscreen
        this.enterFullscreen();

        // Play stream dengan delay kecil agar UI terupdate dulu
        requestAnimationFrame(() => {
            setTimeout(() => this.playStream(url), 100);
        });
    }

    closeModal() {
        this.modal?.classList.remove('active');
        if (document.body) document.body.style.overflow = '';

        // Cleanup player resources
        this.cleanup();

        // Exit fullscreen
        this.exitFullscreen();

        // Return focus to the channel item
        if (this.index >= 0 && this.channels[this.index]) {
            this.channels[this.index].focus();
        }

        // Reset index
        this.index = -1;
    }

    playPrevious() {
        const visibleChannels = this.channels.filter(ch => ch.style.display !== 'none');
        if (visibleChannels.length === 0) return;

        let newIndex = this.index - 1;
        while (newIndex >= 0 && this.channels[newIndex]?.style.display === 'none') newIndex--;
        if (newIndex < 0) newIndex = this.channels.length - 1;
        this.openChannel(newIndex);
    }

    playNext() {
        const visibleChannels = this.channels.filter(ch => ch.style.display !== 'none');
        if (visibleChannels.length === 0) return;

        let newIndex = this.index + 1;
        while (newIndex < this.channels.length && this.channels[newIndex]?.style.display === 'none') newIndex++;
        if (newIndex >= this.channels.length) newIndex = 0;
        this.openChannel(newIndex);
    }

    playStream(url) {
        this.cleanup();
        this.showLoading();

        try {
            // Validasi URL
            if (!url || typeof url !== 'string') {
                throw new Error('Invalid URL');
            }

            // Deteksi tipe stream
            const isHls = url.includes('.m3u8') || url.endsWith('m3u8') || url.includes('m3u8?');
            const isYouTube = url.includes('youtube.com') || url.includes('youtu.be');

            if (isHls) {
                this.playHls(url);
            } else if (isYouTube) {
                this.playYouTube(url);
            } else {
                this.playIframe(url);
            }
        } catch (error) {
            this.showError('Gagal memuat stream. Coba channel lain.');
        }
    }

    cleanup() {
        // Cleanup HLS instance
        if (this.hls) {
            try {
                this.hls.stopLoad();
                this.hls.detachMedia();
                this.hls.destroy();
            } catch (e) {
            }
            this.hls = null;
        }

        // Cleanup container content
        if (this.container) {
            // Stop video elements first
            const videos = this.container.querySelectorAll('video');
            videos.forEach(video => {
                try {
                    video.pause();
                    video.src = '';
                    video.load();
                } catch (e) {
                }
            });

            // Stop iframes
            const iframes = this.container.querySelectorAll('iframe');
            iframes.forEach(iframe => {
                try {
                    iframe.src = '';
                } catch (e) {
                }
            });

            this.container.innerHTML = '';
        }
    }

    enterFullscreen() {
        const element = this.modal;
        if (!element) return;
        if (element.requestFullscreen) {
            element.requestFullscreen().catch(() => {});
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }

    exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }

    showLoading() {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="player-loading">
                <div class="loading-spinner"></div>
                <div class="loading-text" id="loadingText">Memuat stream...</div>
            </div>
        `;
    }

    updateLoadingText(text) {
        const loadingText = document.getElementById('loadingText');
        if (loadingText) loadingText.textContent = text;
    }

    playHls(url) {
        // Cek apakah Hls.js sudah terload
        const waitForHls = (callback, maxAttempts = 50, attempts = 0) => {
            if (typeof Hls !== 'undefined') {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(() => waitForHls(callback, maxAttempts, attempts + 1), 100);
            } else {
                // Hls.js tidak tersedia, coba native HLS support
                const video = this.createVideo();
                if (video) {
                    video.src = url;
                    this.attemptAutoplay(video);
                }
            }
        };

        waitForHls(() => {
            if (typeof Hls !== 'undefined' && Hls.isSupported()) {
                this.cleanup();
                this.hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    maxBufferLength: 30,
                    maxMaxBufferLength: 60,
                    backBufferLength: 10,
                    debug: false
                });

                this.hls.loadSource(url);
                const video = this.createVideo();
                if (!video) return;

                this.hls.attachMedia(video);
                this.updateLoadingText('Memuat manifest...');

                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    this.updateLoadingText('Memulai playback...');
                    this.attemptAutoplay(video);
                });

                this.hls.on(Hls.Events.ERROR, (event, data) => {
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                this.showError('Network error. Coba lagi.');
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                this.hls.recoverMediaError();
                                break;
                            default:
                                this.showError('Stream error. Coba channel lain.');
                                break;
                        }
                    }
                });
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Native HLS support (Safari)
                const video = this.createVideo();
                if (!video) return;
                video.src = url;
                this.attemptAutoplay(video);
            } else {
                this.showError('Browser tidak mendukung HLS. Gunakan browser modern.');
            }
        });
    }

    attemptAutoplay(video) {
        // Set default muted untuk mengatasi autoplay policy
        video.muted = true;

        const playPromise = video.play();

        if (playPromise !== undefined) {
            playPromise
                .then(() => {
                    this.hideLoading();
                    // Tampilkan tombol unmute jika video berhasil diputar dengan muted
                    this.showUnmuteButton(video);
                })
                .catch(error => {
                    // Autoplay benar-benar diblokir, perlu interaksi user
                    const loadingDiv = this.container?.querySelector('.player-loading');
                    if (loadingDiv) {
                        loadingDiv.classList.add('click-hint');
                        this.updateLoadingText('Klik untuk memutar');
                        loadingDiv.style.cursor = 'pointer';
                        loadingDiv.addEventListener('click', () => {
                            video.muted = false;
                            video.play()
                                .then(() => {
                                    this.hideLoading();
                                    loadingDiv.classList.remove('click-hint');
                                })
                                .catch(e => {
                                    this.showError('Gagal memutar video. Coba channel lain.');
                                });
                        }, { once: true });
                    }
                });
        }
    }

    showUnmuteButton(video) {
        if (!this.container || this.container.querySelector('.unmute-button')) return;

        // Hapus loading spinner dulu jika ada
        const loadingDiv = this.container?.querySelector('.player-loading');
        if (loadingDiv && loadingDiv.parentNode === this.container) {
            loadingDiv.remove();
        }

        const unmuteBtn = document.createElement('button');
        unmuteBtn.className = 'unmute-button';
        unmuteBtn.innerHTML = '🔊 Klik untuk suara';
        unmuteBtn.type = 'button';
        unmuteBtn.setAttribute('aria-label', 'Unmute video');
        unmuteBtn.style.cssText = `
            position: absolute;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: rgba(229, 9, 20, 0.95);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            z-index: 10;
            transition: opacity 0.3s ease;
        `;

        unmuteBtn.addEventListener('click', () => {
            video.muted = false;
            unmuteBtn.style.opacity = '0';
            setTimeout(() => unmuteBtn.remove(), 300);
        });

        // Auto-hide setelah 5 detik
        setTimeout(() => {
            if (unmuteBtn.parentNode) {
                unmuteBtn.style.opacity = '0';
                setTimeout(() => unmuteBtn.remove(), 300);
            }
        }, 5000);

        this.container.appendChild(unmuteBtn);
    }

    playYouTube(url) {
        this.updateLoadingText('Memuat YouTube...');

        const iframe = this.createIframe();
        if (!iframe) return;

        let embedUrl = url;
        if (url.includes('watch?v=')) {
            const videoId = url.split('watch?v=')[1].split('&')[0];
            embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&playsinline=1&rel=0&enablejsapi=1&widgetid=1&controls=1`;
        } else if (url.includes('youtu.be/')) {
            const videoId = url.split('youtu.be/')[1].split('?')[0];
            embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&playsinline=1&rel=0&enablejsapi=1&widgetid=1&controls=1`;
        }

        iframe.src = embedUrl;
        setTimeout(() => this.hideLoading(), 3000);
    }

    playIframe(url) {
        this.updateLoadingText('Memuat player...');

        const iframe = this.createIframe();
        if (!iframe) return;

        const separator = url.includes('?') ? '&' : '?';
        iframe.src = `${url}${separator}autoplay=1&playsinline=1&mute=0`;

        setTimeout(() => this.hideLoading(), 3000);

        iframe.addEventListener('load', () => {});
        window.addEventListener('error', (e) => {
            if (e.target === iframe || e.target === window) e.preventDefault();
        }, true);
    }

    createVideo() {
        if (!this.container) return null;

        this.container.innerHTML = '';
        const video = document.createElement('video');

        video.autoplay = true;
        video.muted = true; // Default muted untuk autoplay policy
        video.playsInline = true;
        video.controls = true;
        video.setAttribute('webkit-playsinline', 'webkit-playsinline');
        video.setAttribute('x-webkit-airplay', 'allow');
        video.style.cssText = 'width:100%;height:100%;background:#000';

        // Error handling yang lebih baik
        video.addEventListener('canplay', () => {
            this.hideLoading();
        });

        video.addEventListener('playing', () => {
            this.hideLoading();
        });

        video.addEventListener('error', (e) => {
            this.showError('Gagal memuat video. Coba channel lain.');
        });

        video.addEventListener('stalled', () => {
        });

        video.addEventListener('waiting', () => {
        });

        this.container.appendChild(video);
        return video;
    }

    createIframe() {
        if (!this.container) return null;

        this.container.innerHTML = '';
        const iframe = document.createElement('iframe');

        // Gunakan allowfullscreen
        iframe.allowFullscreen = true;
        iframe.setAttribute('webkitallowfullscreen', 'true');
        iframe.setAttribute('mozallowfullscreen', 'true');
        iframe.referrerPolicy = 'origin';

        // Tambahkan allow-same-origin agar detik.com analytics bisa bekerja
        // Detik.com adalah trusted source untuk streaming TV Indonesia
        iframe.sandbox = 'allow-scripts allow-presentation allow-forms allow-same-origin';
        iframe.style.cssText = 'width:100%;height:100%;border:none';

        iframe.addEventListener('load', () => {
            setTimeout(() => this.hideLoading(), 2000);
        });

        iframe.addEventListener('error', () => {
            this.showError('Gagal memuat player. Coba channel lain.');
        });

        this.container.appendChild(iframe);
        return iframe;
    }

    showError(message) {
        if (!this.container) return;
        this.container.innerHTML = `
            <div class="player-loading">
                <div style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <div class="loading-text">${message}</div>
            </div>
        `;
    }

    hideLoading() {
        const loadingDiv = this.container?.querySelector('.player-loading');
        if (loadingDiv && loadingDiv.parentNode === this.container) loadingDiv.remove();
    }

    async fetchIptvChannels(type) {
        const currentPath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const apiUrl = `${currentPath}api.php?type=${type}`;

        try {
            const response = await fetch(apiUrl);
            const text = await response.text();
            const data = this.decodeResponse(text);

            if (data.error) throw new Error(data.message);

            this.iptv = data.data.map(item => {
                const decoded = JSON.parse(atob(item.d));
                return decoded;
            });

            return this.iptv;
        } catch (error) {
            return [];
        }
    }

    decodeResponse(encodedText) {
        try {
            const decoded = atob(encodedText);
            const keyPrefix = window.APP_CONFIG?.secretKeyPrefix || 'secret_key_';
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const key = keyPrefix + year + month + day;

            let result = '';
            for (let i = 0; i < decoded.length; i++) {
                result += String.fromCharCode(
                    decoded.charCodeAt(i) ^ key.charCodeAt(i % key.length)
                );
            }

            return JSON.parse(result);
        } catch (error) {
            return { error: true, message: 'Failed to decode response' };
        }
    }

    renderLocalChannels() {
        if (!this.list) return;

        this.list.innerHTML = '';

        if (this.local.length === 0) {
            this.list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <div class="empty-text">Tidak ada channel tersedia</div>
                </div>
            `;
            if (this.count) this.count.textContent = '0';
            this.channels = [];
            return;
        }

        // Clone dan append channel items
        this.local.forEach((ch, index) => {
            const clone = ch.cloneNode(true);
            // Store original index for event handlers
            clone.dataset.originalIndex = index;
            this.list.appendChild(clone);
        });

        // Get channels dan attach event listeners
        const channels = Array.from(this.list.querySelectorAll('.channel-item'));
        channels.forEach((channel) => {
            const index = parseInt(channel.dataset.originalIndex || '0', 10);

            channel.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openChannel(index);
            });

            channel.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.openChannel(index);
                }
            });
        });

        this.channels = channels;
        if (this.count) this.count.textContent = channels.length;
    }

    renderIptvChannels(channels) {
        if (!this.list) return;

        this.list.innerHTML = '';

        if (channels.length === 0) {
            this.list.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <div class="empty-text">Tidak ada channel tersedia</div>
                </div>
            `;
            if (this.count) this.count.textContent = '0';
            this.channels = [];
            return;
        }

        // Use document fragment for better performance
        const fragment = document.createDocumentFragment();

        channels.forEach((channel, index) => {
            const item = document.createElement('div');
            item.className = 'channel-item';
            item.dataset.url = channel.url;
            item.dataset.name = channel.channel;
            item.dataset.index = index;
            item.tabIndex = 0;

            // Safe HTML escaping
            const safeChannel = this.escapeHtml(channel.channel || 'Unknown');
            const safeCategory = this.escapeHtml(channel.categories || channel.country || 'General');
            const safeLogo = this.escapeHtml(channel.logo || '');

            item.innerHTML = `
                <div class="channel-thumb">
                    <img src="${safeLogo}" alt="${safeChannel}" loading="lazy" onerror="this.outerHTML='<svg viewBox=\\'0 0 24 24\\' fill=\\'none\\' stroke=\\'%23888\\' stroke-width=\\'2\\' style=\\'width:32px;height:32px;\\'><rect x=\\'2\\' y=\\'7\\' width=\\'20\\' height=\\'15\\' rx=\\'2\\' ry=\\'2\\'></rect><polyline points=\\'17 2 12 7 7 2\\'></polyline></svg>'">
                </div>
                <div class="channel-details">
                    <div class="channel-name">${safeChannel}</div>
                    <div class="channel-category">${safeCategory}</div>
                </div>
                <div class="channel-play">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                </div>
            `;

            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openChannel(index);
            });

            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    this.openChannel(index);
                }
            });

            fragment.appendChild(item);
        });

        this.list.appendChild(fragment);
        this.channels = Array.from(this.list.querySelectorAll('.channel-item'));
        if (this.count) this.count.textContent = channels.length;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    playIptvChannel(channel) {
        const index = this.channels.findIndex(ch => ch.dataset.url === channel.url);
        if (index >= 0) this.openChannel(index);
    }

}

const player = new Player();
window.player = player;

// Global error handling untuk debug (hanya application error)
const filterKeywords = [
    'XrayWrapper',
    'NS_BINDING_ABORTED',
    'Not allowed to define cross-origin',
    '_pk_testcookie_domain',
    '_pk_ref',
    '_pk_id',
    '_pk_ses',
    'tam.js',
    'doubleclick.net',
    'securepubads',
    'ima_ppub_config',
    'detik.com',
    'livestreaming-',
    'gtm.js',
    'detikvideo.core.js',
    'detikbigdata',
    'detikliveusercounter',
    'AdsLoader error',
    'No Ads VAST'
];

function shouldFilterError(message) {
    const msgStr = (message || '').toLowerCase();
    return filterKeywords.some(keyword => msgStr.toLowerCase().includes(keyword.toLowerCase()));
}

window.addEventListener('error', (event) => {
    const message = event.message || '';
    const source = event.filename || '';

    if (shouldFilterError(message) || shouldFilterError(source)) {
        event.preventDefault();
        return;
    }

    // Log application errors only
}, true);

window.addEventListener('unhandledrejection', (event) => {
});

async function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tab) {
            btn.classList.add('active');
            btn.focus();
        }
    });

    player.tab = tab;

    const channelList = document.getElementById('channelList');
    const channelCount = document.getElementById('channelCount');

    if (channelList) {
        channelList.innerHTML = `
            <div style="grid-column: 1/-1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px;">
                <div class="loading-spinner"></div>
                <div class="loading-text">Memuat channels...</div>
            </div>
        `;
    }

    if (channelCount) channelCount.textContent = '...';

    if (tab === 'local') {
        player.channels = player.local;
        player.renderLocalChannels();
    } else if (tab === 'indonesia' || tab === 'global') {
        const channels = await player.fetchIptvChannels(tab);
        player.renderIptvChannels(channels);
    }

    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    setTimeout(() => {
        const firstChannel = document.querySelector('.channel-item');
        if (firstChannel) firstChannel.focus();
    }, 100);
}
