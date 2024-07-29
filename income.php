<?php
session_start(); 


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-wallet 2.0";

$conn = new mysqli($servername, $username, $password, $dbname);
$showError = $_SESSION['showError'] ;

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
        $mandatoryIcon = $_POST['icon'];

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
            $sqlInsertMandatory = "INSERT INTO mandatory (amount, category, date, expiry, icon) VALUES ('$mandatoryAmount', '$mandatoryCategory', '$addMandatoryDate', '$mandatoryExpiryDate', '$mandatoryIcon')";
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
    $dailyCategory = $_POST['dailyCategory'] ?? null; //dzieki temu, jesli nie wyslane, ustawi null
    $dailyDate = $_POST['dailyDate'];
    $leftovers = $_POST['leftovers'];

    
    if($dailyCategory === null){
        $dailyCategory = $_POST['otherCategory'];
    }
    

    if (empty($amountInput) || $amountInput === "-" || $dailyCategory === 'wybierz kategorie' || empty($dailyDate)) {
        $_SESSION['showError'] = "Uzupełnij wszystkie pola XD";
       header('Location: index.php');
        exit;
    } else {
        $dailyCategory = ucfirst($dailyCategory);
        $sqlInsertDaily = "INSERT INTO dailytransactions (date, amount, category, leftovers) VALUES ('$dailyDate', '$amountInput', '$dailyCategory','$leftovers')";
        $conn->query($sqlInsertDaily);
        $conn->close();
        $_SESSION['showError'] = false;
        header('Location: index.php');
        exit;
    }

    
    
}