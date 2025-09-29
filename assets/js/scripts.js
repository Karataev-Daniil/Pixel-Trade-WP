function setFieldMessage(id, message = '', type = '') {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = message;
    el.classList.remove('error', 'warning', 'success');
    if (type) el.classList.add(type);
}

function clearFieldMessage(id) {
    const el = document.getElementById('message_' + id) || document.getElementById(id);
    if (!el) return;
    el.textContent = '';
    el.classList.remove('error', 'warning', 'success');
}

let language = window.language || 'ru';

window.t = function(ru, en, ro) {
    if (window.language === 'en') return en;
    if (window.language === 'ro') return ro;
    return ru;
};

document.addEventListener("DOMContentLoaded", () => {
    const dropdowns = document.querySelectorAll(".dropdown");
    if (!dropdowns.length) return;
    
    dropdowns.forEach(dropdown => {
        const button = dropdown.querySelector(".dropdown__button");
        const items = dropdown.querySelectorAll(".dropdown__item");
        if (!button || !items.length) return;
        
        button.addEventListener("click", (e) => {
            e.stopPropagation();
            
            dropdowns.forEach(d => {
                if (d !== dropdown) d.classList.remove("open");
            });
          
            dropdown.classList.toggle("open");
        });
      
        items.forEach(item => {
            item.addEventListener("click", () => {
                if (button.childNodes.length > 0) {
                    button.childNodes[0].nodeValue = item.textContent + " ";
                }
                dropdown.classList.remove("open");
            });
        });
    });

    document.addEventListener("click", () => {
      dropdowns.forEach(dropdown => dropdown.classList.remove("open"));
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        dropdowns.forEach(dropdown => dropdown.classList.remove("open"));
      }
    });
});
