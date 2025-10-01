document.addEventListener("DOMContentLoaded", () => {
    // --- Elements ---
    const els = {
        // Desktop
        catalogToggle: document.querySelector("#catalogToggle"),
        catalogDropdown: document.querySelector("#catalogDropdown"),
        catalogOverlay: document.querySelector(".catalog-overlay"),
        avatar: document.getElementById("user-avatar"),
        searchField: document.querySelector(".search-field"),
        searchPanel: document.querySelector(".search-panel"),
        clearButton: document.querySelector(".search-clear-button"),
        themeToggle: document.getElementById("theme-toggle-button"),
        header: document.querySelector(".header"),

        // Mobile
        burgerButton: document.getElementById("burger-button"),
        mobileSidebar: document.getElementById("mobile-sidebar"),
        sidebarClose: document.getElementById("sidebar-close"),
        mobileOverlay: document.getElementById("mobile-overlay"),
        searchToggle: document.getElementById("search-toggle-button"),
        mobileSearchPanel: document.getElementById("mobile-search-panel"),
        searchCancel: document.getElementById("search-cancel"),
        mobileLangToggle: document.getElementById("mobile-lang-toggle")
    };

    const menuWrapper = els.avatar?.closest(".user-menu");

    // --- Catalog functions ---
    const resetCatalogActive = () => {
        els.catalogDropdown?.querySelectorAll(".is-active").forEach(el => el.classList.remove("is-active"));
        els.catalogDropdown?.querySelectorAll(".is-open").forEach(el => el.classList.remove("is-open"));
    };

    const closeAllMainBlocks = () => {
        els.catalogDropdown?.classList.remove("is-open");
        els.catalogToggle?.setAttribute("aria-expanded", "false");
        els.catalogOverlay?.classList.remove("is-active");
        menuWrapper?.classList.remove("active");
        resetCatalogActive();
        updateHeaderActive();
    };

    // --- Desktop Catalog ---
    els.catalogToggle?.addEventListener("click", e => {
        e.stopPropagation();
        const isOpen = !els.catalogDropdown.classList.contains("is-open");
        if (isOpen) menuWrapper?.classList.remove("active");
        els.catalogDropdown.classList.toggle("is-open", isOpen);
        els.catalogOverlay?.classList.toggle("is-active", isOpen);
        els.catalogToggle.setAttribute("aria-expanded", isOpen);

        if (isOpen) {
            resetCatalogActive();
            const firstMain = els.catalogDropdown.querySelector(".catalog-main__item");
            const firstSub = els.catalogDropdown.querySelector(".catalog-subcategories__item");
            firstMain?.classList.add("is-active");
            firstSub?.classList.add("is-active");
        }

        setTimeout(updateHeaderActive, 0);
    });

    els.catalogOverlay?.addEventListener("click", closeAllMainBlocks);

    els.catalogDropdown?.addEventListener("mouseover", e => {
        const item = e.target.closest(".catalog-main__item");
        if (!item) return;
        const id = item.dataset.category;
        els.catalogDropdown.querySelectorAll(".catalog-main__item")
            .forEach(i => i.classList.toggle("is-active", i === item));
        els.catalogDropdown.querySelectorAll(".catalog-subcategories__item")
            .forEach(sub => sub.classList.toggle("is-active", sub.dataset.category === id));
        els.catalogDropdown.querySelectorAll(".submenu-grandchildren").forEach(g => g.classList.remove("is-open"));
    });

    els.catalogDropdown?.addEventListener("click", e => {
        const toggle = e.target.closest(".submenu-title a");
        if (!toggle) return;
        e.preventDefault();

        const parent = toggle.closest(".submenu-block");
        const grandchildren = parent?.querySelector(".submenu-grandchildren");
        if (!grandchildren) return;

        els.catalogDropdown.querySelectorAll(".submenu-grandchildren").forEach(g => g !== grandchildren && g.classList.remove("is-open"));
        els.catalogDropdown.querySelectorAll(".submenu-title").forEach(t => t !== toggle.parentElement && t.classList.remove("open"));

        grandchildren.classList.toggle("is-open");
        toggle.parentElement.classList.toggle("open");
    });

    // --- User menu ---
    els.avatar?.addEventListener("click", e => {
        e.stopPropagation();
        const isOpen = !menuWrapper.classList.contains("active");
        if (isOpen) closeAllMainBlocks();
        menuWrapper.classList.toggle("active", isOpen);
        setTimeout(updateHeaderActive, 0);
    });

    // --- Global click close ---
    document.addEventListener("click", e => {
        if (!e.target.closest(".catalog-wrapper, .user-menu, .search-panel")) {
            closeAllMainBlocks();
        }
        setTimeout(updateHeaderActive, 0);
    });

    // --- Search Desktop ---
    const updateSearchState = () => {
        if (!els.searchField || !els.searchPanel) return;
        closeAllMainBlocks();
        const hasValue = els.searchField.value.trim() !== "";
        els.searchPanel.classList.toggle("search-active", hasValue);
        els.searchField.classList.toggle("search-active", hasValue);
    };
    els.searchField?.addEventListener("input", updateSearchState);
    els.clearButton?.addEventListener("click", () => {
        if (!els.searchField) return;
        els.searchField.value = "";
        updateSearchState();
        els.searchField.focus();
    });
    updateSearchState();

    // --- Theme toggle ---
    const getCookie = name => document.cookie.match(new RegExp(`(^| )${name}=([^;]+)`))?.[2] || null;
    const setTheme = theme => {
        document.documentElement.setAttribute("data-theme", theme);
        document.cookie = `theme=${theme};path=/;max-age=${30 * 24 * 60 * 60}`;
    };
    if (els.themeToggle) {
        let currentTheme = getCookie("theme") || document.documentElement.getAttribute("data-theme") || "light";
        setTheme(currentTheme);
        els.themeToggle.addEventListener("click", () => {
            const newTheme = document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
            setTheme(newTheme);
        });
    }

    // --- Header scroll & active ---
    window.addEventListener("scroll", () => {
        els.header.classList.toggle("header-scrolled", window.scrollY > 50);
    });
    const updateHeaderActive = () => {
        const isActive = (els.catalogDropdown?.classList.contains("is-open") || 
                          menuWrapper?.classList.contains("active"));
        els.header.classList.toggle("header-active", isActive);
    };

    // --- Mobile Sidebar ---
    els.burgerButton?.addEventListener("click", () => {
        els.mobileSidebar?.classList.add("open");
        els.mobileOverlay?.classList.add("active");
    });
    els.sidebarClose?.addEventListener("click", () => {
        els.mobileSidebar?.classList.remove("open");
        els.mobileOverlay?.classList.remove("active");
    });
    els.mobileOverlay?.addEventListener("click", () => {
        els.mobileSidebar?.classList.remove("open");
        els.mobileOverlay?.classList.remove("active");
    });

    // --- Mobile Search ---
    els.searchToggle?.addEventListener("click", () => {
        els.mobileSearchPanel?.classList.add("active");
    });
    els.searchCancel?.addEventListener("click", () => {
        els.mobileSearchPanel?.classList.remove("active");
    });

    // --- Mobile Language Switcher (если есть) ---
    els.mobileLangToggle?.addEventListener("click", () => {
        els.mobileLangToggle.classList.toggle("active");
        // здесь можно добавить логику смены языка
    });
});
