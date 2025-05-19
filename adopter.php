<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$message = "";

// Handle adoption form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adopter_name = isset($_POST['adopter_name']) ? $_POST['adopter_name'] : '';
    $phone = isset($_POST['phone_no']) ? $_POST['phone_no'] : '';
    $petID = isset($_POST['petID']) ? $_POST['petID'] : '';

    if ($adopter_name && $phone && is_numeric($petID)) {
        try {
            // Check pet status
            $stmt = $pdo->prepare("SELECT status FROM Animal WHERE petID = ?");
            $stmt->execute(array($petID));
            $status = $stmt->fetchColumn();

            if ($status === 'Available') {
                $stmt = $pdo->query("SELECT MAX(adopterID) AS max_id FROM Adopter");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $nextID = (isset($row['max_id']) ? $row['max_id'] : 8000) + 1;

                $stmt = $pdo->prepare("INSERT INTO Adopter (adopterID, name, phone_no) VALUES (?, ?, ?)");
                $stmt->execute(array($nextID, $adopter_name, $phone));

                $stmt = $pdo->prepare("INSERT INTO Adopts (adopterID, petID) VALUES (?, ?)");
                $stmt->execute(array($nextID, $petID));

                $pdo->prepare("UPDATE Animal SET status = 'Adopted' WHERE petID = ?")->execute(array($petID));

                $message = "✅ You have successfully adopted this pet!";
            } else {
                $message = "❌ This pet is already adopted.";
            }
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "❌ Please fill in all fields.";
    }
}

// Fetch all pets with adopter info
$stmt = $pdo->query("
    SELECT A.petID, A.name AS pet_name, A.breed, A.status,
           AD.name AS adopter_name
    FROM Animal A
    LEFT JOIN Adopts AP ON A.petID = AP.petID
    LEFT JOIN Adopter AD ON AP.adopterID = AD.adopterID
    ORDER BY A.petID
");
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Adopter Page</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f0f0f0; }
        a { text-decoration: none; display: inline-block; margin-bottom: 20px; }
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
<h1 style="text-align:center;">Adoption Records</h1>

<?php if (!empty($message)): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>Name</th>
        <th>Breed</th>
        <th>Status</th>
        <th>Adopter Name</th>
    </tr>
    <?php foreach ($pets as $p): ?>
    <tr>
        <td><?php echo htmlspecialchars($p['pet_name']); ?></td>
        <td><?php echo htmlspecialchars($p['breed']); ?></td>
        <td><?php echo htmlspecialchars($p['status']); ?></td>
        <td><?php echo htmlspecialchars(isset($p['adopter_name']) ? $p['adopter_name'] : ''); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2 style="text-align:center;">Adopt an Available Pet</h2>

<form method="POST">
    <input type="text" name="adopter_name" placeholder="Your Name" required>
    <input type="text" name="phone_no" placeholder="Phone Number" required>
    <select name="petID" required>
        <option value="">-- Select Available Pet --</option>
        <?php foreach ($pets as $p): ?>
            <?php if ($p['status'] === 'Available'): ?>
                <option value="<?php echo $p['petID']; ?>">
                    <?php echo htmlspecialchars($p['pet_name']) . " (Breed: " . $p['breed'] . ")"; ?>
                </option>
            <?php endif; ?>
        <?php endforeach; ?>
    </select>
    <button type="submit">Adopt</button>
</form>

</body>
</html>

