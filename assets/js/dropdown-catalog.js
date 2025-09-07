document.addEventListener("DOMContentLoaded", () => {
            const toggleBtn = document.querySelector("#catalogToggle");
            const dropdown = document.querySelector("#catalogDropdown");
            const overlay = document.querySelector(".catalog-overlay");
            const mainItems = dropdown.querySelectorAll(".catalog-main__item");
            const subcategories = dropdown.querySelectorAll(".catalog-subcategories__item");

            const resetActive = () => {
                mainItems.forEach(item => item.classList.remove("is-active"));
                subcategories.forEach(sub => sub.classList.remove("is-active"));
            };
          
            toggleBtn.addEventListener("click", e => {
                e.stopPropagation();
                const isOpen = dropdown.classList.toggle("is-open");
                toggleBtn.setAttribute("aria-expanded", isOpen);
                overlay.classList.toggle("is-active", isOpen);
            
                if (isOpen) {
                    resetActive();
                    if (mainItems[0] && subcategories[0]) {
                        mainItems[0].classList.add("is-active");
                        subcategories[0].classList.add("is-active");
                    }
                }
            });
          
            document.addEventListener("click", e => {
                if (!e.target.closest(".catalog-wrapper")) {
                    dropdown.classList.remove("is-open");
                    toggleBtn.setAttribute("aria-expanded", "false");
                    overlay.classList.remove("is-active");
                    resetActive();
                }
            });
          
            overlay.addEventListener("click", () => {
                dropdown.classList.remove("is-open");
                toggleBtn.setAttribute("aria-expanded", "false");
                overlay.classList.remove("is-active");
                resetActive();
            });
          
            mainItems.forEach(item => {
                item.addEventListener("mouseover", () => {
                    const id = item.dataset.category;
                    mainItems.forEach(i => i.classList.toggle("is-active", i === item));
                    subcategories.forEach(sub => {
                        sub.classList.toggle("is-active", sub.dataset.category === id);
                    });
                });
            });
          
            const submenuToggles = dropdown.querySelectorAll(".submenu-title a");
            submenuToggles.forEach(toggle => {
                toggle.addEventListener("click", e => {
                    const parent = toggle.closest(".submenu-block");
                    const grandchildren = parent.querySelector(".submenu-grandchildren");
                    if (grandchildren) {
                        e.preventDefault();
                        grandchildren.classList.toggle("is-open");
                        toggle.parentElement.classList.toggle("open");
                    }
                });
            });
          
            const allSubLinks = dropdown.querySelectorAll(".submenu-grandchildren li a");
            allSubLinks.forEach(link => {
                link.addEventListener("mouseover", () => link.classList.add("is-hovered"));
                link.addEventListener("mouseout", () => link.classList.remove("is-hovered"));
            });
        });