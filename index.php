<?php
// Configuration principale
$site_title = "BLACK HAÏTI";
$domain = "https://black-ha-ti-p0rk.onrender.com";
$whatsapp_number = "50944218865"; // Numéro WhatsApp avec indicatif

// Connexion à la base de données
$db_host = getenv('DB_HOST'); // Utilisation des variables d'environnement
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');
$db_name = getenv('DB_NAME');

// Vérification des valeurs obligatoires
if (!$db_host || !$db_user || !$db_name) {
    die("Les variables d'environnement pour la base de données ne sont pas correctement définies.");
}

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Liste des produits disponibles
$products = [
    "Recharge Mobile" => [
        ["label" => "Recharge Digicel - 100 HTG", "price" => 100, "type" => "topup"],
        ["label" => "Recharge Digicel - 500 HTG", "price" => 500, "type" => "topup"],
        ["label" => "Recharge Natcom - 100 HTG", "price" => 100, "type" => "topup"],
        ["label" => "Recharge Natcom - 500 HTG", "price" => 500, "type" => "topup"]
    ],
    "Cartes Cadeaux" => [
        ["label" => "Carte Google Play - 10 USD", "price" => 10, "type" => "giftcard"],
        ["label" => "Carte Amazon - 20 USD", "price" => 20, "type" => "giftcard"],
        ["label" => "Carte Netflix - 1 mois", "price" => 15, "type" => "giftcard"]
    ]
];

// Fonction pour enregistrer une transaction
function saveTransaction($conn, $product) {
    $transaction_id = uniqid("txn_");
    $label = $product['label'];
    $price = $product['price'];
    $type = $product['type'];
    $status = "en attente";

    $stmt = $conn->prepare("INSERT INTO transactions (transaction_id, label, price, type, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Erreur de préparation de la requête : " . $conn->error);
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }

    $stmt->bind_param("ssdss", $transaction_id, $label, $price, $type, $status);
    $stmt->execute();
    $stmt->close();

    return $transaction_id;
}

// Fonction pour générer un lien WhatsApp
function generateWhatsAppLink($product, $transaction_id, $whatsapp_number) {
    $message = urlencode("Bonjour, je souhaite acheter :\n- Produit : " . $product['label'] . "\n- Prix : " . $product['price'] . " HTG/USD\n- ID Transaction : $transaction_id");
    return "https://wa.me/$whatsapp_number?text=$message";
}

// Traitement du formulaire d'achat
$whatsapp_link = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_key'])) {
    $product_key = $_POST['product_key'];
    $found = false;

    foreach ($products as $category => $items) {
        foreach ($items as $key => $product) {
            if ($product_key === "$category-$key") {
                $transaction_id = saveTransaction($conn, $product);
                $whatsapp_link = generateWhatsAppLink($product, $transaction_id, $whatsapp_number);
                $found = true;
                break 2;
            }
        }
    }

    if (!$found) {
        echo "<p>Produit non trouvé. Veuillez réessayer.</p>";
    }
}

// Fermeture de la connexion
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #000;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 10px;
        }
        .product {
            margin-bottom: 20px;
            padding: 15px;
            background: #222;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .buy-form button {
            padding: 10px 15px;
            background-color: #25D366;
            color: #fff;
            border: none;
            border-radius: 5px;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            background: #25D366;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($site_title); ?></h1>
    </header>
    <div class="container">
        <?php foreach ($products as $category => $items): ?>
            <h2><?php echo htmlspecialchars($category); ?></h2>
            <?php foreach ($items as $key => $product): ?>
                <div class="product">
                    <div>
                        <h3><?php echo htmlspecialchars($product['label']); ?></h3>
                        <p>Prix : <?php echo htmlspecialchars($product['price']); ?> HTG/USD</p>
                    </div>
                    <form class="buy-form" method="POST">
                        <input type="hidden" name="product_key" value="<?php echo htmlspecialchars("$category-$key"); ?>">
                        <button type="submit">Acheter</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($whatsapp_link): ?>
            <div class="message">
                <p>Votre commande a été enregistrée ! Cliquez sur le lien ci-dessous pour finaliser le paiement via WhatsApp :</p>
                <a href="<?php echo htmlspecialchars($whatsapp_link); ?>" target="_blank">Payer via WhatsApp</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>