
document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('theme-toggle-button');

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.cookie = "theme=" + theme + ";path=/;max-age=" + (30*24*60*60);

        const sunIcon = toggleButton.querySelector('.icon-sun');
        const moonIcon = toggleButton.querySelector('.icon-moon');

        if (theme === 'dark') {
            sunIcon.style.display = 'inline';
            moonIcon.style.display = 'none';
        } else {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'inline';
        }
    }

    let currentTheme = getCookie('theme') || document.documentElement.getAttribute('data-theme') || 'light';
    setTheme(currentTheme);

    toggleButton.addEventListener('click', () => {
        const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });
});


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
    const dropdown = document.getElementById('user-dropdown');
    avatar.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
    });
    document.addEventListener('click', function () {
        dropdown.style.display = 'none';
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