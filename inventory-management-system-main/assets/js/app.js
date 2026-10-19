document.addEventListener("DOMContentLoaded", function () {

    const alertBox = document.querySelector(".alert");

    if (alertBox) {
        setTimeout(function () {
            alertBox.style.transition = "opacity 0.4s ease";
            alertBox.style.opacity = "0";
            setTimeout(function () {
                alertBox.remove();
            }, 400);
        }, 4000);
    }

});