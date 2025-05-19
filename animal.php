<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$breedFilter = isset($_GET['breed']) ? trim($_GET['breed']) : '';
$ageFilter = isset($_GET['age']) ? trim($_GET['age']) : '';

// Build dynamic query
$sql = "
    SELECT A.name AS pet_name, A.breed, A.age, A.date_admitted, S.name AS shelter_name
    FROM Animal A
    JOIN Shelter S ON A.shelterID = S.shelterID
    WHERE A.status = 'Available'
";

$params = [];

if ($breedFilter !== '') {
    $sql .= " AND A.breed LIKE ?";
    $params[] = "%" . $breedFilter . "%";
}

if ($ageFilter !== '' && is_numeric($ageFilter)) {
    $sql .= " AND A.age = ?";
    $params[] = $ageFilter;
}

$sql .= " ORDER BY A.date_admitted DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Future Pet!</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f8f8f8; }
        a { text-decoration: none; display: inline-block; margin-bottom: 20px; }
        table {
            width: 90%; margin: auto;
            border-collapse: collapse; background: #fff;
        }
        th, td {
            padding: 10px; border: 1px solid #ccc;
        }
        th { background-color: #eee; }
        form {
            width: 90%; margin: 0 auto 20px auto;
            background: #fff; padding: 15px; border-radius: 8px;
        }
        input[type="text"], input[type="number"] {
            padding: 8px; width: 200px; margin-right: 10px;
        }
        button {
            padding: 8px 15px;
        }
    </style>
</head>
<body>

<a href="index.php">← Back to Home</a>
<h1 style="text-align:center;">Your Future Pet!</h1>

<form method="GET" action="animal.php">
    <label for="breed">Breed:</label>
    <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($breedFilter); ?>">

    <label for="age">Age:</label>
    <input type="number" id="age" name="age" min="0" value="<?php echo htmlspecialchars($ageFilter); ?>">

    <button type="submit">Search</button>
    <a href="animal.php" style="margin-left:10px;">Reset</a>
</form>

<table>
    <tr>
        <th>Name</th>
        <th>Breed</th>
        <th>Age</th>
        <th>Date Admitted</th>
        <th>Shelter</th>
    </tr>
    <?php foreach ($pets as $pet): ?>
    <tr>
        <td><?php echo htmlspecialchars($pet['pet_name']); ?></td>
        <td><?php echo htmlspecialchars($pet['breed']); ?></td>
        <td><?php echo $pet['age']; ?></td>
        <td><?php echo $pet['date_admitted']; ?></td>
        <td><?php echo htmlspecialchars($pet['shelter_name']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

