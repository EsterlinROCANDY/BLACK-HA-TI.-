
<?php
// Start a session
session_start();

// Database setup (SQLite for simplicity)
try {
    $db = new PDO('sqlite:topup_cards.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product TEXT NOT NULL,
        amount REAL NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Helper function to render HTML
function render($content) {
    echo '<!DOCTYPE html><html><head><title>BLACK HAÏTI</title></head><body>';
    echo $content;
    echo '</body></html>';
}

// Handle user registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        render('<p>Registration successful. <a href="/">Login here</a>.</p>');
    } catch (PDOException $e) {
        render('<p>Error: ' . $e->getMessage() . '</p>');
    }
    exit;
}

// Handle user login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: /');
        exit;
    } else {
        render('<p>Invalid credentials. <a href="/">Try again</a>.</p>');
    }
    exit;
}

// Handle transaction
if (isset($_POST['buy'])) {
    if (!isset($_SESSION['user_id'])) {
        render('<p>Please <a href="/">login</a> to make a purchase.</p>');
        exit;
    }
    $product = $_POST['product'];
    $amount = $_POST['amount'];
    $message = urlencode("Bonjour, je souhaite acheter : $product pour un montant de $amount USD.");
    $whatsapp_url = "https://wa.me/44218865?text=" . $message;
    header("Location: " . $whatsapp_url);
    exit;
}

// Show login/registration or main page
if (!isset($_SESSION['user_id'])) {
    render('
        <h1>Top-Up & Gift Cards</h1>
        <form method="POST">
            <h3>Login</h3>
            <label>Username: <input type="text" name="username" required></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <button type="submit" name="login">Login</button>
        </form>
        <form method="POST">
            <h3>Register</h3>
            <label>Username: <input type="text" name="username" required></label><br>
            <label>Password: <input type="password" name="password" required></label><br>
            <button type="submit" name="register">Register</button>
        </form>
    ');
} else {
    $products = ['Top-Up $5', 'Top-Up $10', 'Gift Card $25', 'Gift Card $50'];
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    render('<h1>Welcome!</h1>
        <form method="POST">
            <h3>Purchase</h3>
            <label>Product: 
                <select name="product">'
                . implode('', array_map(fn($p) => "<option>$p</option>", $products)) .
                '</select>
            </label><br>
            <label>Amount: <input type="number" name="amount" required></label><br>
            <button type="submit" name="buy">Buy</button>
        </form>
        <h3>Your Transactions</h3>' .
        (count($transactions) ? '<ul>' . implode('', array_map(fn($t) => "<li>{$t['product']} - \${$t['amount']} ({$t['created_at']})</li>", $transactions)) . '</ul>' : '<p>No transactions yet.</p>') .
        '<p><a href="/logout.php">Logout</a></p>
    ');
}

// Logout handler
if ($_SERVER['REQUEST_URI'] === '/logout.php') {
    session_destroy();
    header('Location: /');
    exit;
}
?>
