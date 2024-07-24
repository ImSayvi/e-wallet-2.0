<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e-wallet 2.0";

$conn = new mysqli($servername, $username, $password, $dbname);

// do umieszczenia w tabeli wypłat
$queryIncome = "SELECT * FROM income ORDER BY incomeDate DESC";
$resultIncome = $conn->query($queryIncome);

// do umieszczenia w info 'ostatnia wyplata'

$lastIncome = "SELECT monthlyIncome, incomeDate FROM income ORDER BY incomeDate DESC LIMIT 1";
$lastArray = $conn->query($lastIncome);

if ($lastArray->num_rows > 0) {
    $lastRecord = $lastArray->fetch_assoc();
    $latestIncome = $lastRecord['monthlyIncome'];
    $latestDate = $lastRecord['incomeDate'];
} else {
    $latestIncome = "0";
    $latestDate = null;
}

$_SESSION['latestIncome'] = $latestIncome;

$latestDateType = new DateTime($latestDate); // Konwersja stringa na DateTime
$latestDateType->modify("+1 month");
$mandatoryExpiryDate = $latestDateType->format('Y-m-d');

// do umieszczania w tabeli wplat obowiazkowych oraz w navbarze
$queryMandatory = "SELECT * FROM mandatory WHERE expiry <= '$mandatoryExpiryDate' AND expiry > '$latestDate' OR expiry IS NULL";
$resultMandatory = $conn->query($queryMandatory);
$resultsArray = [];
while ($row = $resultMandatory->fetch_assoc()) {
    $resultsArray[] = $row;
} //zapisanie w tablicy pozwala na wielokrotne uzycie

// podliczanie sumy wydatkow stalych
$totalMandatory = 0;
foreach ($resultsArray as $row) {
    $totalMandatory += $row['amount'];
}

$_SESSION['totalMandatory'] = $totalMandatory;
$todayDate = date('Y-m-d');

//do historii wpłat
$queryDailyIncome = "SELECT * FROM dailytransactions WHERE amount > 0 AND date <= '$mandatoryExpiryDate' AND date > '$latestDate'";
$resultDailyIncome = $conn->query($queryDailyIncome);
$resultDailyIncomeArray = [];
while ($dailyIncRow = $resultDailyIncome->fetch_assoc()) {
    $resultDailyIncomeArray[] = $dailyIncRow;
}

//do podliczania sumy wpłat
$totalDailyIncome = 0;
foreach ($resultDailyIncomeArray as $dailyIncRow) {
    $totalDailyIncome += $dailyIncRow['amount'];
}
$_SESSION['totalDailyIncome'] = $totalDailyIncome;

//do historii wypłat
$queryDailyOutcome = "SELECT * FROM dailytransactions WHERE amount < 0 AND date <= '$mandatoryExpiryDate' AND date > '$latestDate'";
$resultDailyOutcome = $conn->query($queryDailyOutcome);
$resultDailyOutcomeArray = [];
while ($dailyOutRow = $resultDailyOutcome ->fetch_assoc()) {
    $resultDailyOutcomeArray[] = $dailyOutRow;
}

$totalDailyOutcome = 0;
foreach ($resultDailyOutcomeArray as $dailyOutRow) {
    $totalDailyOutcome += $dailyOutRow['amount'];
}
$_SESSION['totalDailyOutcome'] = $totalDailyOutcome;
?>



<!doctype html>
<html lang="pl" data-bs-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-WALLET 2.0</title>
    <link rel="stylesheet" href="style.css" <?php echo time(); ?>>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>

    <!-- MODALE -->
    <!-- modal na dodanie/odejmowanie hajsu z budżetu dizennego -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Tytuł modala</h5>
                    <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="income.php">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="buttonAmount">Kwota</label>
                            <input type="text" class="form-control" id="amountInput" name="amountInput" placeholder="Wprowadź kwotę">
                        </div>

                        <div class="form-group">
                            <label for="category">Kategoria</label>
                            <select class="form-select" id="category" onchange="showInput(this)" name="dailyCategory">
                                <option selected>wybierz kategorie</option>
                                <?php
                                foreach ($resultsArray as $row) {
                                    echo '<option value="' . $row['category'] . '">' . $row['category'] . '</option>';
                                }
                                ?>
                                <option value="other">Inne</option>
                            </select>
                        </div>

                        <div class="form-group otherCategoryDiv" id="otherCategoryDiv">
                            <label for="otherCategory">Inna kategoria</label>
                            <input type="text" class="form-control" id="otherCategory" aria-describedby="otherCategory" placeholder="Wprowadź nazwę kategorii" name="otherCategory">
                        </div>

                        <div class="form-group">
                            <label for="date">Data</label>
                            <input type="date" class="form-control" id="date" name="dailyDate">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                        <button type="submit" class="btn btn-primary" name="save_daily">Wprowadź</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal na dodawanie wydatkow obowiazkowych -->
    <div class="modal fade" id="expensemodal" tabindex="-1" role="dialog" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Dodawanie wydatku obowiązkowego</h5>
                    <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="clearInputs()"></button>
                </div>
                <form method="POST" action="income.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="mandatoryAmount" class="form-label">Kwota</label>
                            <input type="number" class="form-control" id="mandatoryAmount" name="mandatoryAmount">
                        </div>
                        <div class="mb-3">
                            <label for="mandatoryCategory" class="form-label">Na co</label>
                            <input type="text" class="form-control" id="mandatoryCategory" name="mandatoryCategory">
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="mandatoryExpiryChecked" id="flexCheckDefault">
                            <label class="form-check-label" for="flexCheckDefault">
                                Wydatek tylko na ten miesiąc
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary" name="save_mandatory">Wprowadź</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- modal na dodawanie wypłaty -->
    <div class="modal fade" id="paycheckmodal" tabindex="-1" role="dialog" aria-labelledby="paycheckModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Dodawanie wypłaty</h5>
                    <button type="button" class="btn-close" id="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="clearInputs()"></button>
                </div>
                <form method="POST" action="income.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="incomeAmount" class="form-label">Kwota</label>
                            <input type="number" class="form-control" id="incomeAmount" name="incomeAmount">
                        </div>
                        <div class="form-group">
                            <label for="date">Data</label>
                            <input type="date" class="form-control" id="paycheckDate" name="paycheckDate">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary" name="save_income">Wprowadź</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KONIEC MODALI -->



    <div class="container-fluid bg-dark">
        <div class="row flex-nowrap">
            <div class="col-auto col-md-3 col-xl-2 px-0 sticky-top">
                <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100 left-side">
                    <a href="/" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="fa-solid fa-wallet fs-5" style="color: #ffffff;"></i><span class="fs-5 ps-2 d-none d-sm-inline">E-WALLET 2.0</span>
                    </a>
                    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
                        <?php
                        foreach ($resultsArray as $row) {
                            echo '<li class="nav-item">
                                    <a href="#" class="nav-link align-middle px-0">
                                        <i class="fs-4 bi-house"></i> <span class="ms-1 d-none d-sm-inline">' . $row['category'] . ' </span>' . $row['amount'] . ' zł
                                    </a>
                                </li>';
                        }
                        ?>
                    </ul>
                    <hr>
                    <div class="dropdown pb-4">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="https://github.com/mdo.png" alt="hugenerd" width="30" height="30" class="rounded-circle">
                            <span class="d-none d-sm-inline mx-1">loser</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                            <li><a class="dropdown-item" href="#">New project...</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>


            <div class="col py-3 tab-nav delete-ml">

                <nav>
                    <div class="nav nav-tabs nav-justified" id="nav-tab" role="tablist">
                        <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Główna</button>
                        <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Konfiguracja wydatków stałych</button>
                        <button class="nav-link" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false">Wypłata</button>
                    </div>
                </nav>
                <div class="tab-content row-centered" id="nav-tabContent">


                    <!-- STRONA GŁÓWNA -->
                    <div class="tab-pane text-center fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" tabindex="0">
                        <div class="row g-0 justify-content-center main pb-3">

                            <div class="col-1 d-flex align-items-center justify-content-end buttons">
                                <button type="button" class="btn btn-danger same-size-button" id="subMoney" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="changeModalTittle('sub')">-</button>
                            </div>

                            <?php
                            $importantDateDiff = $_SESSION['importantDateDiff'];
                            $daily = ($latestIncome - $totalMandatory) / $importantDateDiff;
                            $dailyRound = round($daily, 0);

                            $dailyTotal = 0;

                            ?>

                            <div class="col-5 col-lg-2 col-md-4 pt-5 leftovers">
                                <p class="mb-0">masz do wydania<br></p>
                                <h3 class="amount"><?php echo $dailyRound; ?><span>zł</span></h3>
                                <div class="info">
                                    <p>dzienny przyrost wydatków:</p>
                                    <p><?php echo $dailyRound; ?> <span>zł</span></p>
                                </div>

                            </div>

                            <div class="col-1 d-flex align-items-center justify-content-start buttons">
                                <button type="button" class="btn btn-success same-size-button" id="addMoney" data-bs-toggle="modal" data-bs-target="#exampleModal" onclick="changeModalTittle('add')">+</button>
                            </div>
                            <div class="offensive">

                            </div>
                        </div>
                        <div class="row history">
                            <div class="col expenses">
                                <h3 class="text-danger">Wydatki</h3>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">Kwota</th>
                                            <th scope="col">Na co</th>
                                            <th scope="col">Kiedy</th>
                                            <th scope="col">Ile zostało</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($resultDailyOutcomeArray as $dailyOutRow) {
                                        echo '<tr>
                                            <td class="text-danger">'.$dailyOutRow['amount'].' zł</td>
                                            <td>'.$dailyOutRow['category'].'</td>
                                            <td>'.$dailyOutRow['date'].'</td>
                                            <td>'.$dailyTotal.'</td>
                                        </tr>';
                                    }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col income">
                                <h3 class="text-success">Wpływy</h3>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">Kwota</th>
                                            <th scope="col">Na co</th>
                                            <th scope="col">Kiedy</th>
                                            <th scope="col">Ile zostało</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($resultDailyIncomeArray as $dailyIncRow) {
                                        echo '<tr>
                                            <td class="text-success">+'.$dailyIncRow['amount'].' zł</td>
                                            <td>'.$dailyIncRow['category'].'</td>
                                            <td>'.$dailyIncRow['date'].'</td>
                                            <td>'.$dailyTotal.'</td>
                                        </tr>';
                                    }
                                    
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- KONFIGURACJA WYDATKÓW -->
                    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
                        <div class="container col col-lg-7 pt-4">
                            <div class="button-container pb-4 d-flex justify-content-center">
                                <button type="button" class="btn btn-primary" id="expenseBtn" data-bs-toggle="modal" data-bs-target="#expensemodal">+ Dodaj wydatek obowiązkowy</button>
                            </div>
                            <div class="info">
                                <h3>Lista wydatków na okres od <?php echo $latestDate; ?> do <?php echo $mandatoryExpiryDate; ?></h3>
                                <p>Suma: <?php echo $totalMandatory ?> zł</p>
                            </div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col">Data wpłaty</th>
                                        <th scope="col">Na co</th>
                                        <th scope="col">Przeznaczone</th>
                                        <th scope="col">Wpłacone</th>
                                        <th scope="col">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php
                                    foreach ($resultsArray as $row) {
                                        $mandatoryModalId = "deleteMandatoryModal" . $row['id'];
                                        $editMandatoryId = "editMandatoryId" . $row['id'];
                                        $textDoModala = ($row['expiry'] === '0000-00-00') ? "?" : " obowiązujący do dnia " . $row['expiry'] . " ?";

                                        echo '<tr>
                                        <td>tu będzie data</td>
                                        <td>' . $row['category'] . '</td>
                                        <td>' . $row['amount'] . ' zł</td>
                                        <td>tu bedzie wpłata</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#' . $editMandatoryId . '"><i class="fas fa-edit"></i></button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#' . $mandatoryModalId . '"><i class="far fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                    

                                    <div class="modal fade" id="' . $editMandatoryId . '" tabindex="-1" aria-labelledby="exampleModalLabel' . $row['id'] . '" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel' . $row['id'] . '">Edycja wydatku obowiązkowego</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST" action="update.php">
                                                        <input type="hidden" name="mandatoryId" value="' . $row['id'] . '">
                                                        <div class="mb-3">
                                                            <label for="mandatoryAmount' . $row['amount'] . '" class="form-label">Kwota</label>
                                                            <input type="number" class="form-control" id="mandatoryAmount' . $row['id'] . '" name="mandatoryAmount" value="' . $row['amount'] . '">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="mandatoryCategory' . $row['category'] . '" class="form-label">Na co</label>
                                                            <input type="text" class="form-control" id="mandatoryCategory' . $row['id'] . '" name="mandatoryCategory" value="' . $row['category'] . '">
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="1" name="mandatoryExpiryChecked" id="flexCheckDefault' . $row['id'] . '" ' . ($row['expiry'] !== '0000-00-00' ? 'checked' : '') . '>
                                                            <label class="form-check-label" for="flexCheckDefault' . $row['id'] . '">
                                                                Wydatek tylko na ten miesiąc
                                                            </label>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                                                            <button type="submit" class="btn btn-primary" name="saveMandatoryEdit">Zapisz zmiany</button>

                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="' . $mandatoryModalId . '" tabindex="-1" aria-labelledby="exampleModalLabel' . $row['id'] . '" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel' . $row['id'] . '">Usuwasz wydatek obowiązkowy!</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Czy na pewno chcesz usunąć wydatek ' . $row['category'] . '' . $textDoModala . '
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                                                    <button type="button" class="btn btn-danger"><a href="update.php?deleteMandatoryId=' . $row['id'] . '" class="text-light">Tak, usuń!</a></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>';
                                    }
                                    ?>

                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- WYPŁATA -->
                    <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab" tabindex="0">
                        <div class="row justify-content-center text-center py-3">
                            <div class="col-5 justify-content-center">
                                <p>Ostatnia wypłata: </p>
                                <h1 class="amount"><?php echo $latestIncome; ?><span> zł</span></h1>
                                <p>z dnia: <?php echo $latestDate; ?></p>
                                <div class="button-container pb-4 d-flex justify-content-center">
                                    <button type="button" class="btn btn-primary" id="paycheck" data-bs-toggle="modal" data-bs-target="#paycheckmodal">+ Dodaj wypłatę</button>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center text-center paycheckHistory">
                            <div class="col-6">
                                <h3>Historia wpłat</h3>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th scope="col">Data</th>
                                            <th scope="col">Kwota</th>
                                            <th scope="col">Usuń</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while ($row = $resultIncome->fetch_assoc()) {
                                            echo '<tr>
                                                <td>' . $row['incomeDate'] . '</td>
                                                <td>' . $row['monthlyIncome'] . ' zł' . '</td>
                                                <td>
                                                    <button type="button" class="btn btn-danger deleteIncome" ><a href="delete.php?deleteid=' . $row['id'] . '" class="text-light"><i class="far fa-trash-alt"></i></a></button>
                                                </td>
                                            </tr>';
                                        }
                                        $recordsAmount = $resultIncome->num_rows;

                                        if ($recordsAmount > 1) {
                                            $sql = "SELECT incomeDate FROM income ORDER BY incomeDate DESC LIMIT 1 OFFSET 1";
                                            $result = $conn->query($sql);
                                            $secondLastRecord = $result->fetch_assoc();
                                            $secondLast = $secondLastRecord['incomeDate'];
                                            $importantDateDiff = dateDiffinDays($latestDate, $secondLast);
                                        } else {
                                            $time = strtotime($latestDate);
                                            $secondLast = date("Y-m-d", strtotime("+1 month", $time));

                                            $importantDateDiff = dateDiffinDays($latestDate, $secondLast);
                                        }
                                        $_SESSION['importantDateDiff'] = $importantDateDiff;  //to pozwala na użycie zmiennej w innym pliku!!!
                                        $_SESSION['latestDate'] = $latestDate;
                                        $_SESSION['secondLast'] = $secondLast;

                                        function dateDiffinDays($date1, $date2)
                                        {
                                            $diff = strtotime($date2) - strtotime($date1);
                                            return abs(round($diff / 86400));
                                        }

                                        ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <script src="https://kit.fontawesome.com/988d321f51.js" crossorigin="anonymous"></script>
        <script src="script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>