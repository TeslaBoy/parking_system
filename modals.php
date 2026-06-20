<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-header border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2"></i>Оформлення бронювання</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="addBookingForm">
                    <input type="hidden" name="parking_id" id="bookingHiddenParkingId">
                    
                    <!-- Selected Location Info -->
                    <div class="selected-info-card mb-4">
                        <div class="row align-items-center">
                            <div class="col-sm-6 mb-3 mb-sm-0">
                                <label class="info-label text-muted small text-uppercase fw-bold mb-1">Обрана локація</label>
                                <div class="info-value fw-bold" id="displayParkingName">Назва паркінгу</div>
                            </div>
                            <div class="col-sm-6 text-sm-end">
                                <label class="info-label text-muted small text-uppercase fw-bold mb-1">Тариф</label>
                                <div class="info-value text-primary fw-bold"><span id="displayParkingPrice">0.00</span> <small>₴/год</small></div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Selection -->
                    <div class="form-section mb-4">
                        <label class="form-section-label">Оберіть транспорт</label>
                        <div class="form-floating-custom">
                            <select class="form-select form-select-lg" name="vehicle_id" id="bookingVehicleId" required>
                                <option value="" disabled selected>Оберіть авто зі списку...</option>
                                <?php 
                                if(isset($vehicles_result)) {
                                    $vehicles_result->data_seek(0);
                                    while($row = $vehicles_result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['license_plate'] . ' — ' . $row['brand'] . ' ' . $row['model']); ?>
                                        </option>
                                    <?php endwhile; 
                                } ?>
                            </select>
                            <i class="bi bi-car-front form-floating-icon"></i>
                        </div>
                    </div>

                    <!-- Time Period -->
                    <div class="form-section mb-4">
                        <label class="form-section-label">Період паркування</label>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="form-floating-custom">
                                    <input type="datetime-local" class="form-control form-control-lg" name="start_time" id="bookingStartTime" required>
                                    <i class="bi bi-calendar-event form-floating-icon"></i>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-floating-custom">
                                    <input type="datetime-local" class="form-control form-control-lg" name="end_time" id="bookingEndTime" required>
                                    <i class="bi bi-calendar-event-check form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price Estimation -->
                    <div class="price-estimation-card mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="estimation-label fw-bold">Орієнтовна сума</span>
                            <span id="estimatedPrice" class="estimation-value">0.00 ₴</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-confirm" id="saveBookingBtn">
                    <i class="bi bi-check-circle me-2"></i>Підтвердити бронювання
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-primary border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-car-front-fill me-2"></i>Додати транспорт</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="addVehicleForm">
                    <div class="form-section mb-3">
                        <label class="form-section-label">Номерний знак</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg text-uppercase fw-bold text-center" 
                                   name="license_plate" placeholder="AE 1234 BT" required maxlength="15" style="letter-spacing: 2px;">
                            <i class="bi bi-upc-scan form-floating-icon"></i>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-lg" name="brand" placeholder="BMW" required>
                                    <i class="bi bi-car-front form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-lg" name="model" placeholder="X5" required>
                                    <i class="bi bi-wrench form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section mb-4">
                        <label class="form-section-label">Колір</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg" name="color" placeholder="Чорний">
                            <i class="bi bi-palette form-floating-icon"></i>
                        </div>
                    </div>

                    <div class="form-section mb-3">
                        <label class="form-section-label">Фото (опціонально)</label>
                        <div class="upload-area">
                            <input type="file" class="form-control" name="image" accept="image/*" id="vehicleImageInput">
                            <label for="vehicleImageInput" class="upload-label">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <span>Натисніть для вибору фото</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-confirm" id="saveVehicleBtn">
                    <i class="bi bi-check-lg me-2"></i>Додати транспорт
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-warning border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Редагувати транспорт</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="editVehicleForm">
                    <input type="hidden" name="id" id="editVehicleId">
                    <div class="form-section mb-3">
                        <label class="form-section-label">Номерний знак</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg text-uppercase fw-bold text-center" 
                                   name="license_plate" id="editVehicleLicensePlate" required maxlength="15" style="letter-spacing: 2px;">
                            <i class="bi bi-upc-scan form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-lg" name="brand" id="editVehicleBrand" required>
                                    <i class="bi bi-car-front form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="text" class="form-control form-control-lg" name="model" id="editVehicleModel" required>
                                    <i class="bi bi-wrench form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section mb-4">
                        <label class="form-section-label">Колір</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg" name="color" id="editVehicleColor" required>
                            <i class="bi bi-palette form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="form-section mb-3">
                        <label class="form-section-label">Оновити фото</label>
                        <div class="upload-area">
                            <input type="file" class="form-control" name="image" accept="image/*" id="editVehicleImageInput">
                            <label for="editVehicleImageInput" class="upload-label">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <span>Натисніть для вибору нового фото</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-update" id="updateVehicleBtn">
                    <i class="bi bi-check-lg me-2"></i>Зберегти зміни
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Parking Modal -->
<div class="modal fade" id="addParkingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-dark border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-building me-2"></i>Додати паркінг</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="addParkingForm">
                    <div class="form-section mb-3">
                        <label class="form-section-label">Назва паркінгу</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg fw-semibold" name="name" required placeholder="Центральний паркінг">
                            <i class="bi bi-signpost form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="form-section mb-3">
                        <label class="form-section-label">Адреса</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg" name="address" required placeholder="вул. Центральна, 1">
                            <i class="bi bi-geo-alt form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" step="0.01" class="form-control form-control-lg" 
                                           name="price_per_hour" value="0.00" required placeholder="0.00">
                                    <i class="bi bi-currency-dollar form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" class="form-control form-control-lg" name="capacity" required min="1" placeholder="50">
                                    <i class="bi bi-p-square form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" class="form-control form-control-lg" name="available" required min="0" placeholder="50">
                                    <i class="bi bi-check-circle form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label class="form-section-label">Фото паркінгу (опціонально)</label>
                        <div class="upload-area">
                            <input type="file" class="form-control" name="image" accept="image/*" id="parkingImageInput">
                            <label for="parkingImageInput" class="upload-label">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <span>Натисніть для вибору зображення</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-confirm" id="saveParkingBtn">
                    <i class="bi bi-plus-lg me-2"></i>Додати паркінг
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Parking Modal -->
<div class="modal fade" id="editParkingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-warning border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-building me-2"></i>Редагувати паркінг</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="editParkingForm">
                    <input type="hidden" name="id" id="editParkingId">
                    <div class="form-section mb-3">
                        <label class="form-section-label">Назва паркінгу</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg fw-semibold" name="name" id="editParkingName" required>
                            <i class="bi bi-signpost form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="form-section mb-3">
                        <label class="form-section-label">Адреса</label>
                        <div class="form-floating-custom">
                            <input type="text" class="form-control form-control-lg" name="address" id="editParkingAddress" required>
                            <i class="bi bi-geo-alt form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" step="0.01" class="form-control form-control-lg" 
                                           name="price_per_hour" id="editParkingPrice" required>
                                    <i class="bi bi-currency-dollar form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" class="form-control form-control-lg" name="capacity" id="editParkingCapacity" required>
                                    <i class="bi bi-p-square form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="number" class="form-control form-control-lg" name="available" id="editParkingAvailable" required>
                                    <i class="bi bi-check-circle form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label class="form-section-label">Оновити зображення</label>
                        <div class="upload-area">
                            <input type="file" class="form-control" name="image" accept="image/*" id="editParkingImageInput">
                            <label for="editParkingImageInput" class="upload-label">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <span>Натисніть для вибору нового фото</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-update" id="updateParkingBtn">
                    <i class="bi bi-check-lg me-2"></i>Зберегти зміни
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <div class="modal-header gradient-warning border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-event me-2"></i>Редагувати бронювання</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <form id="editBookingForm">
                    <input type="hidden" name="id" id="editBookingId">
                    <div class="form-section mb-3">
                        <div class="form-floating-custom">
                            <select class="form-select form-select-lg" name="parking_id" id="editBookingParkingId" required>
                                <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                            <i class="bi bi-building form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="form-section mb-4">
                        <div class="form-floating-custom">
                            <select class="form-select form-select-lg" name="vehicle_id" id="editBookingVehicleId" required>
                                <?php if(isset($vehicles_result)) { $vehicles_result->data_seek(0); while($row = $vehicles_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['license_plate']); ?></option>
                                <?php endwhile; } ?>
                            </select>
                            <i class="bi bi-car-front form-floating-icon"></i>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="datetime-local" class="form-control form-control-lg" name="start_time" id="editBookingStartTime" required>
                                    <i class="bi bi-calendar-event form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-section">
                                <div class="form-floating-custom">
                                    <input type="datetime-local" class="form-control form-control-lg" name="end_time" id="editBookingEndTime" required>
                                    <i class="bi bi-calendar-event-check form-floating-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label class="form-section-label">Статус бронювання</label>
                        <div class="form-floating-custom">
                            <select class="form-select form-select-lg" name="status" id="editBookingStatus">
                                <option value="pending">Очікує</option>
                                <option value="active">Активне</option>
                                <option value="completed">Завершено</option>
                                <option value="cancelled">Скасовано</option>
                            </select>
                            <i class="bi bi-check2-circle form-floating-icon"></i>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-lg btn-update" id="updateBookingBtn">
                    <i class="bi bi-check-lg me-2"></i>Оновити інформацію
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Empty modals for login/register (kept minimal) -->
<div class="modal fade" id="registerModal" tabindex="-1"></div>
<div class="modal fade" id="loginModal" tabindex="-1"></div>