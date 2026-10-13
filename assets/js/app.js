document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = [...document.querySelectorAll('[data-auth-tab]')];
    const authPanels = [...document.querySelectorAll('[data-auth-panel]')];

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const selected = button.dataset.authTab;
            tabButtons.forEach((tab) => {
                const active = tab === button;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', String(active));
            });
            authPanels.forEach((panel) => {
                panel.hidden = panel.dataset.authPanel !== selected;
            });
        });
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