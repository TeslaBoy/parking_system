let occupancyChartInstance = null;
let currentParkingPrice = 0;

/* ============================================
   DATA TABLES INIT
   ============================================ */
function initDataTables() {
    try {
        const selectors = [
            '#parkingsTable', '#vehiclesTable', '#bookingsTable',
            '#userBookingsTable', '#userVehiclesTable'
        ];
        selectors.forEach(selector => {
            if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().destroy();
            }
            if ($(selector).length && $.fn && $.fn.DataTable) {
                $(selector).DataTable({
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' },
                    pageLength: 10,
                    responsive: true,
                    order: [],
                    columnDefs: [{ orderable: false, targets: -1 }]
                });
            }
        });
    } catch(e) {
        console.warn('DataTables init skipped or failed', e);
    }
}

/* ============================================
   CHART INIT
   ============================================ */
function initChart() {
    try {
        const ctx = document.getElementById('occupancyChart');
        if (!ctx || !window.chartLabels || !window.chartData || typeof Chart === 'undefined') return;

        if (occupancyChartInstance) occupancyChartInstance.destroy();

        occupancyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: window.chartLabels,
                datasets: [{
                    label: 'Зайнято місць',
                    data: window.chartData,
                    backgroundColor: window.chartColors || 'rgba(79, 70, 229, 0.6)',
                    borderColor: window.chartColors ? window.chartColors.map(c => c.replace('0.7', '1')) : 'rgba(79, 70, 229, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 12 }, color: '#94a3b8' },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', weight: '600' },
                        bodyFont: { family: 'Inter' },
                        cornerRadius: 8,
                        padding: 12
                    }
                }
            }
        });
    } catch (e) {
        console.warn('Chart init failed', e);
    }
}

/* ============================================
   SIDEBAR TOGGLE (Mobile)
   ============================================ */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !toggle || !overlay) return;

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    });

    sidebar.addEventListener('click', function(e) {
        const link = e.target.closest('.sidebar-link');
        if (!link) return;
        
        e.preventDefault();
        const tabId = link.getAttribute('data-tab');
        if (!tabId) return;

        sidebar.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';

        const tabTrigger = document.querySelector(`[data-bs-target="#${tabId}"]`);
        if (tabTrigger) {
            tabTrigger.click();
        }
    });
}

/* ============================================
   AJAX FORM SUBMIT (із захистом від подвійних кліків)
   ============================================ */
function setupAjaxForm(buttonId, formId, action, modalId) {
    const btn = document.getElementById(buttonId);
    if (!btn) return;

    // ВИПРАВЛЕНО: Блокуємо повторне навішування обробника після AJAX оновлення
    if (btn.dataset.listenerAttached === 'true') return;
    btn.dataset.listenerAttached = 'true';

    btn.addEventListener('click', function() {
        const form = document.getElementById(formId);
        if (!form) return;
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Завантаження...';
        btn.disabled = true;

        fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (modalId) {
                    const modalEl = document.getElementById(modalId);
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                    }
                    // Видаляємо затемнення, якщо Bootstrap завис
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
                form.reset();
                showToast('success', 'Дані успішно збережено!');
                updateUI();
            } else {
                Swal.fire('Помилка', data.error || 'Невідома помилка', 'error');
            }
        })
        .catch(() => Swal.fire('Помилка', 'Проблема зі з\'єднанням із сервером', 'error'))
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
}

/* ============================================
   RE-INIT FORMS
   ============================================ */
function reinitForms() {
    setupAjaxForm('saveParkingBtn',  'addParkingForm',  'add_parking',  'addParkingModal');
    setupAjaxForm('updateParkingBtn','editParkingForm',  'update_parking','editParkingModal');
    setupAjaxForm('saveVehicleBtn',  'addVehicleForm',   'add_vehicle',   'addVehicleModal');
    setupAjaxForm('updateVehicleBtn','editVehicleForm',  'update_vehicle','editVehicleModal');
    setupAjaxForm('saveBookingBtn',  'addBookingForm',   'add_booking',   'addBookingModal');
    setupAjaxForm('updateBookingBtn','editBookingForm',  'update_booking','editBookingModal');
    // Логін та реєстрація оновлюються класичним POST, тому їх можна не перехоплювати AJAX
}

/* ============================================
   UI UPDATE (AJAX refresh)
   ============================================ */
async function updateUI() {
    try {
        const res = await fetch(window.location.href);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        // Admin tabs
        const currentAdminTabs = document.getElementById('adminTabsContent');
        if (currentAdminTabs) {
            const newAdminTabs = doc.getElementById('adminTabsContent');
            const activePaneId = currentAdminTabs.querySelector('.tab-pane.active, .tab-pane.show.active')?.id || 'admin-dash';

            ['#parkingsTable', '#vehiclesTable', '#bookingsTable'].forEach(sel => {
                if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy();
            });

            currentAdminTabs.innerHTML = newAdminTabs.innerHTML;
            currentAdminTabs.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            const activeEl = document.getElementById(activePaneId);
            if (activeEl) activeEl.classList.add('show', 'active');

            const chartLabels = doc.querySelector('script')?.textContent;
            if (chartLabels) {
                const match = chartLabels.match(/window\.chartLabels\s*=\s*(\[.*?\]);/s);
                const matchData = chartLabels.match(/window\.chartData\s*=\s*(\[.*?\]);/s);
                const matchColors = chartLabels.match(/window\.chartColors\s*=\s*(\[.*?\]);/s);
                if (match && window.chartLabels !== undefined) {
                    try {
                        window.chartLabels = JSON.parse(match[1]);
                        window.chartData = JSON.parse(matchData[1]);
                        window.chartColors = JSON.parse(matchColors[1]);
                    } catch(e) {}
                }
            }

            initChart();
            initDataTables();
            setupSidebarActive();
        }

        // User tabs
        const userTabs = document.getElementById('userTabsContent');
        if (userTabs && !currentAdminTabs) {
            const newUserTabs = doc.getElementById('userTabsContent');
            const activeUserPane = userTabs.querySelector('.tab-pane.active, .tab-pane.show.active')?.id || 'user-parkings';

            ['#userBookingsTable', '#userVehiclesTable'].forEach(sel => {
                if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy();
            });

            userTabs.innerHTML = newUserTabs.innerHTML;
            userTabs.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            const activeEl = document.getElementById(activeUserPane);
            if (activeEl) activeEl.classList.add('show', 'active');

            initDataTables();
            setupSidebarActive();
        }

        // Оновлюємо вміст модальних вікон (select-и свіжими авто/паркінгами)
        ['addBookingModal', 'editBookingModal', 'addVehicleModal', 'editVehicleModal', 'addParkingModal', 'editParkingModal'].forEach(id => {
            const currentModal = document.getElementById(id);
            const newModal = doc.getElementById(id);
            if (currentModal && newModal) {
                const currentBody = currentModal.querySelector('.modal-body');
                const newBody = newModal.querySelector('.modal-body');
                if (currentBody && newBody) currentBody.innerHTML = newBody.innerHTML;
            }
        });
        
    } catch (e) {
        console.error('Помилка AJAX оновлення', e);
        location.reload(); // Якщо щось пішло не так, робимо звичайне перезавантаження
    }
}

function setupSidebarActive() {
    document.querySelectorAll('#userTabs .nav-link, #adminTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target');
            if (!targetId) return;
            const tabName = targetId.replace('#', '');
            document.querySelectorAll('.sidebar-link').forEach(link => {
                link.classList.toggle('active', link.getAttribute('data-tab') === tabName);
            });
        });
    });
}

function showToast(icon, title) {
    if(typeof Swal !== 'undefined') {
        Swal.fire({
            icon: icon, title: title, toast: true, position: 'top-end',
            showConfirmButton: false, timer: 2500, timerProgressBar: true
        });
    } else {
        alert(title);
    }
}

/* ============================================
   EVENT DELEGATION
   ============================================ */
document.addEventListener('click', function(e) {
    
    // ВІДПРАВКА ДАНИХ У МОДАЛКУ БРОНЮВАННЯ (З КНОПКИ ПАРКІНГУ)
    const btnPrepare = e.target.closest('.btn-prepare-booking');
    if (btnPrepare) {
        const pId = document.getElementById('bookingHiddenParkingId');
        if(pId) pId.value = btnPrepare.dataset.id;
        
        const pName = document.getElementById('displayParkingName');
        if(pName) pName.textContent = btnPrepare.dataset.name;
        
        currentParkingPrice = parseFloat(btnPrepare.dataset.price) || 0;
        const pPrice = document.getElementById('displayParkingPrice');
        if(pPrice) pPrice.textContent = currentParkingPrice.toFixed(2);

        const st = document.getElementById('bookingStartTime');
        if(st) st.value = '';
        const et = document.getElementById('bookingEndTime');
        if(et) et.value = '';
        const est = document.getElementById('estimatedPrice');
        if(est) est.textContent = '0.00 ₴';
        return;
    }

    // РЕДАГУВАННЯ ПАРКІНГУ
    const btnEditParking = e.target.closest('.btn-edit-parking');
    if (btnEditParking) {
        fetch(`api.php?action=get_parking&id=${btnEditParking.dataset.id}`)
            .then(res => res.json())
            .then(data => {
                const f = document.getElementById('editParkingForm');
                if(!f) return;
                f.querySelector('[name="id"]').value = data.id;
                f.querySelector('[name="name"]').value = data.name;
                f.querySelector('[name="address"]').value = data.address;
                f.querySelector('[name="capacity"]').value = data.capacity;
                f.querySelector('[name="available"]').value = data.available;
                f.querySelector('[name="price_per_hour"]').value = data.price_per_hour;
                new bootstrap.Modal(document.getElementById('editParkingModal')).show();
            });
        return;
    }

    // РЕДАГУВАННЯ АВТО
    const btnEditVehicle = e.target.closest('.btn-edit-vehicle');
    if (btnEditVehicle) {
        fetch(`api.php?action=get_vehicle&id=${btnEditVehicle.dataset.id}`)
            .then(res => res.json())
            .then(data => {
                const f = document.getElementById('editVehicleForm');
                if(!f) return;
                f.querySelector('[name="id"]').value = data.id;
                f.querySelector('[name="license_plate"]').value = data.license_plate;
                f.querySelector('[name="brand"]').value = data.brand;
                f.querySelector('[name="model"]').value = data.model;
                f.querySelector('[name="color"]').value = data.color;
                new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
            });
        return;
    }

    // РЕДАГУВАННЯ БРОНЮВАННЯ
    const btnEditBooking = e.target.closest('.btn-edit-booking');
    if (btnEditBooking) {
        fetch(`api.php?action=get_booking&id=${btnEditBooking.dataset.id}`)
            .then(res => res.json())
            .then(data => {
                const f = document.getElementById('editBookingForm');
                if(!f) return;
                f.querySelector('[name="id"]').value = data.id;
                f.querySelector('[name="parking_id"]').value = data.parking_id;
                f.querySelector('[name="vehicle_id"]').value = data.vehicle_id;
                f.querySelector('[name="start_time"]').value = data.start_time.replace(' ', 'T');
                f.querySelector('[name="end_time"]').value = data.end_time.replace(' ', 'T');
                f.querySelector('[name="status"]').value = data.status;
                new bootstrap.Modal(document.getElementById('editBookingModal')).show();
            });
        return;
    }

    // ВИДАЛЕННЯ
    const btnDeleteP = e.target.closest('.btn-delete-parking');
    const btnDeleteV = e.target.closest('.btn-delete-vehicle');
    const btnDeleteB = e.target.closest('.btn-delete-booking');

    let action = null, id = null, text = '';
    if (btnDeleteP) { action = 'delete_parking'; id = btnDeleteP.dataset.id; text = 'цей паркінг'; }
    else if (btnDeleteV) { action = 'delete_vehicle'; id = btnDeleteV.dataset.id; text = 'цей транспорт'; }
    else if (btnDeleteB) { action = 'delete_booking'; id = btnDeleteB.dataset.id; text = 'це бронювання'; }

    if (action) {
        Swal.fire({
            title: 'Ви впевнені?',
            text: `Ви дійсно хочете видалити ${text}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Так, видалити',
            cancelButtonText: 'Скасувати'
        }).then(result => {
            if (result.isConfirmed) {
                fetch(`api.php?action=${action}&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Успішно видалено!');
                            updateUI();
                        } else {
                            Swal.fire('Помилка', data.error, 'error');
                        }
                    });
            }
        });
        return;
    }

    // ЗМІНА СТАТУСУ (Підтвердити/Відхилити)
    const btnConfirm = e.target.closest('.btn-confirm-booking');
    const btnReject = e.target.closest('.btn-reject-booking');
    if (btnConfirm || btnReject) {
        const id = (btnConfirm || btnReject).dataset.id;
        const status = btnConfirm ? 'active' : 'cancelled';
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', status);

        fetch('api.php?action=change_booking_status', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Статус оновлено!');
                    updateUI();
                }
            });
        return;
    }
});

/* ============================================
   PRICE CALCULATOR
   ============================================ */
document.addEventListener('input', function(e) {
    if (!e.target.matches('#bookingStartTime, #bookingEndTime')) return;

    const startInput = document.getElementById('bookingStartTime');
    const endInput = document.getElementById('bookingEndTime');
    const priceDisplay = document.getElementById('estimatedPrice');
    if (!startInput || !endInput || !priceDisplay) return;

    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const minDateTime = now.toISOString().slice(0, 16);

    if (!startInput.min) startInput.min = minDateTime;
    if (e.target.id === 'bookingStartTime') endInput.min = startInput.value;

    const start = new Date(startInput.value);
    const end = new Date(endInput.value);

    if (start && end && !isNaN(start) && !isNaN(end) && end > start) {
        const diffHours = Math.ceil((end - start) / (1000 * 60 * 60));
        priceDisplay.textContent = (diffHours * currentParkingPrice).toFixed(2) + ' ₴';
    } else {
        priceDisplay.textContent = '0.00 ₴';
    }
});

/* ============================================
   INIT
   ============================================ */
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    setupSidebarActive();
    reinitForms();
    initChart();
    initDataTables();
});