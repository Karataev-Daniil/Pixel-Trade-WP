
document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('theme-toggle-button');

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.cookie = "theme=" + theme + ";path=/;max-age=" + (30*24*60*60);
    }

    let currentTheme = getCookie('theme') || document.documentElement.getAttribute('data-theme') || 'light';
    setTheme(currentTheme);

    toggleButton.addEventListener('click', () => {
        const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });
});

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

document.addEventListener('DOMContentLoaded', function () {
    const searchField = document.querySelector('.search-field');
    const searchPanel = document.querySelector('.search-panel');
    const clearButton = document.querySelector('.search-clear-button');

    function updateSearchState() {
        if (searchField.value.trim() !== '') {
            searchPanel.classList.add('has-content');
            searchPanel.style.top = '28px';
            searchField.style.paddingTop = '12px';
            searchField.style.paddingBottom = '12px';
            searchField.style.borderRadius = '12px';
        } else {
            searchPanel.classList.remove('has-content');
            searchPanel.style.top = '0px';
            searchField.style.paddingTop = '4px';
            searchField.style.paddingBottom = '4px';
            searchField.style.borderRadius = '6px';
        }
    }

    searchField.addEventListener('input', updateSearchState);

    clearButton.addEventListener('click', function () {
        searchField.value = '';
        updateSearchState();
        searchField.focus();
    });

    updateSearchState();
});


document.addEventListener('DOMContentLoaded', function () {
    const avatar = document.getElementById('user-avatar');
    const menuWrapper = avatar.closest('.user-menu');

    avatar.addEventListener('click', function (e) {
        e.stopPropagation();
        menuWrapper.classList.toggle('active');
    });

    document.addEventListener('click', function () {
        menuWrapper.classList.remove('active');
    });
});
document.addEventListener('DOMContentLoaded', function () {
    const avatar = document.getElementById('user-avatar');
    const username = document.querySelector('.user-name');

    username.addEventListener('mouseenter', () => {
        avatar.style.borderColor = 'var(--orange_0)';
        avatar.style.transform = 'scale(1.05)';
    });

    username.addEventListener('mouseleave', () => {
        avatar.style.borderColor = 'rgba(255, 255, 255, 0.2)';
        avatar.style.transform = 'scale(1)';
    });
});


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
