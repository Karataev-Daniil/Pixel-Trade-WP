document.addEventListener("DOMContentLoaded", () => {
    // Catalog Dropdown
    const catalogToggle = document.querySelector("#catalogToggle");
    const catalogDropdown = document.querySelector("#catalogDropdown");
    const catalogOverlay = document.querySelector(".catalog-overlay");
    const mainItems = catalogDropdown?.querySelectorAll(".catalog-main__item") || [];
    const subcategories = catalogDropdown?.querySelectorAll(".catalog-subcategories__item") || [];

    const resetCatalogActive = () => {
        mainItems.forEach(item => item.classList.remove("is-active"));
        subcategories.forEach(sub => sub.classList.remove("is-active"));
    };

    if (catalogToggle && catalogDropdown) {
        catalogToggle.addEventListener("click", e => {
            e.stopPropagation();
            const isOpen = catalogDropdown.classList.toggle("is-open");
            catalogToggle.setAttribute("aria-expanded", isOpen);
            catalogOverlay?.classList.toggle("is-active", isOpen);

            if (isOpen) {
                resetCatalogActive();
                if (mainItems[0]) mainItems[0].classList.add("is-active");
                if (subcategories[0]) subcategories[0].classList.add("is-active");
            }
        });

        document.addEventListener("click", e => {
            if (!e.target.closest(".catalog-wrapper")) {
                catalogDropdown.classList.remove("is-open");
                catalogToggle.setAttribute("aria-expanded", "false");
                catalogOverlay?.classList.remove("is-active");
                resetCatalogActive();
            }
        });

        catalogOverlay?.addEventListener("click", () => {
            catalogDropdown.classList.remove("is-open");
            catalogToggle.setAttribute("aria-expanded", "false");
            catalogOverlay.classList.remove("is-active");
            resetCatalogActive();
        });

        mainItems.forEach(item => {
            item.addEventListener("mouseover", () => {
                const id = item.dataset.category;
                mainItems.forEach(i => i.classList.toggle("is-active", i === item));
                subcategories.forEach(sub => sub.classList.toggle("is-active", sub.dataset.category === id));
            });
        });

        // Submenu toggles
        catalogDropdown.querySelectorAll(".submenu-title a").forEach(toggle => {
            toggle.addEventListener("click", e => {
                const parent = toggle.closest(".submenu-block");
                const grandchildren = parent?.querySelector(".submenu-grandchildren");
                if (grandchildren) {
                    e.preventDefault();
                    grandchildren.classList.toggle("is-open");
                    toggle.parentElement.classList.toggle("open");
                }
            });
        });

        // Hover effect for grandchildren links
        catalogDropdown.querySelectorAll(".submenu-grandchildren li a").forEach(link => {
            link.addEventListener("mouseover", () => link.classList.add("is-hovered"));
            link.addEventListener("mouseout", () => link.classList.remove("is-hovered"));
        });
    }

    // Theme Toggle
    const themeToggleBtn = document.getElementById("theme-toggle-button");

    const getCookie = (name) => {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    };

    const setTheme = (theme) => {
        document.documentElement.setAttribute("data-theme", theme);
        document.cookie = `theme=${theme};path=/;max-age=${30*24*60*60}`;
    };

    if (themeToggleBtn) {
        let currentTheme = getCookie("theme") || document.documentElement.getAttribute("data-theme") || "light";
        setTheme(currentTheme);

        themeToggleBtn.addEventListener("click", () => {
            const newTheme = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
            setTheme(newTheme);
        });
    }

    // Search Field
    const searchField = document.querySelector(".search-field");
    const searchPanel = document.querySelector(".search-panel");
    const clearButton = document.querySelector(".search-clear-button");

    const updateSearchState = () => {
        if (!searchField || !searchPanel) return;
        if (searchField.value.trim() !== '') {
            searchPanel.classList.add("has-content");
            searchPanel.style.top = "28px";
            searchField.style.paddingTop = "12px";
            searchField.style.paddingBottom = "12px";
            searchField.style.borderRadius = "12px";
        } else {
            searchPanel.classList.remove("has-content");
            searchPanel.style.top = "0px";
            searchField.style.paddingTop = "4px";
            searchField.style.paddingBottom = "4px";
            searchField.style.borderRadius = "6px";
        }
    };

    searchField?.addEventListener("input", updateSearchState);
    clearButton?.addEventListener("click", () => {
        if (!searchField) return;
        searchField.value = '';
        updateSearchState();
        searchField.focus();
    });
    updateSearchState();

    // User Avatar Menu
    const avatar = document.getElementById("user-avatar");
    const menuWrapper = avatar?.closest(".user-menu");

    if (avatar && menuWrapper) {
        avatar.addEventListener("click", e => {
            e.stopPropagation();
            menuWrapper.classList.toggle("active");
        });

        document.addEventListener("click", () => {
            menuWrapper.classList.remove("active");
        });

        // Hover on username
        const username = document.querySelector(".user-name");
        username?.addEventListener("mouseenter", () => {
            avatar.style.borderColor = "var(--orange_0)";
            avatar.style.transform = "scale(1.05)";
        });
        username?.addEventListener("mouseleave", () => {
            avatar.style.borderColor = "rgba(255, 255, 255, 0.2)";
            avatar.style.transform = "scale(1)";
        });
    }
});
