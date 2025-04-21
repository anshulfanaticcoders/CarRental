// export default function applyGlobalValidation() {
//     document.addEventListener("input", function (event) {
//         if (event.target.matches("input[type='number'], input[data-type='number']")) {
//             event.target.value = event.target.value.replace(/[^0-9.]/g, "");
            
//             // Prevent multiple decimal points
//             const parts = event.target.value.split(".");
//             if (parts.length > 2) {
//                 event.target.value = parts[0] + "." + parts.slice(1).join("");
//             }
//         }
//     });
// }

