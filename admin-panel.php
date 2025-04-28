<?php
session_start();
require_once 'db.php';

// Проверка авторизации администратора
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['new_status'];
    $rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;
    
    if ($newStatus === 'rejected' && empty($rejectionReason)) {
        $_SESSION['error_message'] = 'При отклонении заявки необходимо указать причину';
        header("Location: admin-panel.php");
        exit();
    }
    
    // Проверяем соединение с базой данных
    if ($db->connect_error) {
        $_SESSION['error_message'] = 'Ошибка подключения к базе данных';
        header("Location: admin-panel.php");
        exit();
    }
    
    // Подготавливаем запрос с проверкой на ошибки
    $stmt = $db->prepare("UPDATE requests SET status = ?, rejection_reason = ? WHERE id = ?");
    
    if ($stmt === false) {
        $_SESSION['error_message'] = 'Ошибка подготовки запроса: ' . $db->error;
        header("Location: admin-panel.php");
        exit();
    }
    
    // Привязываем параметры и выполняем запрос
    $bindResult = $stmt->bind_param("ssi", $newStatus, $rejectionReason, $requestId);
    
    if ($bindResult === false) {
        $_SESSION['error_message'] = 'Ошибка привязки параметров: ' . $stmt->error;
        header("Location: admin-panel.php");
        exit();
    }
    
    $executeResult = $stmt->execute();
    
    if ($executeResult) {
        $_SESSION['success_message'] = 'Статус заявки успешно обновлен';
    } else {
        $_SESSION['error_message'] = 'Ошибка при обновлении статуса: ' . $stmt->error;
    }
    
    $stmt->close();
    header("Location: admin-panel.php");
    exit();
}

// Обработка удаления заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    $requestId = intval($_POST['request_id']);
    
    $stmt = $db->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Заявка успешно удалена';
    } else {
        $_SESSION['error_message'] = 'Ошибка при удалении заявки';
    }
    
    $stmt->close();
    header("Location: admin-panel.php");
    exit();
}

// Получаем все заявки
$query = "SELECT r.*, u.fullname FROM requests r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC";
$result = $db->query($query);
$requests = array();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'new': return 'Новая';
        case 'in_progress': return 'В работе';
        case 'completed': return 'Подтверждено';
        case 'rejected': return 'Отклонена';
        default: return $status;
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'new': return 'status-new';
        case 'in_progress': return 'status-in_progress';
        case 'completed': return 'status-completed';
        case 'rejected': return 'status-rejected';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель | Управление заявками</title>
    <link rel="icon" href="img/apple-touch-icon.png" sizes="96x96" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4a6bff;
            --success: #28a745;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --border: #dee2e6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        
        .admin-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .badge {
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #3a5bd9;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: 1px solid var(--danger);
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }
        
        /* Table */
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .requests-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-align: left;
            padding: 16px 20px;
            border-bottom: 2px solid var(--border);
        }
        
        .requests-table td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        
        .requests-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-new {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .status-in_progress {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .reason-text {
            font-size: 12px;
            color: #721c24;
            margin-top: 5px;
            font-style: italic;
        }
        
        /* Action buttons */
        .action-btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .edit-btn {
            background-color: var(--primary);
            color: white;
        }
        
        .delete-btn {
            background-color: var(--danger);
            color: white;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        textarea.form-control {
            min-height: 100px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">
                Управление заявками
                <span class="badge"><?php echo count($requests); ?></span>
            </h1>
            <a href="index.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <table class="requests-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Номер</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Адрес</th>
                    <th>Услуга</th>
                    <th>Дата создания</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo $request['id']; ?></td>
                    <td><?php echo htmlspecialchars($request['request_number']); ?></td>
                    <td><?php echo htmlspecialchars($request['fullname'] ?? 'Не указано'); ?></td>
                    <td><?php echo htmlspecialchars($request['phone']); ?></td>
                    <td><?php echo htmlspecialchars($request['address']); ?></td>
                    <td><?php echo htmlspecialchars($request['service_type']); ?></td>
                    <td><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                    <td>
                        <span class="status-badge <?php echo getStatusClass($request['status']); ?>">
                            <?php echo getStatusText($request['status']); ?>
                        </span>
                        <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?>
                            <div class="reason-text">
                                <strong>Причина:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>')">
                            <i class="fas fa-edit"></i> Изменить
                        </button>
                        <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $request['id']; ?>)">
                            <i class="fas fa-trash-alt"></i> Удалить
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Изменение статуса заявки</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="request_id" id="editRequestId">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label for="editStatus" class="form-label">Статус:</label>
                    <select name="new_status" id="editStatus" class="form-control" onchange="toggleReasonField()">
                        <option value="new">Новая</option>
                        <option value="in_progress">В работе</option>
                        <option value="completed">Подтверждено</option>
                        <option value="rejected">Отклонена</option>
                    </select>
                </div>
                
                <div class="form-group" id="reasonGroup" style="display: none;">
                    <label for="rejectionReason" class="form-label">Причина отклонения:</label>
                    <textarea name="rejection_reason" id="rejectionReason" class="form-control" placeholder="Укажите причину отклонения..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Подтверждение удаления</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <p>Вы уверены, что хотите удалить эту заявку? Это действие нельзя отменить.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="request_id" id="deleteRequestId">
                <input type="hidden" name="delete_request" value="1">
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open edit modal
        function openEditModal(requestId, currentStatus) {
            const modal = document.getElementById('editModal');
            document.getElementById('editRequestId').value = requestId;
            document.getElementById('editStatus').value = currentStatus;
            toggleReasonField();
            modal.style.display = 'flex';
        }
        
        // Confirm delete
        function confirmDelete(requestId) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('deleteRequestId').value = requestId;
            modal.style.display = 'flex';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Toggle reason field
        function toggleReasonField() {
            const status = document.getElementById('editStatus').value;
            const reasonGroup = document.getElementById('reasonGroup');
            
            if (status === 'rejected') {
                reasonGroup.style.display = 'block';
                document.getElementById('rejectionReason').required = true;
            } else {
                reasonGroup.style.display = 'none';
                document.getElementById('rejectionReason').required = false;
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>