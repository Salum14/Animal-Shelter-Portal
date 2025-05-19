<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : '';
    $shelterID = isset($_POST['shelterID']) ? $_POST['shelterID'] : '';

    if ($name && $phone && is_numeric($shelterID)) {
        try {
            // Get next volunteer ID
            $stmt = $pdo->query("SELECT MAX(volunteerID) AS max_id FROM Volunteer");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $volunteerID = (isset($row['max_id']) ? $row['max_id'] : 4000) + 1;

            $stmt = $pdo->prepare("INSERT INTO Volunteer (volunteerID, name, phone_no, shelterID) VALUES (?, ?, ?, ?)");
            $stmt->execute(array($volunteerID, $name, $phone, $shelterID));

            $message = "✅ Volunteer added!";
        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "❌ Please fill in all fields.";
    }
}

// Get shelters for dropdown
$shelters = $pdo->query("SELECT shelterID, name FROM Shelter")->fetchAll(PDO::FETCH_ASSOC);

// Get volunteer records
$stmt = $pdo->query("
    SELECT V.name AS volunteer_name, V.phone_no, S.name AS shelter_name
    FROM Volunteer V
    JOIN Shelter S ON V.shelterID = S.shelterID
    ORDER BY V.name
");
$volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Volunteers</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f0f0f0; }
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
<h1 style="text-align:center;">Volunteer List</h1>

<?php if (!empty($message)): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>Volunteer Name</th>
        <th>Phone Number</th>
        <th>Shelter</th>
    </tr>
    <?php foreach ($volunteers as $v): ?>
    <tr>
        <td><?php echo htmlspecialchars($v['volunteer_name']); ?></td>
        <td><?php echo htmlspecialchars($v['phone_no']); ?></td>
        <td><?php echo htmlspecialchars($v['shelter_name']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2 style="text-align:center;">Become a Volunteer</h2>

<form method="POST">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="text" name="phone_no" placeholder="Phone Number" required>
    <select name="shelterID" required>
        <option value="">-- Select Shelter --</option>
        <?php foreach ($shelters as $s): ?>
            <option value="<?php echo $s['shelterID']; ?>">
                <?php echo htmlspecialchars($s['name']) . " (ID: " . $s['shelterID'] . ")"; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Volunteer</button>
</form>

</body>
</html>

