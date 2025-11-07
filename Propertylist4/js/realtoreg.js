export function showSnackbar(message, type = "error") {
    const container = document.getElementById("snackbar-container");

    const snackbar = document.createElement("div");
    snackbar.classList.add("snackbar");

    if (type === "success") {
        snackbar.style.backgroundColor = "#4CAF50"; // Green
    } else {
        snackbar.style.backgroundColor = "#f44336"; // Red
    }

    snackbar.textContent = message;
    container.appendChild(snackbar);

    setTimeout(() => {
        snackbar.remove();
    }, 4000);
}
