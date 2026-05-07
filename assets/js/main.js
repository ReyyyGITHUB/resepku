document.addEventListener("DOMContentLoaded", function () {
    var firstInput = document.querySelector(".login-form__input");

    if (firstInput) {
        firstInput.addEventListener("invalid", function () {
            firstInput.classList.add("is-invalid");
        });
    }

    var guestMode = document.body.dataset.guestMode === "1";
    var guestModal = document.querySelector("[data-guest-modal]");
    var guestTriggers = document.querySelectorAll("[data-guest-gate]");

    function openGuestModal() {
        if (!guestModal) {
            return;
        }

        guestModal.classList.add("is-open");
        document.body.classList.add("modal-open");
    }

    function closeGuestModal() {
        if (!guestModal) {
            return;
        }

        guestModal.classList.remove("is-open");
        document.body.classList.remove("modal-open");
    }

    guestTriggers.forEach(function (trigger) {
        trigger.addEventListener("click", function (event) {
            if (guestMode && guestModal) {
                event.preventDefault();
                openGuestModal();
                return;
            }

            if (trigger.hasAttribute("data-action-toggle")) {
                trigger.classList.toggle("is-active");
            }
        });
    });

    if (guestModal) {
        guestModal.addEventListener("click", function (event) {
            if (event.target.hasAttribute("data-guest-close") || event.target.closest("[data-guest-close]")) {
                closeGuestModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeGuestModal();
            }
        });
    }
});
