<div class="modal fade" id="addParkingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Додати паркінг</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addParkingForm">
                    <div class="mb-3">
                        <label class="form-label">Назва</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Адреса</label>
                        <input type="text" class="form-control" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тариф (грн/год)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_hour" value="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Місткість</label>
                        <input type="number" class="form-control" name="capacity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Доступно місць</label>
                        <input type="number" class="form-control" name="available" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Зображення</label>
                        <input type="file" class="form-control" name="image">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveParkingBtn">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editParkingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагувати паркінг</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editParkingForm">
                    <input type="hidden" name="id" id="editParkingId">
                    <div class="mb-3">
                        <label class="form-label">Назва</label>
                        <input type="text" class="form-control" name="name" id="editParkingName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Адреса</label>
                        <input type="text" class="form-control" name="address" id="editParkingAddress" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тариф (грн/год)</label>
                        <input type="number" step="0.01" class="form-control" name="price_per_hour" id="editParkingPrice" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Місткість</label>
                        <input type="number" class="form-control" name="capacity" id="editParkingCapacity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Доступно місць</label>
                        <input type="number" class="form-control" name="available" id="editParkingAvailable" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Зображення</label>
                        <input type="file" class="form-control" name="image">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="updateParkingBtn">Оновити</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Додати транспорт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addVehicleForm">
                    <div class="mb-3">
                        <label class="form-label">Номерний знак</label>
                        <input type="text" class="form-control" name="license_plate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Марка</label>
                        <input type="text" class="form-control" name="brand" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Модель</label>
                        <input type="text" class="form-control" name="model" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Колір</label>
                        <input type="text" class="form-control" name="color" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Паркінг</label>
                        <select class="form-select" name="parking_id">
                            <option value="">Не призначено</option>
                            <?php 
                            $parkings_result->data_seek(0);
                            while($row = $parkings_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Зображення</label>
                        <input type="file" class="form-control" name="image">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveVehicleBtn">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагувати транспорт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editVehicleForm">
                    <input type="hidden" name="id" id="editVehicleId">
                    <div class="mb-3">
                        <label class="form-label">Номерний знак</label>
                        <input type="text" class="form-control" name="license_plate" id="editVehicleLicensePlate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Марка</label>
                        <input type="text" class="form-control" name="brand" id="editVehicleBrand" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Модель</label>
                        <input type="text" class="form-control" name="model" id="editVehicleModel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Колір</label>
                        <input type="text" class="form-control" name="color" id="editVehicleColor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Паркінг</label>
                        <select class="form-select" name="parking_id" id="editVehicleParkingId">
                            <option value="">Не призначено</option>
                            <?php 
                            $parkings_result->data_seek(0);
                            while($row = $parkings_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Зображення</label>
                        <input type="file" class="form-control" name="image">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="updateVehicleBtn">Оновити</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Додати бронювання</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addBookingForm">
                    <div class="mb-3">
                        <label class="form-label">Паркінг</label>
                        <select class="form-select" name="parking_id" id="bookingParkingId" required>
                            <option value="" data-price="0">Оберіть паркінг</option>
                            <?php 
                            $parkings_result->data_seek(0);
                            while($row = $parkings_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" data-price="<?php echo $row['price_per_hour']; ?>">
                                    <?php echo htmlspecialchars($row['name']); ?> (<?php echo number_format($row['price_per_hour'], 2); ?> ₴/год)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Транспорт</label>
                        <select class="form-select" name="vehicle_id" id="bookingVehicleId" required>
                            <option value="">Оберіть транспорт</option>
                            <?php 
                            if(isset($vehicles_result)) {
                                $vehicles_result->data_seek(0);
                                while($row = $vehicles_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model'] . ' (' . $row['license_plate'] . ')'); ?></option>
                                <?php endwhile; 
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Початок</label>
                        <input type="datetime-local" class="form-control" name="start_time" id="bookingStartTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кінець</label>
                        <input type="datetime-local" class="form-control" name="end_time" id="bookingEndTime" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Орієнтовна вартість</label>
                        <div class="form-control bg-light d-flex justify-content-between" readonly>
                            <span class="text-muted">Сума до сплати:</span>
                            <span id="estimatedPrice" class="fw-bold">0.00 ₴</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveBookingBtn">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагувати бронювання</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBookingForm">
                    <input type="hidden" name="id" id="editBookingId">
                    <div class="mb-3">
                        <label class="form-label">Паркінг</label>
                        <select class="form-select" name="parking_id" id="editBookingParkingId" required>
                            <option value="">Оберіть паркінг</option>
                            <?php 
                            $parkings_result->data_seek(0);
                            while($row = $parkings_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Транспорт</label>
                        <select class="form-select" name="vehicle_id" id="editBookingVehicleId" required>
                            <option value="">Оберіть транспорт</option>
                            <?php 
                            if(isset($vehicles_result)) {
                                $vehicles_result->data_seek(0);
                                while($row = $vehicles_result->fetch_assoc()): ?>
                                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model'] . ' (' . $row['license_plate'] . ')'); ?></option>
                                <?php endwhile;
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Початок</label>
                        <input type="datetime-local" class="form-control" name="start_time" id="editBookingStartTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Кінець</label>
                        <input type="datetime-local" class="form-control" name="end_time" id="editBookingEndTime" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Статус</label>
                        <select class="form-select" name="status" id="editBookingStatus">
                            <option value="active">Активне</option>
                            <option value="completed">Завершене</option>
                            <option value="cancelled">Скасоване</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="updateBookingBtn">Оновити</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Реєстрація</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm">
                    <div class="mb-3">
                        <label class="form-label">Ім'я користувача</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="registerBtn">Зареєструватися</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Авторизація</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Ім'я користувача</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="loginBtn">Увійти</button>
            </div>
        </div>
    </div>
</div>