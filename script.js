// const hamBurger = document.querySelector(".toggle-btn");

// hamBurger.addEventListener("click", function () {
//   document.querySelector("#sidebar").classList.toggle("expand");
// });



// funkcja do pokazywania pola na wprowadzenie nazwy innej kategorii
function showInput(answer){
  if (answer.value == "other"){
    document.getElementById("otherCategoryDiv").style.display = "block";
  } else{
    document.getElementById("otherCategoryDiv").style.display = "none";
  }
}


//funckja do pokazywania pola na wprowadzanie miesiaca obowiazywania wydatku obowiazkowego
function showSelect(checkbox){
  if (checkbox.checked) {
    mandatoryDate.style.display = "block";
} else {
  mandatoryDate.style.display = "none";
}
}

// funkcja do zmiany tytułu modala, dodania minusa i zapobieganiu usuniecia go
let modalTittle = document.getElementById("exampleModalLabel");
let modalamountInput = document.getElementById("amountInput");
let initialMinus = false;

function changeModalTittle(action){
    if(action === 'add'){
        modalTittle.innerText = "Dodawanie pieniędzy do budżetu dziennego";
        modalamountInput.value = "";
        initialMinus = false;
    }
    if(action === 'sub'){
        modalTittle.innerText = "Odejmowanie pieniędzy z budżetu dziennego";
        modalamountInput.value = "-";
        initialMinus = true;
    }
}

modalamountInput.addEventListener('keydown', function(event) {
    if (initialMinus && (event.key === 'Backspace' || event.key === 'Delete')) {
        if (modalamountInput.value === '-') {
            event.preventDefault();
        }
    }
});



// działanie przycisku 'close' w modalu
document.getElementById("btn-close").addEventListener("click", clearInputs);

// funkcja do ustawiania daty na dzisiejsza
function setTodayDate() {
    let today = new Date().toISOString().split('T')[0];
    return today;
}


// doprowadzanie modala do pierwotnego stanu
function clearInputs() {
    document.getElementById("amountInput").value = "";
    document.getElementById("category").value = "wybierz kategorie";
    document.getElementById("otherCategory").value = "";
    document.getElementById("date").value = setTodayDate();
    document.getElementById("otherCategoryDiv").style.display = "none";
    document.getElementById("feeAmount").value = "";
    document.getElementById("whatFor").value = "";
    
}

// wywolanie funkcji do zmiany daty na dzisiejsza
window.onload = function() {
    document.getElementById("date").value = setTodayDate();
};

