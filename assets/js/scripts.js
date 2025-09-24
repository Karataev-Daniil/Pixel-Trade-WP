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

jQuery(document).ready(function($) {
    function initFirstSlider() {
        var $slickSlider = $('.main-slider');

        if ($slickSlider.length > 0) {
            $slickSlider.slick({
                infinite: false,
                swipe: false,
                draggable: true,
                slidesToScroll: 1,
                slidesToShow: 1,
                variableWidth: true,
                swipeToSlide: false,
                speed: 400,
                prevArrow: '<button class="slick-prev" aria-label="Назад"></button>',
                nextArrow: '<button class="slick-next" aria-label="Вперёд"></button>',
            });
        }
    }

    initFirstSlider();
});

let language = window.language || 'ru';

window.t = function(ru, en, ro) {
    if (window.language === 'en') return en;
    if (window.language === 'ro') return ro;
    return ru;
};

document.addEventListener("DOMContentLoaded", () => {
  const dropdowns = document.querySelectorAll(".dropdown");

  dropdowns.forEach(dropdown => {
    const button = dropdown.querySelector(".dropdown__button");
    const list = dropdown.querySelector(".dropdown__list");
    const items = dropdown.querySelectorAll(".dropdown__item");
    const icon = dropdown.querySelector(".dropdown__icon");

    button.addEventListener("click", (e) => {
      e.stopPropagation();

      dropdowns.forEach(d => {
        if (d !== dropdown) d.classList.remove("open");
      });

      dropdown.classList.toggle("open");
    });

    items.forEach(item => {
      item.addEventListener("click", () => {
        button.childNodes[0].nodeValue = item.textContent + " ";
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
