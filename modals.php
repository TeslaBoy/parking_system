<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 rounded-top-4 p-4">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-check"></i> Оформлення бронювання</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <form id="addBookingForm">
                    <input type="hidden" name="parking_id" id="bookingHiddenParkingId">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white rounded-3 border shadow-sm">
                        <div>
                            <div class="text-muted small text-uppercase fw-bold mb-1">Обрана локація</div>
                            <div class="fs-5 fw-bold text-dark" id="displayParkingName">Назва паркінгу</div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small text-uppercase fw-bold mb-1">Тариф</div>
                            <div class="fs-5 text-primary fw-bold"><span id="displayParkingPrice">0.00</span> <small>₴/год</small></div>
                        </div>
                    </div>

                    <div class="form-floating mb-3 shadow-sm">
                        <select class="form-select" name="vehicle_id" id="bookingVehicleId" required>
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
                        <label>Ваш транспортний засіб</label>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="form-floating shadow-sm">
                                <input type="datetime-local" class="form-control" name="start_time" id="bookingStartTime" step="1800" required>
                                <label>Час заїзду</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating shadow-sm">
                                <input type="datetime-local" class="form-control" name="end_time" id="bookingEndTime" step="1800" required>
                                <label>Час виїзду</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center p-3 bg-success bg-opacity-10 rounded-3 border border-success">
                        <span class="fw-bold text-success">Орієнтовна сума:</span>
                        <span id="estimatedPrice" class="fs-4 fw-bold text-success">0.00 ₴</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 p-4 pt-0">
                <button type="button" class="btn btn-secondary w-100 mb-2 py-2" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-success w-100 py-2 fw-bold shadow-sm" id="saveBookingBtn">Підтвердити бронювання</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0 rounded-top-4 p-4"><h5 class="modal-title fw-bold"><i class="bi bi-car-front-fill"></i> Додати транспорт</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <form id="addVehicleForm" enctype="multipart/form-data">
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control text-uppercase fw-bold" name="license_plate" placeholder="AE 1234 BT" required><label>Номерний знак</label></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="text" class="form-control" name="brand" placeholder="BMW" required><label>Марка</label></div></div>
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="text" class="form-control" name="model" placeholder="X5" required><label>Модель</label></div></div>
                    </div>
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="color" placeholder="Чорний" required><label>Колір</label></div>
                    <div class="mb-3 p-3 bg-white rounded-3 shadow-sm border"><label class="form-label small text-muted fw-bold mb-1">Фото авто (опціонально)</label><input type="file" class="form-control form-control-sm" name="image" accept="image/*"></div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 p-4 pt-0"><button type="button" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" id="saveVehicleBtn">Зберегти авто</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning border-0 rounded-top-4 p-4"><h5 class="modal-title fw-bold"><i class="bi bi-pencil-square"></i> Редагувати транспорт</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <form id="editVehicleForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editVehicleId">
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control text-uppercase fw-bold" name="license_plate" id="editVehicleLicensePlate" required><label>Номерний знак</label></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="text" class="form-control" name="brand" id="editVehicleBrand" required><label>Марка</label></div></div>
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="text" class="form-control" name="model" id="editVehicleModel" required><label>Модель</label></div></div>
                    </div>
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="color" id="editVehicleColor" required><label>Колір</label></div>
                    <div class="mb-3 p-3 bg-white rounded-3 shadow-sm border"><label class="form-label small text-muted fw-bold mb-1">Оновити фото</label><input type="file" class="form-control form-control-sm" name="image" accept="image/*"></div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light rounded-bottom-4 p-4 pt-0"><button type="button" class="btn btn-warning w-100 py-2 fw-bold shadow-sm" id="updateVehicleBtn">Оновити дані</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="addParkingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white p-4 rounded-top-4"><h5 class="modal-title text-white fw-bold">Додати паркінг</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <form id="addParkingForm" enctype="multipart/form-data">
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="name" required><label>Назва паркінгу</label></div>
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="address" required><label>Адреса</label></div>
                    <div class="form-floating mb-3 shadow-sm"><input type="number" step="0.01" class="form-control" name="price_per_hour" value="0.00" required><label>Тариф (грн/год)</label></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="number" class="form-control" name="capacity" required><label>Загальна місткість</label></div></div>
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="number" class="form-control" name="available" required><label>Доступно місць</label></div></div>
                    </div>
                    <div class="mb-3 p-3 bg-white rounded-3 shadow-sm border">
                        <label class="form-label small text-muted fw-bold mb-1">Зображення паркінгу (опціонально)</label>
                        <input type="file" class="form-control form-control-sm" name="image" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 bg-light rounded-bottom-4"><button type="button" class="btn btn-dark w-100 py-2 fw-bold" id="saveParkingBtn">Зберегти паркінг</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editParkingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning p-4 rounded-top-4"><h5 class="modal-title fw-bold">Редагувати паркінг</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <form id="editParkingForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editParkingId">
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="name" id="editParkingName" required><label>Назва паркінгу</label></div>
                    <div class="form-floating mb-3 shadow-sm"><input type="text" class="form-control" name="address" id="editParkingAddress" required><label>Адреса</label></div>
                    <div class="form-floating mb-3 shadow-sm"><input type="number" step="0.01" class="form-control" name="price_per_hour" id="editParkingPrice" required><label>Тариф (грн/год)</label></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="number" class="form-control" name="capacity" id="editParkingCapacity" required><label>Загальна місткість</label></div></div>
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="number" class="form-control" name="available" id="editParkingAvailable" required><label>Доступно місць</label></div></div>
                    </div>
                    <div class="mb-3 p-3 bg-white rounded-3 shadow-sm border">
                        <label class="form-label small text-muted fw-bold mb-1">Оновити зображення</label>
                        <input type="file" class="form-control form-control-sm" name="image" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 bg-light rounded-bottom-4"><button type="button" class="btn btn-warning w-100 py-2 fw-bold" id="updateParkingBtn">Оновити дані</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning p-4 rounded-top-4"><h5 class="modal-title fw-bold">Редагувати бронювання</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <form id="editBookingForm">
                    <input type="hidden" name="id" id="editBookingId">
                    <div class="form-floating mb-3 shadow-sm">
                        <select class="form-select" name="parking_id" id="editBookingParkingId" required>
                            <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <label>Паркінг</label>
                    </div>
                    <div class="form-floating mb-3 shadow-sm">
                        <input type="hidden" name="vehicle_id" id="editBookingVehicleId">
                        <input type="text" class="form-control fw-bold" id="editBookingVehiclePlate" readonly>
                        <label>Номер авто</label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="datetime-local" class="form-control" name="start_time" id="editBookingStartTime" step="1800" required><label>Початок</label></div></div>
                        <div class="col-6"><div class="form-floating shadow-sm"><input type="datetime-local" class="form-control" name="end_time" id="editBookingEndTime" step="1800" required><label>Кінець</label></div></div>
                    </div>
                    <div class="form-floating mb-3 shadow-sm">
                        <select class="form-select fw-bold" name="status" id="editBookingStatus">
                            <option value="active">Активне</option>
                            <option value="completed">Завершене</option>
                            <option value="cancelled">Скасоване</option>
                        </select>
                        <label>Статус</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 bg-light rounded-bottom-4"><button type="button" class="btn btn-warning w-100 py-2 fw-bold" id="updateBookingBtn">Оновити бронювання</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1"></div>
<div class="modal fade" id="loginModal" tabindex="-1"></div>