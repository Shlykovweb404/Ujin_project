<?php
session_start();
require_once 'db.php';

// Проверка подключения к БД
if ($db->connect_error) {
    die("Ошибка подключения к БД: " . $db->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['title'])) {
    // Генерация номера заявки
    $request_number = 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Получение данных из формы
    $title = trim($_POST['title']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $service_date = $_POST['date'];
    $service_time = $_POST['time'];
    $service_type = $_POST['service'];
    $service_desc = isset($_POST['serviceDesc']) ? trim($_POST['serviceDesc']) : '';
    $payment_method = $_POST['payment'];
    
    // Проверка обязательных полей
    if (empty($title) || empty($address) || empty($phone) || empty($service_date) || empty($service_time) || empty($service_type) || empty($payment_method)) {
        $error = "Все обязательные поля должны быть заполнены!";
    } else {
        try {
            // Используем подготовленные выражения для безопасности
            $stmt = $db->prepare("INSERT INTO requests 
                                (user_id, request_number, title, address, phone, service_date, service_time, service_type, service_description, payment_method) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("isssssssss", 
                $user_id, 
                $request_number, 
                $title, 
                $address, 
                $phone, 
                $service_date, 
                $service_time, 
                $service_type, 
                $service_desc, 
                $payment_method);
            
            if ($stmt->execute()) {
                header("Location: my_applications.php");
                exit();
            } else {
                $error = "Ошибка при создании заявки: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новая заявка</title>
    <link rel="icon" type="" href="img/apple-touch-icon.png" sizes="96x96" />
    <style>
        /* Общие стили */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        /* Контейнер формы */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        /* Шапка формы */
        .form-header {
            background: linear-gradient(135deg, #3a7bc8, #1a4b8c);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .form-header .logo img {
            height: 50px;
            width: auto;
        }
        
        .form-header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        /* Сообщение об ошибке */
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            border-left: 4px solid #c62828;
        }
        
        /* Форма */
        form {
            padding: 0 30px 30px;
        }
        
        .form-group {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .form-group:last-child {
            border-bottom: none;
        }
        
        .form-group h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1a4b8c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group h2::before {
            content: "";
            display: block;
            width: 6px;
            height: 20px;
            background-color: #3a7bc8;
            border-radius: 3px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="date"],
        input[type="time"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="date"]:focus,
        input[type="time"]:focus,
        textarea:focus,
        select:focus {
            border-color: #3a7bc8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(58, 123, 200, 0.2);
        }
        
        /* Радио кнопки */
        .radio-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .radio-option {
            flex: 1 1 calc(50% - 15px);
            min-width: 200px;
        }
        
        .radio-option input[type="radio"] {
            display: none;
        }
        
        .radio-option label {
            display: block;
            padding: 15px;
            background-color: #f5f7fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .radio-option input[type="radio"]:checked + label {
            background-color: #e3f2fd;
            border-color: #3a7bc8;
            color: #1a4b8c;
            font-weight: 600;
        }
        
        /* Поле для описания услуги */
        .hidden-field {
            display: none;
            margin-top: 15px;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Кнопки */
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            flex: 1;
        }
        
        .btn-primary {
            background-color: #3a7bc8;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2c5fa8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #f5f7fa;
            color: #555;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        /* Футер */
        .footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 14px;
            border-top: 1px solid #eee;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .form-header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .form-header .logo img {
                height: 40px;
            }
            
            form {
                padding: 0 20px 20px;
            }
            
            .radio-option {
                flex: 1 1 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .form-container {
                border-radius: 8px;
            }
            
            .form-group h2 {
                font-size: 16px;
            }
            
            input, textarea, select {
                padding: 10px 12px;
            }
            
            .radio-option label {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <div class="logo">
                <img src="img/logo.svg" alt="Логотип">
            </div>
            <h1>Новая заявка</h1>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <h2>Контактные данные</h2>
                <label for="title">Название заявки</label>
                <input type="text" id="title" name="title" required placeholder="Краткое описание заявки" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                
                <label for="address">Адрес</label>
                <input type="text" id="address" name="address" required placeholder="Укажите ваш адрес" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" required placeholder="+7(XXX)XXX-XX-XX" pattern="\+7\(\d{3}\)\d{3}-\d{2}-\d{2}" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <h2>Дата и время</h2>
                <label for="date">Дата выполнения</label>
                <input type="date" id="date" name="date" required value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
                
                <label for="time">Время</label>
                <input type="time" id="time" name="time" required value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <h2>Тип услуги</h2>
                <div class="radio-options">
                    <div class="radio-option">
                        <input type="radio" id="cleaning" name="service" value="cleaning" required <?php echo (isset($_POST['service']) && $_POST['service'] == 'cleaning') ? 'checked' : ''; ?>>
                        <label for="cleaning">Климат контроль</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="repair" name="service" value="repair" <?php echo (isset($_POST['service']) && $_POST['service'] == 'repair') ? 'checked' : ''; ?>>
                        <label for="repair">Умное освещение</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="delivery" name="service" value="delivery" <?php echo (isset($_POST['service']) && $_POST['service'] == 'delivery') ? 'checked' : ''; ?>>
                        <label for="delivery">Полная установка умных домов</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="delivery" name="service" value="delivery" <?php echo (isset($_POST['service']) && $_POST['service'] == 'delivery') ? 'checked' : ''; ?>>
                        <label for="delivery_1">Установка датчиков</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="delivery" name="service" value="delivery" <?php echo (isset($_POST['service']) && $_POST['service'] == 'delivery') ? 'checked' : ''; ?>>
                        <label for="delivery_2">Установка сигнализации</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="delivery" name="service" value="delivery" <?php echo (isset($_POST['service']) && $_POST['service'] == 'delivery') ? 'checked' : ''; ?>>
                        <label for="delivery_3">Установка видеонаблюдения</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="other" name="service" value="other" <?php echo (isset($_POST['service']) && $_POST['service'] == 'other') ? 'checked' : ''; ?>>
                        <label for="other">Другая услуга</label>
                    </div>
                </div>
                
                <div id="otherServiceField" class="hidden-field" style="<?php echo (isset($_POST['service']) && $_POST['service'] == 'other') ? 'display: block;' : 'display: none;'; ?>">
                    <label for="serviceDesc">Опишите услугу</label>
                    <textarea id="serviceDesc" name="serviceDesc" rows="2" placeholder="Подробно опишите необходимую услугу"><?php echo isset($_POST['serviceDesc']) ? htmlspecialchars($_POST['serviceDesc']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <h2>Способ оплаты</h2>
                <div class="radio-options">
                    <div class="radio-option">
                        <input type="radio" id="cash" name="payment" value="cash" required <?php echo (isset($_POST['payment']) && $_POST['payment'] == 'cash') ? 'checked' : ''; ?>>
                        <label for="cash">Наличные</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="card" name="payment" value="card" <?php echo (isset($_POST['payment']) && $_POST['payment'] == 'card') ? 'checked' : ''; ?>>
                        <label for="card">Банковская карта</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Отправить</button>
                <a href="my_applications.php" class="btn btn-secondary">Назад</a>
            </div>
        </form>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Сервис заявок</p>
        </div>
    </div>

    <script>
        document.getElementById('other').addEventListener('change', function() {
            const field = document.getElementById('otherServiceField');
            field.style.display = this.checked ? 'block' : 'none';
            if (this.checked) {
                document.getElementById('serviceDesc').setAttribute('required', '');
            } else {
                document.getElementById('serviceDesc').removeAttribute('required');
            }
        });
        
        // Проверяем при загрузке страницы, нужно ли показать поле для описания
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('other').checked) {
                document.getElementById('otherServiceField').style.display = 'block';
                document.getElementById('serviceDesc').setAttribute('required', '');
            }
        });
    </script>
</body>
</html>