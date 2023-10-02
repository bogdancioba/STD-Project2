<?php
$serverName = 'tcp:gabrielsv.database.windows.net,1433';  
$databaseName = 'gabrieldb';  
$username = 'gabriel';  
$password = 'std-1234'; 

try {
    $conn = new PDO("sqlsrv:server = $serverName; Database = $databaseName", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->query("SELECT fisier, link, data, rezultat FROM Date ORDER BY data DESC");

    $processResult = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $processResult .= "<tr>";
        $processResult .= "<td>" . $row['fisier'] . "</td>";
        $processResult .= "<td><a href='" . $row['link'] . "'>" . $row['link'] . "</a></td>";
        $processResult .= "<td>" . $row['data'] . "</td>";
        $processResult .= "<td>" . $row['rezultat'] . "</td>";
        $processResult .= "</tr>";
    }
} catch (PDOException $e) {
    die("Error connecting to SQL Server: " . $e->getMessage());
}

$conn = null;
?>
