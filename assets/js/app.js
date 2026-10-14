document.addEventListener('DOMContentLoaded', () => {
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

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const deleteAccountForm = document.querySelector('[data-delete-account-form]');
    const deleteAccountDialog = document.querySelector('[data-delete-account-dialog]');
    let deleteAccountConfirmed = false;
    deleteAccountForm?.addEventListener('submit', (event) => {
        if (deleteAccountConfirmed) {
            deleteAccountConfirmed = false;
            return;
        }
        event.preventDefault();
        if (typeof deleteAccountDialog?.showModal === 'function') {
            deleteAccountDialog.showModal();
        } else if (window.confirm('Hapus akun Admin ini secara permanen?')) {
            deleteAccountConfirmed = true;
            deleteAccountForm.requestSubmit();
        }
    });
    deleteAccountDialog?.querySelector('[data-cancel-delete-account]')?.addEventListener('click', () => {
        deleteAccountDialog.close();
    });
    deleteAccountDialog?.querySelector('[data-confirm-delete-account]')?.addEventListener('click', () => {
        deleteAccountConfirmed = true;
        deleteAccountDialog.close();
        deleteAccountForm.requestSubmit();
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