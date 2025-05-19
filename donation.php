<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$message = "";

// Handle donation form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['donor_name']) ? trim($_POST['donor_name']) : '';
    $amount = isset($_POST['amount_donated']) ? floatval($_POST['amount_donated']) : 0;
    $shelterID = isset($_POST['shelterID']) ? $_POST['shelterID'] : '';
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    if ($name && $amount > 0 && is_numeric($shelterID)) {
        try {
            // Get next donor ID
            $stmt = $pdo->query("SELECT MAX(donorID) AS max_id FROM Donor");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $donorID = (isset($row['max_id']) ? $row['max_id'] : 3000) + 1;

            // Insert donor
            $stmt = $pdo->prepare("INSERT INTO Donor (donorID, donor_name, amount_donated, anonymous) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($donorID, $name, $amount, $anonymous));

            // Link to shelter
            $stmt = $pdo->prepare("INSERT INTO Donating (donorID, shelterID) VALUES (?, ?)");
            $stmt->execute(array($donorID, $shelterID));

            $message = "✅ Thank you for your donation!";
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "❌ Please complete all fields correctly.";
    }
}

// Get shelters
$shelters = $pdo->query("SELECT shelterID, name FROM Shelter")->fetchAll(PDO::FETCH_ASSOC);

// Get donations
$stmt = $pdo->query("
    SELECT D.donor_name, D.amount_donated, D.anonymous, S.name AS shelter_name
    FROM Donor D
    JOIN Donating DN ON D.donorID = DN.donorID
    JOIN Shelter S ON DN.shelterID = S.shelterID
    ORDER BY D.donor_name
");
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donations</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f9f9f9; }
        a { text-decoration: none; margin-bottom: 20px; display: inline-block; }
        table {
            width: 90%; margin: 20px auto;
            border-collapse: collapse; background: #fff;
        }
        th, td {
            padding: 10px; border: 1px solid #ccc;
        }
        th { background: #eee; }
        form {
            width: 400px; margin: 30px auto;
            padding: 20px; background: #fff; border-radius: 10px;
        }
        input, select, button {
            width: 95%; padding: 10px; margin: 10px 0;
        }
        .message { text-align: center; font-weight: bold; color: green; }
    </style>
</head>
<body>

<a href="index.php">← Back to Home</a>
<h1 style="text-align:center;">Donation Records</h1>

<?php if (!empty($message)): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>Donor Name</th>
        <th>Amount Donated</th>
        <th>Shelter</th>
    </tr>
    <?php foreach ($donations as $d): ?>
    <tr>
        <td><?php echo htmlspecialchars($d['anonymous'] ? 'Anonymous' : $d['donor_name']); ?></td>
        <td>$<?php echo number_format($d['amount_donated'], 2); ?></td>
        <td><?php echo htmlspecialchars($d['shelter_name']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2 style="text-align:center;">Make a Donation</h2>

<form method="POST">
    <input type="text" name="donor_name" placeholder="Your Name" required>
    <input type="number" step="0.01" name="amount_donated" placeholder="Amount" required>
    <select name="shelterID" required>
        <option value="">-- Select Shelter --</option>
        <?php foreach ($shelters as $s): ?>
            <option value="<?php echo $s['shelterID']; ?>">
                <?php echo htmlspecialchars($s['name']) . " (ID: " . $s['shelterID'] . ")"; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <label>
        <input type="checkbox" name="anonymous" value="1"> Make my donation anonymous
    </label>
    <button type="submit">Donate</button>
</form>

</body>
</html>

