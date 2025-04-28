<?php
session_start();

// Подключение к базе данных
require_once 'db.php';

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $fullname = $db->real_escape_string($_POST['fullname']);
    $phone = $db->real_escape_string($_POST['phone']);
    $username = $db->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Проверка на существование пользователя
    $check_query = "SELECT id FROM users WHERE username='$username'";
    $check_result = $db->query($check_query);

    if ($check_result->num_rows > 0) {
        $error = "Пользователь с таким логином уже существует";
    } else {
        // Вставка нового пользователя
        $query = "INSERT INTO users (fullname, phone, username, password) VALUES ('$fullname', '$phone', '$username', '$password')";
        
        if ($db->query($query)) {
            $_SESSION['user_id'] = $db->insert_id;
            $_SESSION['username'] = $username;
            header("Location: my_applications.html");
            exit();
        } else {
            $error = "Ошибка при регистрации: " . $db->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="icon" type="" href="img/apple-touch-icon.png" sizes="96x96" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/logo.svg" alt="Логотип системы">
        </div>
        
        <h1>Регистрация в системе</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <div class="input-group">
                <label for="fullname">ФИО</label>
                <input type="text" id="fullname" name="fullname" placeholder="Введите ФИО..." required>
            </div>

            <div class="input-group">
                <label for="phone">Номер телефона</label>
                <input type="text" id="phone" name="phone" placeholder="+7(XXX)-XXX-XX-XX" required>
            </div>

            <div class="input-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" placeholder="Введите ваш логин" required>
            </div>
            
            <div class="input-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите ваш пароль" required>
            </div>
            
            <button type="submit" name="register">Зарегистрироваться</button>
        </form>
        
        <div class="footer">
            <p>Уже зарегистрированы? <a href="index.php">Войти</a></p>
        </div>
    </div>
</body>
</html>