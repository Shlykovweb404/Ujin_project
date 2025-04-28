<?php
session_start();

// Подключение к базе данных
require_once 'db.php';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Проверка на администратора
    if ($username === 'adminka' && $password === 'password') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin-panel.php"); // Перенаправляем на admin-panel.php
        exit();
    }

    // Сначала проверяем админские credentials
    if ($username === 'adminka' && $password === 'password') {
        $_SESSION['admin_authenticated'] = true;
        header("Location: admin-panel.php");
        exit();
    }

    // Если не админ, проверяем обычного пользователя
    $username = $db->real_escape_string($username);
    $query = "SELECT id, password FROM users WHERE username='$username'";
    $result = $db->query($query);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header("Location: my_applications.php");
            exit();
        } else {
            $error = "Неверный пароль";
        }
    } else {
        $error = "Пользователь не найден";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="icon" type="" href="img/apple-touch-icon.png" sizes="96x96" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/logo.svg" alt="Логотип системы">
        </div>
        
        <h1>Вход в систему</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="index.php" method="POST" id="adminka_1">
            <div class="input-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" placeholder="Введите ваш логин" required>
            </div>
            
            <div class="input-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
            </div>
            
            <button type="submit" name="login">Войти</button>
        </form>
        
        <div class="footer">
            <p>Нет аккаунта? <a href="register.php">Зарегистрироваться</a></p>
        </div>
    </div>
    <script>
        document.getElementById('adminka_1').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (username === 'adminka' && password === 'password') {
                // Создаем скрытую форму для отправки данных
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php';
                
                const input1 = document.createElement('input');
                input1.type = 'hidden';
                input1.name = 'username';
                input1.value = username;
                
                const input2 = document.createElement('input');
                input2.type = 'hidden';
                input2.name = 'password';
                input2.value = password;
                
                const input3 = document.createElement('input');
                input3.type = 'hidden';
                input3.name = 'login';
                input3.value = '1';
                
                form.appendChild(input1);
                form.appendChild(input2);
                form.appendChild(input3);
                document.body.appendChild(form);
                form.submit();
            } else {
                // Если не админ, отправляем форму как обычно
                return true;
            }
        });
    </script>
</body>
</html>