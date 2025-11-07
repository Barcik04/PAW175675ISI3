document.addEventListener("DOMContentLoaded", () => {
    const currentPage = location.pathname.split("/").pop();

    document.querySelectorAll(".navbar a").forEach(link => {
        const href = link.getAttribute("href");
        if (href === currentPage || (href === "home.html" && currentPage === "")) {
            link.classList.add("active");
        }
    });
});
