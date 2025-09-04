function showPopup(options) {
    const defaultOptions = {
        title: '',
        message: '',
        type: 'info', // success | warning | danger | info
        buttons: [{ text: 'Ок', callback: () => {}, className: 'primary' }]
    };
    const opts = { ...defaultOptions, ...options };

    const icons = {
        success: themeVars.imgPath + 'icon-success.svg',
        warning: themeVars.imgPath + 'icon-warning.svg',
        danger:  themeVars.imgPath + 'icon-error.svg',
        info:    themeVars.imgPath + 'icon-info.svg',
        close:   themeVars.imgPath + 'icon-close.svg'
    };

    const overlay = document.createElement('div');
    overlay.className = 'universal-popup-overlay';

    const popup = document.createElement('div');
    popup.className = `universal-popup ${opts.type}`;

    const html = `
        <h3 class="popup-title title-larger">${opts.title}</h3>
        <p class="popup-message body-small-regular">${opts.message}</p>
        <div class="popup-buttons"></div>
    `;
    popup.insertAdjacentHTML('beforeend', html);

    function insertSVG(path, container, className, callback) {
        fetch(path)
            .then(res => res.text())
            .then(svgText => {
                const wrapper = document.createElement('div');
                wrapper.className = className;
                wrapper.innerHTML = svgText;
                container.appendChild(wrapper);
                if (callback) callback(wrapper);
            })
            .catch(err => console.error('Ошибка загрузки SVG:', err));
    }

    insertSVG(icons.close, popup, 'popup-close', (el) => {
        el.addEventListener('click', () => document.body.removeChild(overlay));
    });

    insertSVG(icons[opts.type], popup, 'popup-icon');

    overlay.appendChild(popup);
    document.body.appendChild(overlay);

    const btnContainer = popup.querySelector('.popup-buttons');
    opts.buttons.forEach(btn => {
        const b = document.createElement('button');
        b.textContent = btn.text;
        b.className = `popup-btn button-small ${btn.className || ''}`;
        b.addEventListener('click', () => {
            if (btn.callback) btn.callback();
            if (document.body.contains(overlay)) document.body.removeChild(overlay);
        });
        btnContainer.appendChild(b);
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) document.body.removeChild(overlay);
    });
}
