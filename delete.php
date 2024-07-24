<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-wallet 2.0";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['deleteid'])) {
    $incomeId = intval($_GET['deleteid']); // Ensure the ID is an integer

    // Prepare the SQL query using a parameterized statement
    $deleteQuery = "DELETE FROM income WHERE id = ?";
    $statement = $conn->prepare($deleteQuery);

    if ($statement) {
        $statement->bind_param("i", $incomeId); // Bind the integer parameter

        if ($statement->execute()) {
            header('location: index.php');
            exit();
        } else {
            echo "Error deleting record: " . $statement->error;
        }

        $statement->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
}
?>