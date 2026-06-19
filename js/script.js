// Допоміжна функція для безпечного додавання обробників подій
function addSafeListener(id, event, callback) {
    const el = document.getElementById(id);
    if (el) el.addEventListener(event, callback);
}

// Універсальна функція для відправки AJAX форм
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
            if (data.success) location.reload();
            else alert('Помилка: ' + (data.error || 'Невідома помилка'));
        })
        .catch(err => console.error('Помилка мережі:', err));
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
                
                const modal = new bootstrap.Modal(document.getElementById('editParkingModal'));
                modal.show();
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
                
                const modal = new bootstrap.Modal(document.getElementById('editVehicleModal'));
                modal.show();
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
                
                const modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
                modal.show();
            });
    });
});

// Обробка видалення
document.querySelectorAll('.btn-delete-parking').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Ви впевнені, що хочете видалити цей паркінг?')) {
            const id = this.getAttribute('data-id');
            fetch(`api.php?action=delete_parking&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Помилка: ' + data.error);
                }
            });
        }
    });
});

// Обробка зміни статусу (Підтвердження/Відхилення)
document.querySelectorAll('.btn-confirm-booking').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', 'active');

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

document.querySelectorAll('.btn-reject-booking').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', 'cancelled');

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

document.querySelectorAll('.btn-delete-vehicle').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Ви впевнені, що хочете видалити цей транспорт?')) {
            const id = this.getAttribute('data-id');
            fetch(`api.php?action=delete_vehicle&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Помилка: ' + data.error);
                }
            });
        }
    });
});

document.querySelectorAll('.btn-delete-booking').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Ви впевнені, що хочете видалити це бронювання?')) {
            const id = this.getAttribute('data-id');
            fetch(`api.php?action=delete_booking&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Помилка: ' + data.error);
                }
            });
        }
    });
});