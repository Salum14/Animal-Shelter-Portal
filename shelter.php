<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

$stmt = $pdo->query("
    SELECT S.shelterID, S.name, S.phone_no, S.location,
           COUNT(DISTINCT A.petID) AS animal_count,
           COALESCE(SUM(D.amount_donated), 0) AS total_donations
    FROM Shelter S
    LEFT JOIN Animal A ON S.shelterID = A.shelterID
    LEFT JOIN Donating DN ON S.shelterID = DN.shelterID
    LEFT JOIN Donor D ON DN.donorID = D.donorID
    GROUP BY S.shelterID
    ORDER BY S.name
");

$shelters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Our Shelters</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f7f7f7; }
        a { text-decoration: none; margin-bottom: 20px; display: inline-block; }
        .shelter {
            width: 90%; margin: 10px auto; background: #fff;
            border: 1px solid #ccc; border-radius: 6px;
        }
        .shelter h3 {
            margin: 0; padding: 15px;
            background: #eee; cursor: pointer;
        }
        .details {
            display: none; padding: 15px;
        }
    </style>
    <script>
        function toggleDetails(id) {
            var section = document.getElementById("details-" + id);
            section.style.display = (section.style.display === "none" ? "block" : "none");
        }
    </script>
</head>
<body>

<a href="index.php">← Back to Home</a>
<h1 style="text-align:center;">Our Shelters</h1>

<?php foreach ($shelters as $s): ?>
<div class="shelter">
    <h3 onclick="toggleDetails(<?php echo $s['shelterID']; ?>)">
        <?php echo htmlspecialchars($s['name']); ?>
    </h3>
    <div class="details" id="details-<?php echo $s['shelterID']; ?>">
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($s['phone_no']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($s['location']); ?></p>
        <p><strong>Number of Animals:</strong> <?php echo $s['animal_count']; ?></p>
        <p><strong>Total Donations:</strong> $<?php echo number_format($s['total_donations'], 2); ?></p>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>

