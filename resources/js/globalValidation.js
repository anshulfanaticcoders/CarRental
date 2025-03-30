export default function applyGlobalValidation() {
    document.addEventListener("input", function (event) {
        if (event.target.matches("input[type='number'], input[data-type='number']")) {
            event.target.value = event.target.value.replace(/\D/g, ""); // Remove non-numeric characters
        }
    });
}
