<?php
session_start();
require_once 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Проверка соединения с БД
if ($db->connect_error) {
    die("Ошибка подключения к базе данных: " . $db->connect_error);
}

$user_id = $_SESSION['user_id'];
$user_name = '';
$requests = [];

// 1. Сначала узнаем структуру таблицы users
$columns_query = "SHOW COLUMNS FROM users";
$columns_result = $db->query($columns_query);
$user_columns = [];
while ($row = $columns_result->fetch_assoc()) {
    $user_columns[] = $row['Field'];
}

// 2. Определяем, какие поля доступны для имени пользователя
$username_fields = [];
if (in_array('fullname', $user_columns)) $username_fields[] = 'fullname';
if (in_array('username', $user_columns)) $username_fields[] = 'username';
if (in_array('login', $user_columns)) $username_fields[] = 'login';
if (in_array('name', $user_columns)) $username_fields[] = 'name';

// 3. Получаем имя пользователя (используем первый доступный вариант)
if (!empty($username_fields)) {
    $username_field = $username_fields[0];
    $user_query = "SELECT $username_field FROM users WHERE id = ?";
    $stmt = $db->prepare($user_query);
    
    if ($stmt === false) {
        die("Ошибка подготовки запроса пользователя: " . $db->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        die("Ошибка выполнения запроса пользователя: " . $stmt->error);
    }
    
    $user_result = $stmt->get_result();
    if ($user_data = $user_result->fetch_assoc()) {
        $user_name = $user_data[$username_field];
    }
    $stmt->close();
} else {
    $user_name = 'Пользователь #' . $user_id;
}

// 4. Получаем заявки пользователя
$query = "SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($query);

if ($stmt === false) {
    die("Ошибка подготовки запроса заявок: " . $db->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Ошибка выполнения запроса заявок: " . $stmt->error);
}

$result = $stmt->get_result();
while ($request = $result->fetch_assoc()) {
    $requests[] = $request;
}
$stmt->close();

// Функции для форматирования данных
function getStatusText($status) {
    switch ($status) {
        case 'new': return 'Новая';
        case 'in_progress': return 'В обработке';
        case 'completed': return 'Завершена';
        case 'rejected': return 'Отклонена';
        default: return $status;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'new': return 'new';
        case 'in_progress': return 'in-progress';
        case 'completed': return 'completed';
        case 'rejected': return 'rejected';
        default: return '';
    }
}

function formatServiceType($type) {
    $types = [
        'cleaning' => 'Уборка помещения',
        'repair' => 'Ремонтные работы',
        'delivery' => 'Доставка',
        'consultation' => 'Консультация'
    ];
    return $types[$type] ?? $type;
}

function formatPaymentMethod($method) {
    $methods = [
        'cash' => 'Наличные',
        'card' => 'Банковская карта',
        'online' => 'Онлайн-оплата'
    ];
    return $methods[$method] ?? $method;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заявки</title>
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3a7bc8;
            --primary-dark: #1a4b8c;
            --danger: #c62828;
            --success: #2e7d32;
            --warning: #ff8f00;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --border: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .logo {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .user-panel {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--dark);
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .btn-logout {
            background-color: #ffebee;
            color: var(--danger);
            border-color: #ffcdd2;
        }
        
        .btn-logout:hover {
            background-color: #ffcdd2;
        }
        
        .section-title {
            font-size: 22px;
            color: var(--primary-dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3f2fd;
        }
        
        .requests-list {
            margin-bottom: 30px;
        }
        
        .request-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .request-card:hover {
            border-color: var(--primary);
            box-shadow: 0 3px 10px rgba(58, 123, 200, 0.1);
        }
        
        .request-card.active {
            border-color: var(--primary);
            background-color: #f8fafc;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .request-number {
            font-weight: 600;
            color: var(--primary-dark);
            font-size: 16px;
        }
        
        .request-date {
            color: var(--gray);
            font-size: 14px;
        }
        
        .request-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .status-new {
            background-color: #e3f2fd;
            color: var(--primary-dark);
        }
        
        .status-in-progress {
            background-color: #fff8e1;
            color: var(--warning);
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: var(--success);
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: var(--danger);
        }
        
        .request-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .request-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border);
            display: none;
        }
        
        .request-card.active .request-details {
            display: block;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--gray);
            min-width: 150px;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .rejection-reason {
            color: var(--danger);
            font-style: italic;
            background-color: #ffebee;
            padding: 8px;
            border-radius: 4px;
            margin-top: 5px;
            display: inline-block;
        }
        
        .toggle-details {
            color: var(--primary);
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .request-card.active .toggle-details {
            color: var(--primary-dark);
        }
        
        .new-request-btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
        }
        
        .new-request-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 123, 200, 0.2);
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-4 {
            margin-top: 30px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            color: var(--gray);
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .request-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                margin-bottom: 5px;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 8px;
                padding: 15px;
            }
            
            .new-request-btn {
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Система заявок</div>
            <div class="user-panel">
                <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                <a href="index.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </div>
        
        <h2 class="section-title">Мои заявки</h2>
        
        <div class="requests-list">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request): ?>
                    <div class="request-card" onclick="toggleDetails(this)">
                        <div class="request-header">
                            <span class="request-number">№<?= htmlspecialchars($request['request_number']) ?></span>
                            <span class="request-date"><?= date('d.m.Y', strtotime($request['created_at'])) ?></span>
                            <span class="request-status status-<?= getStatusClass($request['status']) ?>">
                                <?= getStatusText($request['status']) ?>
                            </span>
                        </div>
                        
                        <div class="request-title"><?= htmlspecialchars($request['title']) ?></div>
                        
                        <div class="request-details">
                            <div class="detail-row">
                                <span class="detail-label">Адрес:</span>
                                <span class="detail-value"><?= htmlspecialchars($request['address']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Телефон:</span>
                                <span class="detail-value"><?= htmlspecialchars($request['phone']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Дата выполнения:</span>
                                <span class="detail-value">
                                    <?= date('d.m.Y', strtotime($request['service_date'])) ?> в <?= $request['service_time'] ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Тип услуги:</span>
                                <span class="detail-value"><?= formatServiceType($request['service_type']) ?></span>
                            </div>
                            <?php if (!empty($request['service_description'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Описание:</span>
                                <span class="detail-value"><?= htmlspecialchars($request['service_description']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="detail-label">Способ оплаты:</span>
                                <span class="detail-value"><?= formatPaymentMethod($request['payment_method']) ?></span>
                            </div>
                            
                            <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Причина отклонения:</span>
                                <span class="detail-value">
                                    <div class="rejection-reason">
                                        <?= htmlspecialchars($request['rejection_reason']) ?>
                                    </div>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="toggle-details">Показать подробности ▼</div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">У вас пока нет заявок</p>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="forming_an_application.php" class="new-request-btn">
                <i class="fas fa-plus"></i> Создать новую заявку
            </a>
        </div>
        
        <div class="footer">
            <p>© <?= date('Y') ?> Система заявок. Все права защищены.</p>
        </div>
    </div>

    <script>
        function toggleDetails(card) {
            card.classList.toggle('active');
            const toggleText = card.querySelector('.toggle-details');
            toggleText.textContent = card.classList.contains('active') 
                ? 'Скрыть подробности ▲' 
                : 'Показать подробности ▼';
        }
    </script>
</body>
</html>