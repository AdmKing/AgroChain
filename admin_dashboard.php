<?php
// this is just a page for testinng connection to database using my backend config
require 'backend/config/db.php';

// Handle form submission
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['firstname']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['pwd'], PASSWORD_DEFAULT);
    $role_id  = intval($_POST['role_id']); // must exist in roles table

    // Insert new user with selected role
    $stmt = $pdo->prepare("INSERT INTO users (firstname, email, pwd, role_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $password, $role_id])) {
        $message = "✅ User created successfully!";
    } else {
        $message = "❌ Error creating user.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
</head>
<body>
<h1>Create a New Admin User</h1>

<?php if (!empty($message)): ?>
    <p><?php echo $message; ?></p>
<?php endif; ?>

<form method="post">
    <label>Username:</label><br>
    <p>your username is your first name</p>
    <input type="text" name="firstname" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="pwd" required><br><br>

    <label>Role ID:</label><br>
    <input type="number" name="role_id" required><br>
    <small>(Use the ID of your Admin role from the roles table)</small><br><br>

    <button type="submit">Create Admin</button>
</form>

</body>
</html>
