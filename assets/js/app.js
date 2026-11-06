document.addEventListener('DOMContentLoaded', () => {
    const settingsStatus = document.querySelector('[data-settings-status]');
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const guideToggle = document.querySelector('[data-guide-toggle]');
    const guideForms = [...document.querySelectorAll('[data-guide]')];
    const themeStorageKey = 'ruang-pkl-theme-v2';
    const showSettingsStatus = (message) => {
        if (!settingsStatus) return;
        settingsStatus.textContent = message;
        settingsStatus.hidden = false;
    };
    const selectTheme = (theme) => {
        const selectedTheme = theme === 'dark' ? 'dark' : 'light';
        document.body.dataset.theme = selectedTheme;
        if (themeToggle) themeToggle.checked = selectedTheme === 'dark';
    };
    const setGuidesVisible = (visible) => {
        guideForms.forEach((form) => {
            let guide = form.querySelector('[data-form-guide]');
            if (!guide) {
                guide = document.createElement('div');
                guide.className = 'form-guide';
                guide.dataset.formGuide = '';
                guide.setAttribute('role', 'note');
                const title = document.createElement('strong');
                title.textContent = 'Panduan';
                const message = document.createElement('span');
                message.textContent = form.dataset.guide || '';
                guide.append(title, message);
                form.prepend(guide);
            }
            guide.hidden = !visible;
        });
        if (guideToggle) guideToggle.checked = visible;
    };
    try {
        selectTheme(window.localStorage.getItem(themeStorageKey) || 'light');
        setGuidesVisible(window.localStorage.getItem('ruang-pkl-guide') === 'on');
    } catch (error) {
        selectTheme('light');
        setGuidesVisible(false);
        showSettingsStatus('Pengaturan browser tidak dapat dibaca. Pilihan hanya berlaku selama halaman ini terbuka.');
    }
    themeToggle?.addEventListener('change', () => {
        const theme = themeToggle.checked ? 'dark' : 'light';
        selectTheme(theme);
        try {
            window.localStorage.setItem(themeStorageKey, theme);
            showSettingsStatus('Tema tersimpan di browser ini.');
        } catch (error) {
            showSettingsStatus('Tema diterapkan, tetapi tidak dapat disimpan di browser ini.');
        }
    });
    guideToggle?.addEventListener('change', () => {
        const enabled = guideToggle.checked;
        setGuidesVisible(enabled);
        try {
            window.localStorage.setItem('ruang-pkl-guide', enabled ? 'on' : 'off');
            showSettingsStatus(enabled ? 'Panduan penggunaan aktif.' : 'Panduan penggunaan nonaktif.');
        } catch (error) {
            showSettingsStatus('Pilihan panduan diterapkan, tetapi tidak dapat disimpan di browser ini.');
        }
    });

    document.querySelectorAll('[data-auto-dismiss]').forEach((notice) => {
        window.setTimeout(() => {
            notice.classList.add('is-dismissing');
            window.setTimeout(() => notice.remove(), 250);
        }, 5000);
    });

    const tabButtons = [...document.querySelectorAll('[data-auth-tab]')];
    const authPanels = [...document.querySelectorAll('[data-auth-panel]')];
    const authTabs = document.querySelector('.auth-tabs');
    const recoveryPanels = [...document.querySelectorAll('[data-recovery-panel]')];
    const authTitle = document.querySelector('[data-auth-title]');
    const authSubtitle = document.querySelector('[data-auth-subtitle]');
    const showAuthPanel = (selected) => {
        if (authTabs) authTabs.hidden = false;
        authPanels.forEach((panel) => {
            panel.hidden = panel.dataset.authPanel !== selected;
        });
        recoveryPanels.forEach((panel) => {
            panel.hidden = true;
        });
        if (authTitle) authTitle.textContent = selected === 'register' ? 'Buat akun' : 'Masuk ke akun';
        if (authSubtitle) authSubtitle.textContent = selected === 'register'
            ? 'Lengkapi data untuk membuat akun baru.'
            : 'Gunakan NIP, NISN, atau username terdaftar.';
    };
    const showRecoveryPanel = (selected) => {
        if (authTabs) authTabs.hidden = true;
        authPanels.forEach((panel) => {
            panel.hidden = true;
        });
        recoveryPanels.forEach((panel) => {
            panel.hidden = panel.dataset.recoveryPanel !== selected;
        });
        if (authTitle) authTitle.textContent = selected === 'verify' ? 'Atur ulang kata sandi' : 'Lupa kata sandi';
        if (authSubtitle) authSubtitle.textContent = selected === 'verify'
            ? 'Masukkan kode OTP yang dikirim ke email Anda.'
            : 'Masukkan email yang terdaftar untuk menerima kode OTP.';
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const selected = button.dataset.authTab;
            tabButtons.forEach((tab) => {
                const active = tab === button;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', String(active));
            });
            showAuthPanel(selected);
        });
    });
    document.querySelector('[data-forgot-password]')?.addEventListener('click', () => showRecoveryPanel('request'));
    document.querySelectorAll('[data-return-login]').forEach((button) => {
        button.addEventListener('click', () => showAuthPanel('login'));
    });
    document.querySelector('[data-resend-otp]')?.addEventListener('click', () => {
        document.querySelector('[data-resend-form]')?.requestSubmit();
    });

    const roleSelect = document.querySelector('[data-role-select]');
    const classLabel = document.querySelector('.register-class');
    const classField = document.querySelector('[data-class-field]');
    const emailLabel = document.querySelector('.register-email');
    const emailField = document.querySelector('[data-admin-field]');
    const updateRegistrationFields = () => {
        if (!roleSelect) return;
        const isAdmin = roleSelect.value === 'admin';
        classLabel.hidden = isAdmin;
        classField.disabled = isAdmin;
        classField.required = !isAdmin;
        emailLabel.hidden = !isAdmin;
        emailField.disabled = !isAdmin;
        emailField.required = isAdmin;
    };
    roleSelect?.addEventListener('change', updateRegistrationFields);
    updateRegistrationFields();

    const dateOptions = { day: 'numeric', month: 'long', year: 'numeric' };
    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    const zoneLabel = /Jakarta|Pontianak/i.test(timeZone) ? 'WIB' : (/Makassar|Ujung_Pandang|Denpasar|Mataram|Kupang/i.test(timeZone) ? 'WITA' : (/Jayapura|Ambon/i.test(timeZone) ? 'WIT' : timeZone));
    const updateClock = () => {
        const now = new Date();
        const dateText = now.toLocaleDateString('id-ID', dateOptions);
        const dayText = `${dayNames[now.getDay()]}, ${dateText}`;
        document.querySelectorAll('[data-local-day]').forEach((element) => { element.textContent = dayText; });
        document.querySelectorAll('[data-local-date]').forEach((element) => { element.textContent = dateText; });
        document.querySelectorAll('[data-local-clock]').forEach((element) => { element.textContent = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' }); });
        document.querySelectorAll('[data-local-zone]').forEach((element) => { element.textContent = zoneLabel; });
    };
    updateClock();
    window.setInterval(updateClock, 1000);

    const placeForm = document.querySelector('[data-place-form]');
    const placeMapElement = document.querySelector('[data-place-picker-map]');
    if (placeForm && placeMapElement) {
        const latitudeInput = placeForm.querySelector('[data-coordinate-latitude]');
        const longitudeInput = placeForm.querySelector('[data-coordinate-longitude]');
        const feedback = placeForm.querySelector('[data-coordinate-feedback]');
        let marker = null;
        let map = null;
        if (window.L) {
            map = window.L.map(placeMapElement).setView([-2.5, 118], 5);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            }).addTo(map);
            const initialLatitude = Number(latitudeInput.value);
            const initialLongitude = Number(longitudeInput.value);
            if (latitudeInput.value !== '' && longitudeInput.value !== ''
                && Number.isFinite(initialLatitude) && Number.isFinite(initialLongitude)) {
                marker = window.L.marker([initialLatitude, initialLongitude]).addTo(map);
                map.setView([initialLatitude, initialLongitude], 15);
            }
            map.on('click', (event) => {
                const { lat, lng } = event.latlng;
                latitudeInput.value = lat.toFixed(7);
                longitudeInput.value = lng.toFixed(7);
                if (marker) marker.setLatLng(event.latlng);
                else marker = window.L.marker(event.latlng).addTo(map);
                feedback.textContent = 'Titik peta dipilih. Periksa koordinat sebelum menyimpan.';
            });
            window.setTimeout(() => map.invalidateSize(), 0);
        } else {
            placeMapElement.textContent = 'Peta tidak dapat dimuat. Anda tetap dapat memasukkan koordinat secara manual atau memakai lokasi perangkat.';
            placeMapElement.classList.add('map-unavailable');
        }

        placeForm.querySelector('[data-get-device-location]')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                feedback.textContent = 'Browser ini tidak mendukung pembacaan lokasi.';
                return;
            }
            const button = placeForm.querySelector('[data-get-device-location]');
            button.disabled = true;
            feedback.textContent = 'Meminta lokasi perangkat… Izinkan akses lokasi di browser.';
            navigator.geolocation.getCurrentPosition((position) => {
                const { latitude, longitude, accuracy } = position.coords;
                latitudeInput.value = latitude.toFixed(7);
                longitudeInput.value = longitude.toFixed(7);
                if (map) {
                    const location = [latitude, longitude];
                    map.setView(location, 17);
                    if (marker) marker.setLatLng(location);
                    else marker = window.L.marker(location).addTo(map);
                }
                feedback.textContent = `Lokasi perangkat terisi (akurasi sekitar ${Math.round(accuracy)} m). Pastikan titiknya sesuai sebelum menyimpan.`;
                button.disabled = false;
            }, (error) => {
                const messages = {
                    1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu coba lagi.',
                    2: 'Lokasi tidak tersedia. Aktifkan GPS atau pilih titik di peta.',
                    3: 'Pembacaan lokasi terlalu lama. Coba lagi atau pilih titik di peta.',
                };
                feedback.textContent = messages[error.code] || 'Lokasi tidak dapat dibaca.';
                button.disabled = false;
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    }

    document.querySelectorAll('[data-geolocation-form]').forEach((form) => {
        const latitude = form.querySelector('[name="latitude"]');
        const longitude = form.querySelector('[name="longitude"]');
        const photoInput = form.querySelector('[data-attendance-photo]');
        const overlay = form.querySelector('[data-attendance-overlay]');
        const video = form.querySelector('[data-attendance-video]');
        const preview = form.querySelector('[data-attendance-preview]');
        const previewCard = form.querySelector('[data-attendance-preview-card]');
        const previewThumb = form.querySelector('[data-attendance-preview-thumb]');
        const feedback = form.querySelector('[data-location-feedback]');
        const cameraFeedback = form.querySelector('[data-camera-feedback]');
        const submitButton = form.querySelector('[data-location-button]');
        const startButton = form.querySelector('[data-camera-start]');
        const captureButton = form.querySelector('[data-camera-capture]');
        const retakeButton = form.querySelector('[data-camera-retake]');
        const useButton = form.querySelector('[data-camera-use]');
        const reopenButton = form.querySelector('[data-camera-reopen]');
        let cameraStream = null;
        let previewUrl = null;
        let captureSequence = 0;
        let lastGeocodeRequest = 0;
        const addressCache = new Map();
        document.body.append(overlay);

        const setCameraMessage = (message) => {
            if (feedback) feedback.textContent = message;
            if (cameraFeedback) cameraFeedback.textContent = message;
        };
        const stopCamera = () => {
            cameraStream?.getTracks().forEach((track) => track.stop());
            cameraStream = null;
            if (video) video.srcObject = null;
        };
        const resetPhoto = () => {
            photoInput.value = '';
            latitude.value = '';
            longitude.value = '';
            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = null;
            preview.removeAttribute('src');
            previewThumb.removeAttribute('src');
            previewCard.hidden = true;
            startButton.disabled = false;
        };
        const closeCameraOverlay = (discardPhoto) => {
            overlay.hidden = true;
            stopCamera();
            if (discardPhoto) {
                captureSequence += 1;
                resetPhoto();
            }
            captureButton.hidden = false;
            captureButton.disabled = false;
            retakeButton.hidden = true;
            useButton.hidden = true;
            video.hidden = false;
            preview.hidden = true;
            startButton.disabled = false;
            startButton.focus();
        };
        const readDeviceLocation = () => new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Browser ini tidak mendukung pembacaan lokasi.'));
                return;
            }
            navigator.geolocation.getCurrentPosition(
                resolve,
                (error) => {
                    const messages = {
                        1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu coba lagi.',
                        2: 'Lokasi tidak tersedia. Pastikan GPS perangkat aktif.',
                        3: 'Pembacaan lokasi terlalu lama. Coba lagi.',
                    };
                    reject(new Error(messages[error.code] || 'Lokasi tidak dapat dibaca.'));
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
            );
        });
        const loadMapTile = (deviceLatitude, deviceLongitude) => new Promise((resolve, reject) => {
            const zoom = 17;
            const tileCount = 2 ** zoom;
            const boundedLatitude = Math.max(-85.05112878, Math.min(85.05112878, deviceLatitude));
            const latitudeRadians = boundedLatitude * Math.PI / 180;
            const tileX = Math.min(tileCount - 1e-9, Math.max(0, (deviceLongitude + 180) / 360 * tileCount));
            const tileY = Math.min(tileCount - 1e-9, Math.max(0, (1 - Math.asinh(Math.tan(latitudeRadians)) / Math.PI) / 2 * tileCount));
            const image = new Image();
            image.crossOrigin = 'anonymous';
            image.onload = () => resolve({
                image,
                offsetX: (tileX - Math.floor(tileX)) * 256,
                offsetY: (tileY - Math.floor(tileY)) * 256,
            });
            image.onerror = () => reject(new Error('Peta lokasi tidak dapat dimuat. Periksa koneksi internet lalu coba lagi.'));
            image.src = `https://tile.openstreetmap.org/${zoom}/${Math.floor(tileX)}/${Math.floor(tileY)}.png`;
        });
        const lookupAddress = async (deviceLatitude, deviceLongitude) => {
            const cacheKey = `${deviceLatitude.toFixed(5)},${deviceLongitude.toFixed(5)}`;
            if (addressCache.has(cacheKey)) return addressCache.get(cacheKey);
            const delay = Math.max(0, 1100 - (Date.now() - lastGeocodeRequest));
            if (delay) await new Promise((resolve) => window.setTimeout(resolve, delay));
            lastGeocodeRequest = Date.now();
            const parameters = new URLSearchParams({
                format: 'jsonv2',
                lat: deviceLatitude,
                lon: deviceLongitude,
                zoom: '18',
                addressdetails: '1',
                'accept-language': 'id',
            });
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${parameters}`);
            if (!response.ok) throw new Error('Alamat lokasi tidak dapat ditemukan. Periksa koneksi lalu coba lagi.');
            const result = await response.json();
            if (typeof result.display_name !== 'string' || !result.display_name.trim()) {
                throw new Error('Alamat lokasi tidak ditemukan. Pindah ke area terbuka lalu coba kembali.');
            }
            addressCache.set(cacheKey, result.display_name);
            return result.display_name;
        };
        const drawWrappedText = (context, text, x, y, maxWidth, lineHeight, maxLines) => {
            const words = text.split(/\s+/);
            let line = '';
            let lineCount = 0;
            words.forEach((word) => {
                const candidate = line ? `${line} ${word}` : word;
                if (context.measureText(candidate).width > maxWidth && line) {
                    context.fillText(line, x, y + lineCount * lineHeight, maxWidth);
                    lineCount += 1;
                    line = word;
                } else {
                    line = candidate;
                }
            });
            if (line && lineCount < maxLines) context.fillText(line, x, y + lineCount * lineHeight, maxWidth);
        };

        startButton?.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                setCameraMessage('Kamera tidak tersedia. Gunakan browser yang mendukung kamera melalui HTTPS atau localhost.');
                return;
            }
            resetPhoto();
            startButton.disabled = true;
            overlay.hidden = false;
            overlay.focus();
            setCameraMessage('Meminta izin kamera…');
            try {
                stopCamera();
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 1280 } },
                });
                video.srcObject = cameraStream;
                video.hidden = false;
                preview.hidden = true;
                captureButton.hidden = false;
                retakeButton.hidden = true;
                useButton.hidden = true;
                await video.play();
                setCameraMessage('Arahkan kamera, lalu ambil foto untuk membuat watermark lokasi.');
            } catch (error) {
                stopCamera();
                closeCameraOverlay(true);
                setCameraMessage(error.name === 'NotAllowedError'
                    ? 'Izin kamera ditolak. Aktifkan izin kamera browser lalu coba lagi.'
                    : 'Kamera tidak dapat dibuka. Pastikan tidak sedang digunakan aplikasi lain.');
                startButton.disabled = false;
            }
        });

        captureButton?.addEventListener('click', async () => {
            if (!video.videoWidth || !video.videoHeight) {
                setCameraMessage('Kamera belum siap. Tunggu sebentar lalu coba lagi.');
                return;
            }
            const activeCapture = ++captureSequence;
            captureButton.disabled = true;
            setCameraMessage('Mengambil lokasi GPS untuk watermark…');
            try {
                const position = await readDeviceLocation();
                const { latitude: deviceLatitude, longitude: deviceLongitude, accuracy } = position.coords;
                if (activeCapture !== captureSequence) return;
                setCameraMessage('Mencari alamat dan memuat peta lokasi…');
                const [address, mapTile] = await Promise.all([
                    lookupAddress(deviceLatitude, deviceLongitude),
                    loadMapTile(deviceLatitude, deviceLongitude),
                ]);
                if (activeCapture !== captureSequence) return;
                const scale = Math.min(1, 1280 / video.videoWidth);
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(video.videoWidth * scale);
                canvas.height = Math.round(video.videoHeight * scale);
                const context = canvas.getContext('2d');
                if (!context) throw new Error('Foto tidak dapat diproses oleh browser ini.');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const fontScale = Math.max(.72, canvas.width / 720);
                const footerHeight = Math.max(142 * fontScale, Math.round(canvas.height * .24));
                const mapX = Math.round(14 * fontScale);
                const mapY = canvas.height - footerHeight + Math.round(12 * fontScale);
                const mapWidth = Math.round(Math.min(190 * fontScale, canvas.width * .28));
                const mapHeight = footerHeight - Math.round(24 * fontScale);
                context.fillStyle = 'rgba(10, 20, 15, .76)';
                context.fillRect(0, canvas.height - footerHeight, canvas.width, footerHeight);
                context.drawImage(mapTile.image, mapX, mapY, mapWidth, mapHeight);
                const markerX = mapX + mapTile.offsetX / 256 * mapWidth;
                const markerY = mapY + mapTile.offsetY / 256 * mapHeight;
                context.fillStyle = '#fff';
                context.beginPath();
                context.arc(markerX, markerY, 9 * fontScale, 0, Math.PI * 2);
                context.fill();
                context.fillStyle = '#d33b35';
                context.beginPath();
                context.arc(markerX, markerY, 5 * fontScale, 0, Math.PI * 2);
                context.fill();
                context.strokeStyle = 'rgba(255, 255, 255, .9)';
                context.lineWidth = fontScale;
                context.strokeRect(mapX, mapY, mapWidth, mapHeight);
                context.fillStyle = '#ffffff';
                context.textBaseline = 'top';
                const textX = mapX + mapWidth + Math.round(12 * fontScale);
                const textWidth = canvas.width - textX - Math.round(12 * fontScale);
                const textTop = canvas.height - footerHeight + Math.round(12 * fontScale);
                context.font = `700 ${Math.round(14 * fontScale)}px sans-serif`;
                context.fillText('BUKTI PRESENSI · RUANG PKL', textX, textTop, textWidth);
                context.font = `${Math.round(11 * fontScale)}px sans-serif`;
                const timeText = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'medium' }).format(new Date());
                context.fillText(timeText, textX, textTop + 22 * fontScale, textWidth);
                context.font = `700 ${Math.round(10 * fontScale)}px sans-serif`;
                context.fillText('ALAMAT', textX, textTop + 42 * fontScale, textWidth);
                context.font = `${Math.round(9 * fontScale)}px sans-serif`;
                drawWrappedText(context, address, textX, textTop + 56 * fontScale, textWidth, 12 * fontScale, 2);
                context.font = `${Math.round(8 * fontScale)}px sans-serif`;
                context.fillText(`${deviceLatitude.toFixed(6)}, ${deviceLongitude.toFixed(6)} · akurasi ±${Math.round(accuracy)} m`, textX, textTop + 83 * fontScale, textWidth);
                context.fillText('© OpenStreetMap contributors', textX, textTop + 97 * fontScale, textWidth);

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', .82));
                if (activeCapture !== captureSequence) return;
                if (!blob) throw new Error('Foto gagal dibuat. Coba ambil ulang.');
                if (blob.size > 5 * 1024 * 1024) throw new Error('Ukuran foto melebihi 5 MB. Coba ambil ulang.');
                const file = new File([blob], `presensi-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                photoInput.files = transfer.files;
                latitude.value = deviceLatitude.toFixed(7);
                longitude.value = deviceLongitude.toFixed(7);
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = URL.createObjectURL(blob);
                preview.src = previewUrl;
                previewThumb.src = previewUrl;
                previewCard.hidden = true;
                preview.hidden = false;
                video.hidden = true;
                retakeButton.hidden = false;
                useButton.hidden = false;
                captureButton.hidden = true;
                setCameraMessage('Foto, peta, alamat, dan watermark lokasi siap.');
            } catch (error) {
                if (activeCapture === captureSequence) {
                    setCameraMessage(error.message || 'Foto gagal diambil. Coba lagi.');
                }
            } finally {
                captureButton.disabled = false;
            }
        });

        retakeButton?.addEventListener('click', () => {
            resetPhoto();
            retakeButton.hidden = true;
            useButton.hidden = true;
            captureButton.hidden = false;
            preview.hidden = true;
            video.hidden = false;
            setCameraMessage('Arahkan kamera, lalu ambil foto untuk membuat watermark lokasi.');
            if (cameraStream && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) return;
            closeCameraOverlay(true);
            startButton.click();
        });
        useButton?.addEventListener('click', () => {
            closeCameraOverlay(false);
            previewCard.hidden = false;
            setCameraMessage('Foto dengan peta, alamat, dan watermark lokasi siap. Kirim presensi untuk menyimpan.');
        });
        reopenButton?.addEventListener('click', () => startButton.click());
        overlay?.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeCameraOverlay(true);
                setCameraMessage('Pengambilan foto dibatalkan.');
            }
        });
        overlay?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeCameraOverlay(true);
                setCameraMessage('Pengambilan foto dibatalkan.');
            }
        });
        form.addEventListener('submit', async (event) => {
            if (!photoInput?.files.length) {
                event.preventDefault();
                setCameraMessage('Silakan buka kamera dan ambil foto sebelum mengirim presensi.');
                return;
            }
            if (latitude.value && longitude.value) {
                stopCamera();
                submitButton.disabled = true;
                return;
            }
            event.preventDefault();
            submitButton.disabled = true;
            setCameraMessage('Mengambil koordinat GPS…');
            try {
                const position = await readDeviceLocation();
                latitude.value = position.coords.latitude.toFixed(7);
                longitude.value = position.coords.longitude.toFixed(7);
                setCameraMessage('Lokasi didapat. Mengirim presensi…');
                form.requestSubmit();
            } catch (error) {
                setCameraMessage(error.message);
                submitButton.disabled = false;
            }
        });
        window.addEventListener('pagehide', stopCamera);
    });

    const photoDialog = document.querySelector('[data-photo-dialog]');
    const photoDialogImage = photoDialog?.querySelector('[data-photo-dialog-image]');
    document.querySelectorAll('[data-photo-view]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!photoDialog || typeof photoDialog.showModal !== 'function') return;
            event.preventDefault();
            photoDialogImage.src = link.href;
            photoDialog.showModal();
        });
    });
    const closePhotoDialog = () => {
        if (photoDialog?.open) photoDialog.close();
        if (photoDialogImage) photoDialogImage.removeAttribute('src');
    };
    photoDialog?.querySelector('[data-photo-close]')?.addEventListener('click', closePhotoDialog);
    photoDialog?.addEventListener('click', (event) => {
        if (event.target === photoDialog) closePhotoDialog();
    });
    photoDialog?.addEventListener('close', () => {
        if (photoDialogImage) photoDialogImage.removeAttribute('src');
    });

    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    const confirmTitle = confirmDialog?.querySelector('[data-confirm-dialog-title]');
    let pendingConfirmForm = null;
    let confirmedForm = null;
    document.querySelectorAll('form[data-confirm-title]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (confirmedForm === form) {
                confirmedForm = null;
                return;
            }
            event.preventDefault();
            if (typeof confirmDialog?.showModal === 'function') {
                pendingConfirmForm = form;
                if (confirmTitle) confirmTitle.textContent = form.dataset.confirmTitle || 'Yakin ingin menghapus data ini?';
                confirmDialog.showModal();
            } else if (window.confirm(form.dataset.confirmTitle || 'Yakin ingin menghapus data ini?')) {
                confirmedForm = form;
                form.requestSubmit();
            }
        });
    });
    confirmDialog?.querySelector('[data-cancel-confirm]')?.addEventListener('click', () => {
        pendingConfirmForm = null;
        confirmDialog.close();
    });
    confirmDialog?.querySelector('[data-submit-confirm]')?.addEventListener('click', () => {
        if (!pendingConfirmForm) return;
        confirmedForm = pendingConfirmForm;
        pendingConfirmForm = null;
        confirmDialog.close();
        confirmedForm.requestSubmit();
    });
    confirmDialog?.addEventListener('cancel', () => {
        pendingConfirmForm = null;
    });
    confirmDialog?.addEventListener('click', (event) => {
        if (event.target === confirmDialog) {
            pendingConfirmForm = null;
            confirmDialog.close();
        }
    });

    const logbookForm = document.querySelector('[data-logbook-form]');
    document.querySelectorAll('[data-revise-logbook]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!logbookForm) return;
            logbookForm.querySelector('[name="tanggal"]').value = button.dataset.date;
            logbookForm.querySelector('[name="kegiatan"]').value = button.dataset.activity;
            logbookForm.querySelector('textarea').focus();
            document.querySelector('[data-logbook-heading]').textContent = 'Perbaiki kegiatanmu';
            logbookForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    const sidebar = document.querySelector('[data-sidebar]');
    document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => {
        sidebar?.classList.toggle('is-open');
    });
    document.addEventListener('click', (event) => {
        if (sidebar?.classList.contains('is-open') && !sidebar.contains(event.target) && !event.target.closest('[data-menu-toggle]')) {
            sidebar.classList.remove('is-open');
        }
    });
});