<?php
// classes/Uploader.php

class Uploader {
    // Дозволені типи файлів (лише зображення)
    private $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    // Максимальний розмір (наприклад, 5 МБ)
    private $maxFileSize = 5242880; 
    private $uploadDir;

    public function __construct($uploadDir = 'images/') {
        $this->uploadDir = $uploadDir;
        // Створюємо папку, якщо її немає
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Обробляє завантаження файлу
     * Повертає шлях до файлу або викидає виняток (Exception)
     */
    public function uploadImage($fileInputName) {
        if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return ''; // Файл не завантажували, це нормально (необов'язкове поле)
        }

        $file = $_FILES[$fileInputName];

        // 1. Перевірка на помилки передачі
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Помилка під час завантаження файлу.");
        }

        // 2. Перевірка розміру
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("Файл занадто великий. Максимум 5 МБ.");
        }

        // 3. Справжня перевірка типу файлу (MIME-type), а не просто розширення
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception("Недопустимий формат файлу. Дозволені лише JPG, PNG та WEBP.");
        }

        // 4. Генерація безпечного унікального імені файлу
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFileName = uniqid('img_', true) . '.' . $extension;
        $destination = $this->uploadDir . $safeFileName;

        // 5. Збереження файлу
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Не вдалося зберегти файл на сервері.");
        }

        return $destination;
    }
}
?>