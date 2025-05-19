<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$message = "";

// Handle form submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_visitor'])) {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone_no']) ? trim($_POST['phone_no']) : '';
        $visit_date = isset($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d');
        $petIDs = isset($_POST['petIDs']) ? $_POST['petIDs'] : array();

        if ($name && $phone && !empty($petIDs)) {
            try {
                // Check if visitor exists
                $stmt = $pdo->prepare("SELECT visitorID FROM Visitor WHERE name = ? AND phone_no = ?");
                $stmt->execute(array($name, $phone));
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $visitorID = $existing['visitorID'];
                } else {
                    // Insert new visitor
                    $stmt = $pdo->query("SELECT MAX(visitorID) AS max_id FROM Visitor");
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $visitorID = (isset($row['max_id']) ? $row['max_id'] : 7000) + 1;

                    $stmt = $pdo->prepare("INSERT INTO Visitor (visitorID, name, phone_no, visit_date) VALUES (?, ?, ?, ?)");
                    $stmt->execute(array($visitorID, $name, $phone, $visit_date));
                }

                // Link interests
                $check = $pdo->prepare("SELECT 1 FROM Interested WHERE visitorID = ? AND petID = ?");
                $insert = $pdo->prepare("INSERT INTO Interested (visitorID, petID) VALUES (?, ?)");

                foreach ($petIDs as $petID) {
                    if (is_numeric($petID)) {
                        $check->execute(array($visitorID, $petID));
                        if (!$check->fetch()) {
                            $insert->execute(array($visitorID, $petID));
                        }
                    }
                }

                $message = "✅ Visitor saved with pet interests.";
            } catch (PDOException $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        } else {
            $message = "❌ Please fill in all fields and select at least one pet.";
        }
    }

    // Handle removal
    if (isset($_POST['remove_interest'])) {
        $visitorID = $_POST['visitorID'];
        $petID = $_POST['petID'];
        if (is_numeric($visitorID) && is_numeric($petID)) {
            $stmt = $pdo->prepare("DELETE FROM Interested WHERE visitorID = ? AND petID = ?");
            $stmt->execute(array($visitorID, $petID));
            $message = "✅ Interest removed.";
        }
    }
}

// Fetch available pets
$pets = $pdo->query("SELECT petID, name FROM Animal WHERE status = 'Available'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch interest records
$stmt = $pdo->query("
    SELECT V.visitorID, V.name AS visitor_name, V.visit_date,
           A.name AS pet_name, A.petID, S.name AS shelter_name
    FROM Visitor V
    JOIN Interested I ON V.visitorID = I.visitorID
    JOIN Animal A ON I.petID = A.petID
    JOIN Shelter S ON A.shelterID = S.shelterID
    ORDER BY V.visitorID DESC
");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visitor Interests</title>
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
<h1 style="text-align:center;">Visitors Interested in Pets</h1>

<?php if (!empty($message)): ?>
    <p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<table>
    <tr>
        <th>Visitor Name</th>
        <th>Visit Date</th>
        <th>Pet Name</th>
        <th>Shelter</th>
        <th>Remove</th>
    </tr>
    <?php foreach ($records as $r): ?>
    <tr>
        <td><?php echo htmlspecialchars($r['visitor_name']); ?></td>
        <td><?php echo $r['visit_date']; ?></td>
        <td><?php echo htmlspecialchars($r['pet_name']); ?></td>
        <td><?php echo htmlspecialchars($r['shelter_name']); ?></td>
        <td>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="visitorID" value="<?php echo $r['visitorID']; ?>">
                <input type="hidden" name="petID" value="<?php echo $r['petID']; ?>">
                <button type="submit" name="remove_interest">Remove</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h2 style="text-align:center;">New Visitor Sign-In</h2>

<form method="POST">
    <input type="hidden" name="add_visitor" value="1">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="text" name="phone_no" placeholder="Phone Number" required>
    <label>Visit Date:</label>
    <input type="date" name="visit_date" value="<?php echo date('Y-m-d'); ?>" required>
    <label>Select Pets You're Interested In:</label>
    <select name="petIDs[]" multiple required size="5">
        <?php foreach ($pets as $p): ?>
            <option value="<?php echo $p['petID']; ?>">
                <?php echo htmlspecialchars($p['name']) . " (ID: " . $p['petID'] . ")"; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Submit</button>
</form>

</body>
</html>

