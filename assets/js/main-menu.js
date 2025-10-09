document.addEventListener("DOMContentLoaded", () => {
    const els = {
        // Desktop
        catalogToggle: document.querySelector("#catalogToggle"),
        catalogDropdown: document.querySelector("#catalogDropdown"),
        catalogOverlay: document.querySelector(".catalog-overlay"),
        avatar: document.getElementById("user-avatar"),
        searchField: document.querySelector("#search-field"),
        searchPanel: document.querySelector(".search-panel"),
        searchSuggestions: document.getElementById("search-suggestions"),
        clearButton: document.getElementById("clear-search"),
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

    // Remove active/open classes inside catalog
    const resetCatalogActive = () => {
        els.catalogDropdown?.querySelectorAll(".is-active").forEach(el => el.classList.remove("is-active"));
        els.catalogDropdown?.querySelectorAll(".is-open").forEach(el => el.classList.remove("is-open"));
    };

    // Update header state based on open blocks
    const updateHeaderActive = () => {
        const isActive = (els.catalogDropdown?.classList.contains("is-open") || menuWrapper?.classList.contains("active"));
        els.header.classList.toggle("header-active", isActive);
    };

    // Close catalog & user menu
    const closeCatalogAndMenu = () => {
        els.catalogDropdown?.classList.remove("is-open");
        els.catalogToggle?.setAttribute("aria-expanded", "false");
        menuWrapper?.classList.remove("active");
        document.body.classList.remove("body-lock");
        resetCatalogActive();
        updateHeaderActive();
    };

    // Catalog toggle (desktop)
    els.catalogToggle?.addEventListener("click", e => {
        e.stopPropagation();

        els.searchPanel?.classList.remove("search-active");
        els.searchField?.classList.remove("search-active");
        els.searchSuggestions?.classList.remove("active");

        const isOpen = !els.catalogDropdown.classList.contains("is-open");
        if (isOpen) closeCatalogAndMenu();
        els.catalogDropdown.classList.toggle("is-open", isOpen);
        els.catalogOverlay?.classList.toggle("is-active", isOpen);
        els.catalogToggle.setAttribute("aria-expanded", isOpen);

        if (isOpen) {
            resetCatalogActive();
            const firstMain = els.catalogDropdown.querySelector(".catalog-main__item");
            const firstSub = els.catalogDropdown.querySelector(".catalog-subcategories__item");
            firstMain?.classList.add("is-active");
            firstSub?.classList.add("is-active");
            document.body.classList.add("body-lock");
        } else {
            document.body.classList.remove("body-lock");
        }

        setTimeout(updateHeaderActive, 0);
    });

    // Overlay click
    els.catalogOverlay?.addEventListener("click", () => {
        closeCatalogAndMenu();
        els.searchSuggestions?.classList.remove("active");
        if (!els.catalogDropdown?.classList.contains("is-open") && !menuWrapper?.classList.contains("active")) {
            els.catalogOverlay?.classList.remove("is-active");
        }
    });

    // Catalog hover/submenu
    els.catalogDropdown?.addEventListener("mouseover", e => {
        const item = e.target.closest(".catalog-main__item");
        if (!item) return;
        const id = item.dataset.category;
        els.catalogDropdown.querySelectorAll(".catalog-main__item").forEach(i => i.classList.toggle("is-active", i === item));
        els.catalogDropdown.querySelectorAll(".catalog-subcategories__item").forEach(sub => sub.classList.toggle("is-active", sub.dataset.category === id));
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

    // User menu toggle
    els.avatar?.addEventListener("click", e => {
        e.stopPropagation();
        const isOpen = !menuWrapper.classList.contains("active");
        if (isOpen) closeCatalogAndMenu();
        menuWrapper.classList.toggle("active", isOpen);
        setTimeout(updateHeaderActive, 0);
    });

    // Global click to close
    document.addEventListener("click", e => {
        if (!e.target.closest(".catalog-wrapper, .user-menu, .search-panel, .search-form, .search-suggestions")) {
            closeCatalogAndMenu();
            if (els.searchSuggestions) {
                els.searchSuggestions.classList.remove("active");
            }
            if (!els.catalogDropdown?.classList.contains("is-open") && !menuWrapper?.classList.contains("active")) {
                els.catalogOverlay?.classList.remove("is-active");
            }
        }
        setTimeout(updateHeaderActive, 0);
    });

    // Search logic
    let searchTimer;
    const loaderTemplate = document.querySelector("#loader-template");

    const updateSearchState = () => {
        if (!els.searchField || !els.searchPanel) return;
        const hasValue = els.searchField.value.trim() !== "";
    
        // Toggle search button disabled
        const searchButton = document.querySelector(".search-icon-button");
        if (searchButton) {
            searchButton.disabled = !hasValue;
        }
    
        if (hasValue) {
            els.catalogDropdown?.classList.remove("is-open");
            els.catalogToggle?.setAttribute("aria-expanded", "false");
            resetCatalogActive();
            menuWrapper?.classList.remove("active");
            document.body.classList.remove("body-lock");
        
            els.searchPanel.classList.add("search-active");
            els.searchField.classList.add("search-active");
            els.catalogOverlay?.classList.add("is-active");
        } else {
            els.searchPanel.classList.remove("search-active");
            els.searchField.classList.remove("search-active");
            els.searchSuggestions?.querySelectorAll(".search-section").forEach(sec => sec.remove());
            els.searchSuggestions?.querySelectorAll(".loader-box").forEach(loader => loader.style.display = "none");
        
            if (!els.catalogDropdown?.classList.contains("is-open") && !menuWrapper?.classList.contains("active")) {
                els.catalogOverlay?.classList.remove("is-active");
            }
        }
    
        updateHeaderActive();
    };


    if (els.searchField && els.searchSuggestions) {
        els.searchField.addEventListener("input", () => {
            const q = els.searchField.value.trim();
            updateSearchState();

            els.searchSuggestions.querySelectorAll(".search-section").forEach(sec => sec.remove());
            els.searchSuggestions.querySelectorAll(".loader-box").forEach(loader => loader.style.display = "flex");
            els.searchSuggestions.classList.add("active");

            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                fetch(`${ajax_object.ajaxurl}?action=search_products&q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(res => {
                        els.searchSuggestions.querySelectorAll(".search-section").forEach(sec => sec.remove());
                        els.searchSuggestions.querySelectorAll(".loader-box").forEach(loader => loader.style.display = "none");

                        if (!res.success) {
                            els.searchSuggestions.classList.remove("active");
                            if (!els.catalogDropdown?.classList.contains("is-open") && !menuWrapper?.classList.contains("active")) {
                                els.catalogOverlay?.classList.remove("is-active");
                            }
                            return;
                        }

                        const data = res.data;
                        const createSection = (title, itemsHtml) => {
                            if (!itemsHtml || itemsHtml.length === 0) return "";
                            return `
                                <li class="search-section">
                                    <div class="search-section-title title-medium">${title}</div>
                                    <ul class="search-section-items">${itemsHtml}</ul>
                                </li>
                            `;
                        };

                        // Categories
                        const categoriesHtml = data.categories?.map(cat =>
                            `<li class="search-suggestion-item body-small-regular category">
                                <a href="${cat.link}">
                                    ${ajax_object.icons.category}
                                    ${cat.name}
                                </a>
                            </li>`
                        ).join("");
                        if (categoriesHtml) els.searchSuggestions.insertAdjacentHTML("beforeend", createSection("Категории", categoriesHtml));

                        // Users
                        const usersHtml = data.users?.map(user =>
                            `<li class="search-suggestion-item body-small-regular user">
                                <a href="${user.link}">
                                    ${ajax_object.icons.user}
                                    ${user.name}
                                </a>
                            </li>`
                        ).join("");
                        if (usersHtml) els.searchSuggestions.insertAdjacentHTML("beforeend", createSection("Пользователи", usersHtml));

                        // Popular queries
                        const popularHtml = data.popular_queries?.map(q =>
                            `<li class="search-suggestion-item body-small-regular popular"><span>🔍 ${q}</span></li>`
                        ).join("");
                        if (popularHtml) els.searchSuggestions.insertAdjacentHTML("beforeend", createSection("Популярное", popularHtml));

                        // Products
                        if (data.products_html) {
                            els.searchSuggestions.insertAdjacentHTML("beforeend", createSection("Товары", data.products_html));
                        }

                        els.searchSuggestions.classList.add("active");
                        els.catalogOverlay?.classList.add("is-active");
                    })
                    .catch(() => {
                        els.searchSuggestions.querySelectorAll(".loader-box").forEach(loader => loader.style.display = "none");
                        els.searchSuggestions.insertAdjacentHTML("beforeend", '<li class="loader">Ошибка загрузки</li>');
                    });
            }, 250);
        });

        // Clear button
        els.clearButton?.addEventListener("click", () => {
            els.searchField.value = "";
            els.searchSuggestions.querySelectorAll(".search-section").forEach(sec => sec.remove());
            els.searchSuggestions.querySelectorAll(".loader-box").forEach(loader => loader.style.display = "none");
            els.searchSuggestions.classList.remove("active");
        
            // Just update state (this disables button properly)
            updateSearchState();
        
            // Ensure search button stays visible
            const searchButton = document.querySelector(".search-icon-button");
            if (searchButton) {
                searchButton.disabled = true;
                searchButton.style.removeProperty("display");
            }
        
            els.searchField.focus();
        });

        // Click outside to close
        document.addEventListener("click", e => {
            if (!e.target.closest(".search-form, .search-suggestions")) {
                els.searchSuggestions.classList.remove("active");
                if (!els.catalogDropdown?.classList.contains("is-open") && !menuWrapper?.classList.contains("active")) {
                    els.catalogOverlay?.classList.remove("is-active");
                }
            }
        });
    }

    // Initial state
    updateSearchState();

    // Theme toggle
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

    // Header scroll
    window.addEventListener("scroll", () => {
        els.header.classList.toggle("header-scrolled", window.scrollY > 50);
    });

    // Mobile Sidebar
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

    // Mobile Search
    els.searchToggle?.addEventListener("click", () => {
        els.mobileSearchPanel?.classList.add("active");
    });
    els.searchCancel?.addEventListener("click", () => {
        els.mobileSearchPanel?.classList.remove("active");
    });

    // Mobile Language Switcher
    els.mobileLangToggle?.addEventListener("click", () => {
        els.mobileLangToggle.classList.toggle("active");
    });
});
