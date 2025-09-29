document.addEventListener("DOMContentLoaded", () => {
    // Main elements
    const catalogToggle = document.querySelector("#catalogToggle");
    const catalogDropdown = document.querySelector("#catalogDropdown");
    const catalogOverlay = document.querySelector(".catalog-overlay");
    const mainItems = catalogDropdown?.querySelectorAll(".catalog-main__item") || [];
    const subcategories = catalogDropdown?.querySelectorAll(".catalog-subcategories__item") || [];

    const avatar = document.getElementById("user-avatar");
    const menuWrapper = avatar?.closest(".user-menu");

    // Reset functions
    const resetCatalogActive = () => {
        mainItems.forEach(item => item.classList.remove("is-active"));
        subcategories.forEach(sub => sub.classList.remove("is-active"));
    };
    const closeAllSubmenus = () => {
        catalogDropdown.querySelectorAll(".submenu-grandchildren").forEach(g => g.classList.remove("is-open"));
        catalogDropdown.querySelectorAll(".submenu-title").forEach(t => t.classList.remove("open"));
    };

    // Universal close all
    const closeAllMainBlocks = () => {
        // Close catalog
        catalogDropdown.classList.remove("is-open");
        catalogToggle.setAttribute("aria-expanded", "false");
        catalogOverlay?.classList.remove("is-active");
        resetCatalogActive();
        closeAllSubmenus();

        // Close user menu
        menuWrapper?.classList.remove("active");
    };

    // Catalog toggle
    if (catalogToggle && catalogDropdown) {
        catalogToggle.addEventListener("click", e => {
            e.stopPropagation();
            const isOpen = !catalogDropdown.classList.contains("is-open");

            // Close user menu if opening catalog
            if (isOpen) menuWrapper?.classList.remove("active");

            catalogDropdown.classList.toggle("is-open", isOpen);
            catalogToggle.setAttribute("aria-expanded", isOpen);
            catalogOverlay?.classList.toggle("is-active", isOpen);

            if (isOpen) {
                resetCatalogActive();
                closeAllSubmenus();
                if (mainItems[0]) mainItems[0].classList.add("is-active");
                if (subcategories[0]) subcategories[0].classList.add("is-active");
            }
        });

        catalogOverlay?.addEventListener("click", closeAllMainBlocks);
    }

    // Main items hover
    mainItems.forEach(item => {
        item.addEventListener("mouseover", () => {
            const id = item.dataset.category;
            mainItems.forEach(i => i.classList.toggle("is-active", i === item));
            subcategories.forEach(sub => sub.classList.toggle("is-active", sub.dataset.category === id));
            closeAllSubmenus();
        });
    });

    // Submenu toggle
    catalogDropdown.querySelectorAll(".submenu-title a").forEach(toggle => {
        toggle.addEventListener("click", e => {
            e.preventDefault();
            const parent = toggle.closest(".submenu-block");
            const grandchildren = parent?.querySelector(".submenu-grandchildren");
            if (grandchildren) {
                catalogDropdown.querySelectorAll(".submenu-grandchildren").forEach(g => {
                    if (g !== grandchildren) g.classList.remove("is-open");
                });
                catalogDropdown.querySelectorAll(".submenu-title").forEach(t => {
                    if (t !== toggle.parentElement) t.classList.remove("open");
                });
                grandchildren.classList.toggle("is-open");
                toggle.parentElement.classList.toggle("open");
            }
        });
    });

    // Hover grandchildren links
    catalogDropdown.querySelectorAll(".submenu-grandchildren li a").forEach(link => {
        link.addEventListener("mouseover", () => link.classList.add("is-hovered"));
        link.addEventListener("mouseout", () => link.classList.remove("is-hovered"));
    });

    // User avatar menu
    if (avatar && menuWrapper) {
        avatar.addEventListener("click", e => {
            e.stopPropagation();
            const isOpen = !menuWrapper.classList.contains("active");

            // Close catalog if opening user menu
            if (isOpen) closeAllSubmenus(), catalogDropdown.classList.remove("is-open"), catalogOverlay?.classList.remove("is-active"), catalogToggle.setAttribute("aria-expanded", "false");

            menuWrapper.classList.toggle("active", isOpen);
        });
    }

    // Document click
    document.addEventListener("click", e => {
        if (!e.target.closest(".catalog-wrapper") &&
            !e.target.closest(".user-menu") &&
            !e.target.closest(".search-panel")) {
            closeAllMainBlocks();
        }
    });

    // Search field
    const searchField = document.querySelector(".search-field");
    const searchPanel = document.querySelector(".search-panel");
    const clearButton = document.querySelector(".search-clear-button");

    const updateSearchState = () => {
        if (!searchField || !searchPanel) return;

        // Close all main blocks when typing
        closeAllMainBlocks();

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

    // Theme toggle
    const themeToggleBtn = document.getElementById("theme-toggle-button");
    const getCookie = name => document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'))?.[2] || null;
    const setTheme = theme => {
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
});
