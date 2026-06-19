// Допоміжна функція для безпечного додавання обробників подій
function addSafeListener(id, event, callback) {
    const el = document.getElementById(id);
    if (el) el.addEventListener(event, callback);
}

// Універсальна функція для відправки AJAX форм з використанням SweetAlert2
function setupAjaxForm(buttonId, formId, action) {
    addSafeListener(buttonId, 'click', function() {
        const form = document.getElementById(formId);
        if (!form) return;
        const formData = new FormData(form);
        
        fetch(`api.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Успішний попап замість перезавантаження відразу
                Swal.fire({
                    icon: 'success',
                    title: 'Успішно!',
                    text: 'Дані збережено.',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Помилка',
                    text: data.error || 'Невідома помилка'
                });
            }
        })
        .catch(err => {
            console.error('Помилка мережі:', err);
            Swal.fire('Помилка мережі', 'Не вдалося з\'єднатися із сервером.', 'error');
        });
    });
}

// Ініціалізація всіх кнопок дій
setupAjaxForm('saveParkingBtn', 'addParkingForm', 'add_parking');
setupAjaxForm('updateParkingBtn', 'editParkingForm', 'update_parking');
setupAjaxForm('saveVehicleBtn', 'addVehicleForm', 'add_vehicle');
setupAjaxForm('updateVehicleBtn', 'editVehicleForm', 'update_vehicle');
setupAjaxForm('saveBookingBtn', 'addBookingForm', 'add_booking');
setupAjaxForm('updateBookingBtn', 'editBookingForm', 'update_booking');
setupAjaxForm('registerBtn', 'registerForm', 'register');

// Обробка редагування
document.querySelectorAll('.btn-edit-parking').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        fetch(`api.php?action=get_parking&id=${id}`)
            .then(res => res.json())
            .then(data => {
                const form = document.getElementById('editParkingForm');
                form.querySelector('[name="id"]').value = data.id;
                form.querySelector('[name="name"]').value = data.name;
                form.querySelector('[name="address"]').value = data.address;
                form.querySelector('[name="capacity"]').value = data.capacity;
                form.querySelector('[name="available"]').value = data.available;
                form.querySelector('[name="price_per_hour"]').value = data.price_per_hour;
                
                new bootstrap.Modal(document.getElementById('editParkingModal')).show();
            });
    });
});

document.querySelectorAll('.btn-edit-vehicle').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        fetch(`api.php?action=get_vehicle&id=${id}`)
            .then(res => res.json())
            .then(data => {
                const form = document.getElementById('editVehicleForm');
                form.querySelector('[name="id"]').value = data.id;
                form.querySelector('[name="license_plate"]').value = data.license_plate;
                form.querySelector('[name="brand"]').value = data.brand;
                form.querySelector('[name="model"]').value = data.model;
                form.querySelector('[name="color"]').value = data.color;
                if (form.querySelector('[name="parking_id"]')) {
                    form.querySelector('[name="parking_id"]').value = data.parking_id || "";
                }
                
                new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
            });
    });
});

document.querySelectorAll('.btn-edit-booking').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        fetch(`api.php?action=get_booking&id=${id}`)
            .then(res => res.json())
            .then(data => {
                const form = document.getElementById('editBookingForm');
                form.querySelector('[name="id"]').value = data.id;
                form.querySelector('[name="parking_id"]').value = data.parking_id;
                form.querySelector('[name="vehicle_id"]').value = data.vehicle_id;
                form.querySelector('[name="start_time"]').value = data.start_time.replace(' ', 'T');
                form.querySelector('[name="end_time"]').value = data.end_time.replace(' ', 'T');
                form.querySelector('[name="status"]').value = data.status;
                
                new bootstrap.Modal(document.getElementById('editBookingModal')).show();
            });
    });
});

// Функція для підтвердження видалення зі SweetAlert2
function handleDelete(buttonClass, actionName, itemText) {
    document.querySelectorAll(buttonClass).forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            
            Swal.fire({
                title: 'Ви впевнені?',
                text: `Ви дійсно хочете видалити ${itemText}? Цю дію неможливо скасувати.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Так, видалити!',
                cancelButtonText: 'Скасувати'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`api.php?action=${actionName}&id=${id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Видалено!', '', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Помилка', data.error, 'error');
                        }
                    });
                }
            });
        });
    });
}

handleDelete('.btn-delete-parking', 'delete_parking', 'цей паркінг');
handleDelete('.btn-delete-vehicle', 'delete_vehicle', 'цей транспорт');
handleDelete('.btn-delete-booking', 'delete_booking', 'це бронювання');

// Обробка зміни статусу
function handleStatusChange(buttonClass, statusVal) {
    document.querySelectorAll(buttonClass).forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', statusVal);

            fetch('api.php?action=change_booking_status', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
            });
        });
    });
}

handleStatusChange('.btn-confirm-booking', 'active');
handleStatusChange('.btn-reject-booking', 'cancelled');


// === Динамічний розрахунок ціни та валідація дат ===
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const minDateTime = now.toISOString().slice(0,16); 
    
    const startInput = document.getElementById('bookingStartTime');
    const endInput = document.getElementById('bookingEndTime');
    
    if (startInput) startInput.min = minDateTime;
    if (endInput) endInput.min = minDateTime;

    if (startInput && endInput) {
        startInput.addEventListener('change', function() {
            endInput.min = this.value; 
            calculateEstimatedPrice();
        });
    }

    function calculateEstimatedPrice() {
        const parkingSelect = document.getElementById('bookingParkingId');
        const priceDisplay = document.getElementById('estimatedPrice');

        if (!parkingSelect || !startInput || !endInput || !priceDisplay) return;

        const selectedOption = parkingSelect.options[parkingSelect.selectedIndex];
        const pricePerHour = parseFloat(selectedOption.getAttribute('data-price')) || 0;

        const start = new Date(startInput.value);
        const end = new Date(endInput.value);

        if (start && end && !isNaN(start) && !isNaN(end) && end > start) {
            const diffMs = end - start;
            const diffHours = Math.ceil(diffMs / (1000 * 60 * 60));
            const total = diffHours * pricePerHour;
            
            priceDisplay.textContent = total.toFixed(2) + ' ₴';
            priceDisplay.classList.add('text-success');
        } else {
            priceDisplay.textContent = '0.00 ₴';
            priceDisplay.classList.remove('text-success');
        }
    }

    const bookingInputs = document.querySelectorAll('#bookingParkingId, #bookingStartTime, #bookingEndTime');
    bookingInputs.forEach(input => {
        input.addEventListener('input', calculateEstimatedPrice);
        input.addEventListener('change', calculateEstimatedPrice);
    });

    // === Ініціалізація графіка Chart.js для Адміна ===
    const ctx = document.getElementById('occupancyChart');
    if (ctx && window.chartLabels && window.chartData) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: window.chartLabels,
                datasets: [{
                    label: 'Зайнято місць',
                    data: window.chartData,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});