<?php
session_start(); 


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-wallet 2.0";

$conn = new mysqli($servername, $username, $password, $dbname);


if (isset($_POST['save_income'])){
    $incomeAmount = $_POST['incomeAmount'];
    $incomeDate= $_POST['paycheckDate'];
    if(!empty($incomeAmount) && !empty($incomeDate)){
        $sqlInsert = "INSERT INTO income (monthlyIncome, incomeDate) VALUES ('$incomeAmount', '$incomeDate')";
        $conn-> query($sqlInsert);
        $conn->close();

        header('location: index.php');
    } 
}




if (isset($_SESSION['importantDateDiff'])) {
    $importantDateDiff = $_SESSION['importantDateDiff'];
    $latestDate = $_SESSION['latestDate'];
    $secondLast = $_SESSION['secondLast'];

    if (isset($_POST['save_mandatory'])) {
        $mandatoryAmount = $_POST['mandatoryAmount'];
        $mandatoryCategory = $_POST['mandatoryCategory'];
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
            $sqlInsertMandatory = "INSERT INTO mandatory (amount, category, date, expiry) VALUES ('$mandatoryAmount', '$mandatoryCategory', '$addMandatoryDate', '$mandatoryExpiryDate')";
            $conn->query($sqlInsertMandatory);
            $conn->close();
            header('Location: index.php');
        }
    }
} else {
    echo "Zmiennej 'importantDateDiff' nie ustawiono.";
}


if(isset($_POST['save_daily'])){
    $amountInput = $_POST['amountInput'];
    $dailyCategory = $_POST['dailyCategory'];
    $dailyDate = $_POST['dailyDate'];

    if($dailyCategory === 'other'){
        $dailyCategory = $_POST['otherCategory'];
    }

    if (!empty($amountInput) && !empty($dailyCategory) && !empty($dailyDate)){
        $sqlInsertDaily = "INSERT INTO dailytransactions (date, amount, category) VALUES ('$dailyDate', '$amountInput', '$dailyDate')";
        $conn->query($sqlInsertDaily);
        $conn->close();
        header('Location: index.php');
    }
}