document.addEventListener("DOMContentLoaded", function () {
    var firstInput = document.querySelector(".login-form__input");

    if (firstInput) {
        firstInput.addEventListener("invalid", function () {
            firstInput.classList.add("is-invalid");
        });
    }
});
