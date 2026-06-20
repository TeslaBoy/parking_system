let occupancyChartInstance = null;
let currentParkingPrice = 0;

/* ============================================
   БЕЗПЕЧНІ ХЕЛПЕРИ ДЛЯ DOM
   ============================================ */
const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
const setText = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val; };

function showModalSafe(modalId) {
    const el = document.getElementById(modalId);
    if (!el) return;
    let modal = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    modal.show();
}

function hideModalSafe(modalId) {
    const el = document.getElementById(modalId);
    if (!el) return;
    try {
        const modal = bootstrap.Modal.getInstance(el);
        if (modal) { modal.hide(); modal.dispose(); }
    } catch(e) {}
    
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

function showToast(icon, title) {
    if(typeof Swal !== 'undefined') {
        Swal.fire({ icon: icon, title: title, toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true });
    } else alert(title);
}

/* ============================================
   ІНІЦІАЛІЗАЦІЯ БІБЛІОТЕК
   ============================================ */
function initDataTables() {
    try {
        const selectors = ['#parkingsTable', '#vehiclesTable', '#bookingsTable', '#userBookingsTable', '#userVehiclesTable'];
        selectors.forEach(selector => {
            if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                $(selector).DataTable().destroy();
            }
            if ($(selector).length && $.fn && $.fn.DataTable) {
                $(selector).DataTable({
                    language: { 
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json',
                        emptyTable: "<div class='text-center py-4 text-muted'><i class='bi bi-inbox fs-1 d-block mb-2'></i>Немає записів</div>"
                    },
                    pageLength: 10, responsive: true, order: [], columnDefs: [{ orderable: false, targets: -1 }]
                });
            }
        });
    } catch(e) { console.warn('DataTables init failed', e); }
}

function initChart() {
    try {
        const ctx = document.getElementById('occupancyChart');
        if (!ctx || !window.chartLabels || typeof Chart === 'undefined') return;
        if (occupancyChartInstance) occupancyChartInstance.destroy();

        occupancyChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: window.chartLabels,
                datasets: [{
                    label: 'Зайнято місць', data: window.chartData,
                    backgroundColor: window.chartColors || 'rgba(79, 70, 229, 0.6)',
                    borderWidth: 1, borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } },
                plugins: { legend: { display: false } }
            }
        });
    } catch (e) { console.warn('Chart init failed', e); }
}

/* ============================================
   ОБРОБКА ФОРМ (AJAX)
   ============================================ */
function setupAjaxForm(buttonId, formId, action, modalId) {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    if (btn.dataset.listenerAttached === 'true') return;
    btn.dataset.listenerAttached = 'true';

    btn.addEventListener('click', function() {
        const form = document.getElementById(formId);
        if (!form) return;
        if (!form.checkValidity()) { form.reportValidity(); return; }

        const formData = new FormData(form);
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>...';
        btn.disabled = true;

        fetch(`api.php?action=${action}`, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (modalId) hideModalSafe(modalId);
                form.reset();
                showToast('success', 'Збережено!');
                updateUI();
            } else {
                Swal.fire('Помилка', data.error || 'Невідома помилка', 'error');
            }
        })
        .catch(() => Swal.fire('Помилка', 'Проблема зі з\'єднанням', 'error'))
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    });
}

function reinitForms() {
    setupAjaxForm('saveParkingBtn',  'addParkingForm',  'add_parking',  'addParkingModal');
    setupAjaxForm('updateParkingBtn','editParkingForm',  'update_parking','editParkingModal');
    setupAjaxForm('saveVehicleBtn',  'addVehicleForm',   'add_vehicle',   'addVehicleModal');
    setupAjaxForm('updateVehicleBtn','editVehicleForm',  'update_vehicle','editVehicleModal');
    setupAjaxForm('saveBookingBtn',  'addBookingForm',   'add_booking',   'addBookingModal');
    setupAjaxForm('updateBookingBtn','editBookingForm',  'update_booking','editBookingModal');
}

/* ============================================
   SPA ОНОВЛЕННЯ ІНТЕРФЕЙСУ
   ============================================ */
async function updateUI() {
    try {
        const res = await fetch(window.location.href);
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const currentAdminTabs = document.getElementById('adminTabsContent');
        if (currentAdminTabs) {
            const newAdminTabs = doc.getElementById('adminTabsContent');
            const activePaneId = currentAdminTabs.querySelector('.tab-pane.active, .tab-pane.show.active')?.id || 'admin-dash';

            ['#parkingsTable', '#vehiclesTable', '#bookingsTable'].forEach(sel => { if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy(); });
            currentAdminTabs.innerHTML = newAdminTabs.innerHTML;
            currentAdminTabs.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            if (document.getElementById(activePaneId)) document.getElementById(activePaneId).classList.add('show', 'active');
            
            const chartScript = doc.querySelector('script')?.textContent;
            if (chartScript && chartScript.includes('window.chartLabels')) {
                try {
                    window.chartLabels = JSON.parse(chartScript.match(/window\.chartLabels\s*=\s*(\[.*?\]);/s)[1]);
                    window.chartData = JSON.parse(chartScript.match(/window\.chartData\s*=\s*(\[.*?\]);/s)[1]);
                } catch(e) {}
            }
            initChart(); initDataTables();
        }

        const userTabs = document.getElementById('userTabsContent');
        if (userTabs && !currentAdminTabs) {
            const newUserTabs = doc.getElementById('userTabsContent');
            const activeUserPane = userTabs.querySelector('.tab-pane.active, .tab-pane.show.active')?.id || 'user-parkings';

            ['#userBookingsTable', '#userVehiclesTable'].forEach(sel => { if ($.fn && $.fn.DataTable && $.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy(); });
            userTabs.innerHTML = newUserTabs.innerHTML;
            userTabs.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            if (document.getElementById(activeUserPane)) document.getElementById(activeUserPane).classList.add('show', 'active');
            initDataTables();
        }

        // БЕЗПЕЧНА заміна модалок
        ['addBookingModal', 'editBookingModal', 'addVehicleModal', 'editVehicleModal', 'addParkingModal', 'editParkingModal'].forEach(id => {
            const currentModal = document.getElementById(id);
            const newModal = doc.getElementById(id);
            if (currentModal && newModal) {
                try { const m = bootstrap.Modal.getInstance(currentModal); if(m) m.dispose(); } catch(e) {}
                currentModal.outerHTML = newModal.outerHTML; 
            }
        });
        reinitForms();
    } catch (e) {
        console.error('Помилка AJAX, перезавантаження...', e);
        location.reload();
    }
}

/* ============================================
   КЕРУВАННЯ ПОДІЯМИ ТА САЙДБАРОМ
   ============================================ */
document.addEventListener('click', function(e) {
    
    // --- КЕРУВАННЯ ЛІВИМ САЙДБАРОМ (Sidebar) ---
    const sidebarLink = e.target.closest('.sidebar-link');
    if (sidebarLink) {
        e.preventDefault();
        const tabId = sidebarLink.getAttribute('data-tab');
        if (!tabId) return;

        // Виділяємо активний лінк
        document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
        sidebarLink.classList.add('active');

        // Закриваємо мобільне меню (якщо воно відкрите)
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if(sidebar) sidebar.classList.remove('open');
        if(overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';

        // Перемикаємо вкладку у головному вікні
        const targetPane = document.getElementById(tabId);
        if (targetPane) {
            const container = targetPane.closest('.tab-content');
            if (container) container.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
            targetPane.classList.add('show', 'active');
        }
        return;
    }

    // Мобільна кнопка гамбургер
    const toggleBtn = e.target.closest('#sidebarToggle');
    if (toggleBtn) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if(sidebar) sidebar.classList.toggle('open');
        if(overlay) overlay.classList.toggle('show');
        document.body.style.overflow = sidebar?.classList.contains('open') ? 'hidden' : '';
        return;
    }

    // Клік по темному фону на мобільному для закриття меню
    if (e.target.id === 'sidebarOverlay') {
        const sidebar = document.getElementById('sidebar');
        if(sidebar) sidebar.classList.remove('open');
        e.target.classList.remove('show');
        document.body.style.overflow = '';
        return;
    }

    // --- МОДАЛКИ ТА КНОПКИ ---
    const btnPrepare = e.target.closest('.btn-prepare-booking');
    if (btnPrepare) {
        setVal('bookingHiddenParkingId', btnPrepare.dataset.id);
        setText('displayParkingName', btnPrepare.dataset.name);
        currentParkingPrice = parseFloat(btnPrepare.dataset.price) || 0;
        setText('displayParkingPrice', currentParkingPrice.toFixed(2));
        setVal('bookingStartTime', ''); setVal('bookingEndTime', ''); setText('estimatedPrice', '0.00 ₴');
        return;
    }

    const btnEditParking = e.target.closest('.btn-edit-parking');
    if (btnEditParking) {
        fetch(`api.php?action=get_parking&id=${btnEditParking.dataset.id}`).then(res => res.json()).then(data => {
            setVal('editParkingId', data.id); setVal('editParkingName', data.name); setVal('editParkingAddress', data.address);
            setVal('editParkingCapacity', data.capacity); setVal('editParkingAvailable', data.available); setVal('editParkingPrice', data.price_per_hour);
            showModalSafe('editParkingModal');
        }); return;
    }

    const btnEditVehicle = e.target.closest('.btn-edit-vehicle');
    if (btnEditVehicle) {
        fetch(`api.php?action=get_vehicle&id=${btnEditVehicle.dataset.id}`).then(res => res.json()).then(data => {
            setVal('editVehicleId', data.id); setVal('editVehicleLicensePlate', data.license_plate);
            setVal('editVehicleBrand', data.brand); setVal('editVehicleModel', data.model); setVal('editVehicleColor', data.color);
            showModalSafe('editVehicleModal');
        }); return;
    }

    const btnEditBooking = e.target.closest('.btn-edit-booking');
    if (btnEditBooking) {
        fetch(`api.php?action=get_booking&id=${btnEditBooking.dataset.id}`).then(res => res.json()).then(data => {
            setVal('editBookingId', data.id); setVal('editBookingParkingId', data.parking_id);
            setVal('editBookingVehicleId', data.vehicle_id); setVal('editBookingVehiclePlate', data.license_plate || 'Авто');
            setVal('editBookingStartTime', data.start_time.replace(' ', 'T')); setVal('editBookingEndTime', data.end_time.replace(' ', 'T'));
            setVal('editBookingStatus', data.status);
            showModalSafe('editBookingModal');
        }); return;
    }

    const btnDelete = e.target.closest('.btn-delete-parking, .btn-delete-vehicle, .btn-delete-booking');
    if (btnDelete) {
        let action = null, id = btnDelete.dataset.id, text = '';
        if (btnDelete.classList.contains('btn-delete-parking')) { action = 'delete_parking'; text = 'цей паркінг'; }
        else if (btnDelete.classList.contains('btn-delete-vehicle')) { action = 'delete_vehicle'; text = 'цей транспорт'; }
        else if (btnDelete.classList.contains('btn-delete-booking')) { action = 'delete_booking'; text = 'це бронювання'; }

        Swal.fire({
            title: 'Ви впевнені?', text: `Ви дійсно хочете видалити ${text}?`, icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Так, видалити'
        }).then(result => {
            if (result.isConfirmed) {
                fetch(`api.php?action=${action}&id=${id}`).then(res => res.json()).then(data => {
                    if (data.success) { showToast('success', 'Видалено!'); updateUI(); } else Swal.fire('Помилка', data.error, 'error');
                });
            }
        }); return;
    }

    const btnStatus = e.target.closest('.btn-confirm-booking, .btn-reject-booking');
    if (btnStatus) {
        const id = btnStatus.dataset.id;
        const status = btnStatus.classList.contains('btn-confirm-booking') ? 'active' : 'cancelled';
        const fd = new FormData(); fd.append('id', id); fd.append('status', status);
        fetch('api.php?action=change_booking_status', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if (data.success) { showToast('success', 'Статус оновлено!'); updateUI(); }
        }); return;
    }
});

/* ============================================
   РОЗРАХУНОК ЦІНИ ТА ОКРУГЛЕННЯ ЧАСУ
   ============================================ */
document.addEventListener('change', function(e) {
    if (!e.target.matches('#bookingStartTime, #bookingEndTime, #editBookingStartTime, #editBookingEndTime')) return;

    let val = e.target.value;
    if (val) {
        let d = new Date(val);
        let m = d.getMinutes();
        if (m !== 0 && m !== 30) {
            if (m < 15) m = 0; else if (m < 45) m = 30; else { m = 0; d.setHours(d.getHours() + 1); }
            d.setMinutes(m); d.setSeconds(0); d.setMilliseconds(0);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            e.target.value = d.toISOString().slice(0, 16);
            showToast('info', 'Час округлено до 30 хвилин');
        }
    }

    const startInput = e.target.id.includes('edit') ? document.getElementById('editBookingStartTime') : document.getElementById('bookingStartTime');
    const endInput = e.target.id.includes('edit') ? document.getElementById('editBookingEndTime') : document.getElementById('bookingEndTime');
    const priceDisplay = e.target.id.includes('edit') ? null : document.getElementById('estimatedPrice');
    
    if (!startInput || !endInput) return;

    const now = new Date();
    let nm = now.getMinutes();
    if (nm > 0 && nm <= 30) now.setMinutes(30); else if (nm > 30) { now.setHours(now.getHours() + 1); now.setMinutes(0); }
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    
    if (!startInput.min) startInput.min = now.toISOString().slice(0, 16);

    if (e.target.id === 'bookingStartTime' || e.target.id === 'editBookingStartTime') {
        if (startInput.value) {
            let minEnd = new Date(startInput.value);
            minEnd.setMinutes(minEnd.getMinutes() + 30);
            minEnd.setMinutes(minEnd.getMinutes() - minEnd.getTimezoneOffset());
            endInput.min = minEnd.toISOString().slice(0, 16);
            
            if (endInput.value && new Date(endInput.value) < new Date(endInput.min)) {
                endInput.value = endInput.min;
                showToast('warning', 'Мінімальний час - 30 хвилин');
            }
        }
    }

    if (priceDisplay && startInput.value && endInput.value) {
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        if (end > start) {
            const diffHours = (end - start) / (1000 * 60 * 60);
            priceDisplay.textContent = (diffHours * currentParkingPrice).toFixed(2) + ' ₴';
        } else priceDisplay.textContent = '0.00 ₴';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    reinitForms(); initChart(); initDataTables();
});