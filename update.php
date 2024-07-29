<?php
session_start(); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-wallet 2.0";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$todayDate = date('Y-m-d');


if (isset($_GET['deleteMandatoryId'])) {
    $mandatoryId = $conn->real_escape_string($_GET['deleteMandatoryId']); 
    $latestDate = $_SESSION['latestDate'];
     
    $latestDateType = new DateTime($latestDate); // Konwersja stringa na DateTime
    $latestDateType->modify("-1 day");
    $dayBeforeLatest = $latestDateType->format('Y-m-d');  //usuwanie ustawia na date przed wyplata, dla statystyk

    

    $updateMandatoryQuery = "UPDATE mandatory SET expiry = '$dayBeforeLatest' WHERE id = '$mandatoryId'";
    
    if ($conn->query($updateMandatoryQuery) === TRUE) {
         header('Location: index.php');
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
}


if (isset($_POST['saveMandatoryEdit'])){
    $importantDateDiff = $_SESSION['importantDateDiff'];
    $latestDate = $_SESSION['latestDate'];
    $secondLast = $_SESSION['secondLast'];

    $mandatoryAmount = $_POST['mandatoryAmount'];
    $mandatoryCategory = $_POST['mandatoryCategory'];
    $mandatoryCategory = ucfirst($mandatoryCategory);
    $mandatoryId = $_POST['mandatoryId'];
    $mandatoryExpiryChecked = isset($_POST['mandatoryExpiryChecked']) ? 1 : 0;

    if ($mandatoryExpiryChecked) {
        $latestDateType = new DateTime($latestDate); // Konwersja stringa na DateTime
        $latestDateType->modify("+1 month");
        $mandatoryExpiryDate = $latestDateType->format('Y-m-d');
        $addMandatoryDate = $latestDate;
    } else {
        $mandatoryExpiryDate = null;
        $addMandatoryDate = null;
    }

    if (!empty($mandatoryAmount) && !empty($mandatoryCategory)) {
        $sqlInsertMandatory = "UPDATE mandatory SET amount = '$mandatoryAmount', category = '$mandatoryCategory', date = '$todayDate', expiry = '$mandatoryExpiryDate' WHERE id = '$mandatoryId'";
        $conn->query($sqlInsertMandatory);
        $conn->close();
        header('Location: index.php');
    }

}
?>