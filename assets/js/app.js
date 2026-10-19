document.addEventListener('DOMContentLoaded', () => {
    const settingsStatus = document.querySelector('[data-settings-status]');
    const themeChoices = [...document.querySelectorAll('[data-theme-choice]')];
    const guideChoices = [...document.querySelectorAll('[data-guide-choice]')];
    const guideForms = [...document.querySelectorAll('[data-guide]')];
    const showSettingsStatus = (message) => {
        if (!settingsStatus) return;
        settingsStatus.textContent = message;
        settingsStatus.hidden = false;
    };
    const selectTheme = (theme) => {
        const selectedTheme = theme === 'dark' ? 'dark' : 'light';
        document.body.dataset.theme = selectedTheme;
        themeChoices.forEach((choice) => {
            choice.checked = choice.value === selectedTheme;
        });
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
        guideChoices.forEach((choice) => {
            choice.checked = (choice.value === 'on') === visible;
        });
    };
    try {
        selectTheme(window.localStorage.getItem('ruang-pkl-theme') || 'light');
        setGuidesVisible(window.localStorage.getItem('ruang-pkl-guide') === 'on');
    } catch (error) {
        selectTheme('light');
        setGuidesVisible(false);
        showSettingsStatus('Pengaturan browser tidak dapat dibaca. Pilihan hanya berlaku selama halaman ini terbuka.');
    }
    themeChoices.forEach((choice) => {
        choice.addEventListener('change', () => {
            if (!choice.checked) return;
            selectTheme(choice.value);
            try {
                window.localStorage.setItem('ruang-pkl-theme', choice.value);
                showSettingsStatus('Tema tersimpan di browser ini.');
            } catch (error) {
                showSettingsStatus('Tema diterapkan, tetapi tidak dapat disimpan di browser ini.');
            }
        });
    });
    guideChoices.forEach((choice) => {
        choice.addEventListener('change', () => {
            if (!choice.checked) return;
            const enabled = choice.value === 'on';
            setGuidesVisible(enabled);
            try {
                window.localStorage.setItem('ruang-pkl-guide', enabled ? 'on' : 'off');
                showSettingsStatus(enabled ? 'Panduan penggunaan aktif.' : 'Panduan penggunaan nonaktif.');
            } catch (error) {
                showSettingsStatus('Pilihan panduan diterapkan, tetapi tidak dapat disimpan di browser ini.');
            }
        });
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
        form.addEventListener('submit', (event) => {
            const latitude = form.querySelector('[name="latitude"]');
            if (latitude.value) return;
            event.preventDefault();
            const feedback = form.querySelector('[data-location-feedback]');
            const button = form.querySelector('[data-location-button]');
            if (!navigator.geolocation) {
                feedback.textContent = 'Browser ini tidak mendukung pembacaan lokasi.';
                return;
            }
            button.disabled = true;
            feedback.textContent = 'Mengambil koordinat GPS…';
            navigator.geolocation.getCurrentPosition((position) => {
                form.querySelector('[name="latitude"]').value = position.coords.latitude;
                form.querySelector('[name="longitude"]').value = position.coords.longitude;
                feedback.textContent = 'Lokasi didapat. Mengirim presensi…';
                form.requestSubmit();
            }, (error) => {
                const messages = {
                    1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu coba lagi.',
                    2: 'Lokasi tidak tersedia. Pastikan GPS perangkat aktif.',
                    3: 'Pembacaan lokasi terlalu lama. Coba lagi.',
                };
                feedback.textContent = messages[error.code] || 'Lokasi tidak dapat dibaca.';
                button.disabled = false;
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        });
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