<?php
// Configuration principale du site
$site_title = "BLACK HAÏTI";
$domain = "https://black-ha-ti-p0rk.onrender.com";
$whatsapp_number = "44218865"; // Votre numéro WhatsApp

// Paramètres de connexion à la base de données
$db_host = "localhost"; // Adresse du serveur
$db_user = "root"; // Utilisateur de la base
$db_password = ""; // Mot de passe de la base
$db_name = "black_haiti"; // Nom de la base

// Connexion à la base de données
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// Fonction pour traiter une transaction
function processTransaction($phoneNumber, $amount, $type, $whatsapp_number) {
    global $conn;

    // Validation des entrées
    if (!is_numeric($phoneNumber) || strlen($phoneNumber) < 8 || $amount <= 0) {
        return "Erreur : Données invalides. Veuillez vérifier le numéro et le montant.";
    }

    // Générer les détails de la transaction
    $transaction_id = uniqid("txn_");
    $status = "en attente";

    // Enregistrement de la transaction
    $stmt = $conn->prepare("INSERT INTO transactions (phone_number, amount, type, transaction_id, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsss", $phoneNumber, $amount, $type, $transaction_id, $status);

    if ($stmt->execute()) {
        // Préparer le lien WhatsApp
        $message = urlencode("Bonjour, je souhaite effectuer une transaction.\n\nDétails :\n- Numéro : $phoneNumber\n- Montant : $amount Gourdes\n- Service : $type\n- ID Transaction : $transaction_id");
        $whatsapp_link = "https://wa.me/509$whatsapp_number?text=$message";
        return $whatsapp_link;
    } else {
        return "Erreur lors de l'enregistrement : " . $stmt->error;
    }
}

// Traitement du formulaire
$whatsapp_link = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneNumber = $_POST['phone_number'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];

    $whatsapp_link = processTransaction($phoneNumber, $amount, $type, $whatsapp_number);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
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
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.5);
        }
        .container form input,
        .container form select,
        .container form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
        }
        .container form button {
            background-color: #e91e63;
            color: #fff;
            cursor: pointer;
        }
        .container form button:hover {
            background-color: #d81b60;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #222;
            border-left: 4px solid #e91e63;
        }
        .whatsapp-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #25D366;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .whatsapp-link:hover {
            background-color: #1da851;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo $site_title; ?></h1>
        <p>Rechargez vos mobiles et achetez des cartes cadeaux facilement</p>
    </header>
    <div class="container">
        <form method="POST" action="">
            <label for="phone_number">Numéro de téléphone :</label>
            <input type="text" id="phone_number" name="phone_number" placeholder="Exemple : 50912345678" required>

            <label for="amount">Montant (en gourdes) :</label>
            <input type="number" id="amount" name="amount" placeholder="Exemple : 100" required>

            <label for="type">Type de service :</label>
            <select id="type" name="type">
                <option value="topup">Recharge Mobile</option>
                <option value="giftcard">Carte Cadeau</option>
            </select>

            <button type="submit">Effectuer la Transaction</button>
        </form>
        <?php 
        if (!empty($whatsapp_link)) { 
            echo "<a class='whatsapp-link' href='$whatsapp_link' target='_blank'>Payer via WhatsApp</a>"; 
        } 
        ?>
    </div>
</body>
</html>