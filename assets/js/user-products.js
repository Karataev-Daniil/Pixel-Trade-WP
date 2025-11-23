document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const statusTabs = document.querySelectorAll('.status-tab');
    const searchInput = document.querySelector('.filter-search');
    const categorySelect = document.querySelector('.filter-category');
    const sortSelect = document.querySelector('.filter-sort');
    const productsList = document.getElementById('products-list');
    const selectAllCheckbox = document.getElementById('select-all-products');
    const bulkActions = document.getElementById('bulk-actions');
    const pagination = document.getElementById('pagination');
    const selectionInfo = document.getElementById('selection-info');

    // State
    let currentStatus = 'all';
    let selectedProducts = new Set();
    let deselectedProducts = new Set();
    let selectAllGlobal = false;
    let currentPage = 1;
    let totalPages = 1;
    let totalCount = 0;

    // Utilities
    const debounce = (fn, delay = 300) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    };

    const tSafe = (...args) => {
        // small helper if t() not defined — fallback to first arg
        if (typeof t === 'function') return t(...args);
        return args[0];
    };

    // Selection UI
    const updateSelectionInfo = () => {
        let count = 0;
        if (selectAllGlobal) {
            count = totalCount - deselectedProducts.size;
            selectionInfo.textContent = count === 0 ? '' : `${tSafe('Выбраны все','All selected')} (${count})`;
        } else {
            count = selectedProducts.size;
            selectionInfo.textContent = count === 0 ? '' : `${tSafe('Выбрано:','Selected:')} ${count}`;
        }
    };

    const resetSelectionState = () => {
        selectedProducts.clear();
        deselectedProducts.clear();
        selectAllGlobal = false;
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        document.querySelectorAll('.select-product').forEach(cb => {
            cb.checked = false;
            const tr = cb.closest('tr');
            if (tr) tr.classList.remove('selected');
        });
        bulkActions.style.display = 'none';
        updateSelectionInfo();
    };

    // Pagination render
    const renderPagination = () => {
        pagination.innerHTML = '';
        if (totalPages <= 1) return;
        const fragment = document.createDocumentFragment();
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = i;
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.addEventListener('click', () => {
                if (i !== currentPage) {
                    currentPage = i;
                    fetchProducts();
                    // scroll to top of table slightly
                    window.scrollTo({ top: productsList.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
                }
            });
            fragment.appendChild(btn);
        }
        pagination.appendChild(fragment);
    };

    // Row event wiring (called after rows rendered)
    const attachRowEvents = () => {
        // checkbox handlers
        productsList.querySelectorAll('.select-product').forEach(cb => {
            cb.removeEventListener('change', onRowCheckboxChange);
            cb.addEventListener('change', onRowCheckboxChange);
        });

        // per-row action buttons (republish/hide/delete)
        productsList.querySelectorAll('.action-btn').forEach(btn => {
            // skip edit links (they have .edit)
            if (btn.classList.contains('edit')) return;
            btn.removeEventListener('click', onRowActionClick);
            btn.addEventListener('click', onRowActionClick);
        });
    };

    // Checkbox change handler
    function onRowCheckboxChange(e) {
        const cb = e.currentTarget;
        const row = cb.closest('tr');
        const id = cb.value;
        if (!id) return;

        if (selectAllGlobal) {
            if (cb.checked) {
                deselectedProducts.delete(id);
                row.classList.add('selected');
            } else {
                deselectedProducts.add(id);
                row.classList.remove('selected');
            }
        } else {
            if (cb.checked) {
                selectedProducts.add(id);
                row.classList.add('selected');
            } else {
                selectedProducts.delete(id);
                row.classList.remove('selected');
            }
        }

        bulkActions.style.display = (selectAllGlobal || selectedProducts.size > 0) ? 'flex' : 'none';
        updateSelectionInfo();
    }

    // Per-row action button click
    function onRowActionClick(e) {
        e.stopPropagation();
        const btn = e.currentTarget;
        if (btn.classList.contains('edit')) return;
        const row = btn.closest('tr');
        const id = row?.querySelector('.select-product')?.value;
        if (!id) return;
        performAction([id], btn.dataset.action);
    }

    // Actions (single or bulk)
    const performAction = (ids, actionType) => {
        const messages = {
            delete: tSafe('Вы уверены, что хотите удалить выбранные товары?', 'Are you sure you want to delete selected products?'),
            hide: tSafe('Вы уверены, что хотите скрыть выбранные товары?', 'Are you sure you want to hide selected products?'),
            republish: tSafe('Вы уверены, что хотите обновить выбранные товары?', 'Are you sure you want to republish selected products?')
        };
        const successMsg = {
            delete: tSafe('Товары удалены','Products deleted'),
            hide: tSafe('Товары скрыты','Products hidden'),
            republish: tSafe('Товары обновлены','Products updated')
        };

        const sendAction = (payloadForm) => {
            fetch(MY_PRODUCTS_AJAX.ajaxUrl, { method: 'POST', body: payloadForm })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        if (typeof showPopup === 'function') {
                            showPopup({ title: tSafe('Успешно','Success'), message: successMsg[actionType] || tSafe('Действие выполнено','Action performed'), type: 'success' });
                        } else {
                            alert(successMsg[actionType] || tSafe('Действие выполнено','Action performed'));
                        }
                        resetSelectionState();
                        fetchProducts();
                    } else {
                        if (typeof showPopup === 'function') {
                            showPopup({ title: tSafe('Ошибка','Error'), message: res.data || tSafe('Произошла ошибка','An error occurred'), type: 'danger' });
                        } else {
                            alert(res.data || tSafe('Произошла ошибка','An error occurred'));
                        }
                    }
                })
                .catch(err => {
                    if (typeof showPopup === 'function') {
                        showPopup({ title: tSafe('Ошибка','Error'), message: err.message || tSafe('Произошла ошибка','An error occurred'), type: 'danger' });
                    } else {
                        alert(err.message || tSafe('Произошла ошибка','An error occurred'));
                    }
                });
        };

        if (actionType === 'delete') {
            // confirmation popup with callback
            if (typeof showPopup === 'function') {
                showPopup({
                    title: tSafe('Подтверждение','Confirmation'),
                    message: messages.delete,
                    type: 'warning',
                    buttons: [
                        { text: tSafe('Отмена','Cancel'), className: 'secondary' },
                        { text: tSafe('Ок','Ok'), className: 'primary', callback: () => {
                            const data = new FormData();
                            data.append('action', 'product_action');
                            ids.forEach(id => data.append('product_ids[]', id));
                            data.append('action_type', actionType);
                            data.append('nonce', MY_PRODUCTS_AJAX.nonce);
                            sendAction(data);
                        } }
                    ]
                });
            } else {
                if (confirm(messages.delete)) {
                    const data = new FormData();
                    data.append('action', 'product_action');
                    ids.forEach(id => data.append('product_ids[]', id));
                    data.append('action_type', actionType);
                    data.append('nonce', MY_PRODUCTS_AJAX.nonce);
                    sendAction(data);
                }
            }
        } else {
            const data = new FormData();
            data.append('action', 'product_action');
            ids.forEach(id => data.append('product_ids[]', id));
            data.append('action_type', actionType);
            data.append('nonce', MY_PRODUCTS_AJAX.nonce);
            sendAction(data);
        }
    };

    // Bulk actions handler
    const handleBulkAction = (actionType) => {
        const data = new FormData();
        data.append('action', 'product_action');
        data.append('action_type', actionType);
        data.append('nonce', MY_PRODUCTS_AJAX.nonce);

        if (selectAllGlobal) {
            data.append('select_all', '1');
            deselectedProducts.forEach(id => data.append('deselected_ids[]', id));
        } else {
            for (const id of selectedProducts) data.append('product_ids[]', id);
        }

        fetch(MY_PRODUCTS_AJAX.ajaxUrl, { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    if (typeof showPopup === 'function') showPopup({ title: tSafe('Успешно','Success'), message: tSafe('Действие выполнено','Action performed'), type:'success' });
                    resetSelectionState();
                    fetchProducts();
                } else {
                    if (typeof showPopup === 'function') showPopup({ title: tSafe('Ошибка','Error'), message: res.data || tSafe('Произошла ошибка','An error occurred'), type:'danger' });
                }
            })
            .catch(err => {
                if (typeof showPopup === 'function') showPopup({ title: tSafe('Ошибка','Error'), message: err.message || tSafe('Произошла ошибка','An error occurred'), type:'danger' });
            });
    };

    // wire bulk actions buttons
    if (bulkActions) {
        bulkActions.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const act = btn.dataset.action;
                if (!act) return;
                // for bulk delete we may want confirmation — performAction will handle it for single but here we use handleBulkAction and confirm manually
                if (act === 'delete') {
                    if (typeof showPopup === 'function') {
                        showPopup({
                            title: tSafe('Подтверждение','Confirmation'),
                            message: tSafe('Вы уверены, что хотите удалить выбранные товары?','Are you sure you want to delete selected products?'),
                            type: 'warning',
                            buttons: [
                                { text: tSafe('Отмена','Cancel'), className: 'secondary' },
                                { text: tSafe('Ок','Ok'), className: 'primary', callback: () => handleBulkAction(act) }
                            ]
                        });
                    } else {
                        if (confirm(tSafe('Вы уверены, что хотите удалить выбранные товары?','Are you sure?'))) handleBulkAction(act);
                    }
                } else {
                    handleBulkAction(act);
                }
            });
        });
    }

    // Fetch products (list)
    const fetchProducts = () => {
        const data = new FormData();
        data.append('action', 'filter_my_products');
        data.append('statuses[]', currentStatus);
        data.append('search', searchInput?.value || '');
        data.append('category', categorySelect?.value || 'all');
        data.append('page', currentPage);
        data.append('sort', sortSelect?.value || '');
        data.append('nonce', MY_PRODUCTS_AJAX.nonce);

        productsList.innerHTML = `<tr><td colspan="5">${tSafe('Загрузка товаров...', 'Loading products...')}</td></tr>`;

        fetch(MY_PRODUCTS_AJAX.ajaxUrl, { method: 'POST', body: data })
            .then(res => res.json())
            .then(res => {
                productsList.innerHTML = '';
                resetSelectionState();

                totalCount = res.data?.total_count || 0;

                if (res.success && Array.isArray(res.data.products) && res.data.products.length) {
                    const fragment = document.createDocumentFragment();
                    res.data.products.forEach(p => {
                        const tr = document.createElement('tr');
                        tr.classList.add('product-row', 'product-' + (p.status || 'unknown'));
                        tr.dataset.id = p.id;
                        tr.innerHTML = `
                            <td><div class="checkbox-block"><input type="checkbox" class="select-product" value="${p.id}"></div></td>
                            <td><img src="${p.thumb}" alt="${p.title}" class="product-thumb" onerror="this.style.opacity=.6"></td>
                            <td><strong>${escapeHtml(p.title)}</strong><br>${p.categories || ''}<br><span class="product-price">${escapeHtml(p.price || '')}</span></td>
                            <td>${escapeHtml(p.status)} / ${escapeHtml(p.date)}</td>
                            <td class="product-actions">
                                <span class="action-btn" data-action="republish" title="${tSafe('Переопубликовать','Republish')}">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 2a10 10 0 1 0 10 10h-2a8 8 0 1 1-8-8v4l5-5-5-5v4z"/></svg>
                                </span>
                                <span class="action-btn" data-action="hide" title="${tSafe('Скрыть','Hide')}">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12c-2.48 0-4.5-2.02-4.5-4.5S9.52 7.5 12 7.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5z"/><line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <span class="action-btn" data-action="delete" title="${tSafe('Удалить','Delete')}">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/><line x1="6" y1="18" x2="18" y2="6" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <a href="${p.edit_link}" class="action-btn edit" title="${tSafe('Редактировать','Edit')}" onclick="event.stopPropagation()">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path d="M3 17.25V21h3.75l11.06-11.06-3.75-3.75L3 17.25zM21.41 6.34a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </a>
                            </td>`;
                        fragment.appendChild(tr);
                    });
                    productsList.appendChild(fragment);

                    // update pagination & counts
                    currentPage = res.data.current_page || 1;
                    totalPages = res.data.total_pages || 1;
                    if (res.data.status_counts) {
                        for (const s in res.data.status_counts) {
                            const el = document.querySelector(`.status-tab[data-status="${s}"] .count`);
                            if (el) el.textContent = res.data.status_counts[s];
                        }
                        // update 'all' count if present
                        const allCountEl = document.querySelector('.status-tab[data-status="all"] .count');
                        if (allCountEl) allCountEl.textContent = Object.values(res.data.status_counts).reduce((a,b)=>a+(+b||0),0);
                    }

                    attachRowEvents();
                } else {
                    productsList.innerHTML = `<tr><td colspan="5">${tSafe('Товары не найдены','Products not found')}</td></tr>`;
                    totalPages = 1;
                    currentPage = 1;
                }
                renderPagination();
                updateSelectionInfo();
            })
            .catch(err => {
                productsList.innerHTML = `<tr><td colspan="5">${tSafe('Ошибка загрузки','Loading error')}: ${escapeHtml(err.message)}</td></tr>`;
            });
    };

    const fetchProductsDebounced = debounce(() => { currentPage = 1; fetchProducts(); }, 300);

    // Select all handling
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            selectAllGlobal = selectAllCheckbox.checked;
            selectedProducts.clear();
            deselectedProducts.clear();

            productsList.querySelectorAll('.select-product').forEach(cb => {
                cb.checked = selectAllGlobal;
                const tr = cb.closest('tr');
                if (tr) tr.classList.toggle('selected', selectAllGlobal);
            });

            bulkActions.style.display = selectAllGlobal ? 'flex' : 'none';
            updateSelectionInfo();
        });
    }

    // Status tabs
    statusTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            statusTabs.forEach(tn => tn.classList.remove('active'));
            tab.classList.add('active');
            currentStatus = tab.dataset.status;
            currentPage = 1;
            fetchProducts();
        });
    });

    // Filters
    if (searchInput) searchInput.addEventListener('input', fetchProductsDebounced);
    if (categorySelect) categorySelect.addEventListener('change', () => { currentPage = 1; fetchProducts(); });
    if (sortSelect) sortSelect.addEventListener('change', () => { currentPage = 1; fetchProducts(); });

    // Row click: open stats popup (delegated)
    // We attach one global listener that ignores clicks on action buttons, links and checkboxes.
    document.addEventListener('click', async (e) => {
        const row = e.target.closest('tr.product-row');
        // ignore if not product row or click was on control inside row
        if (!row) return;
        if (e.target.closest('.action-btn') || e.target.closest('input[type="checkbox"]') || e.target.tagName === 'A') return;

        const productId = row.dataset.id;
        if (!productId) return;

        await openStatsPopup(productId);
    });

    // Popup implementation
    let activePopup = null;

    async function openStatsPopup(productId) {
        // prevent duplicate
        if (activePopup) activePopup.remove();

        // create popup skeleton
        const popup = document.createElement('div');
        popup.className = 'stats-popup';
        popup.innerHTML = `
            <div class="popup-inner">
                <div class="stats-header">
                    <h2>📊 ${tSafe('Статистика товара','Product statistics')}</h2>
                    <button class="close-popup" title="${tSafe('Закрыть','Close')}">×</button>
                </div>
                <div class="stats-body">
                    <div class="stats-loading">${tSafe('Загрузка данных...','Loading...')}</div>
                </div>
            </div>
        `;
        document.body.appendChild(popup);
        activePopup = popup;

        // close handlers
        popup.querySelector('.close-popup').addEventListener('click', () => popup.remove());
        popup.addEventListener('click', (ev) => {
            if (ev.target === popup) popup.remove();
        });
        document.addEventListener('keydown', onEscPress);

        // fetch stats from server
        try {
            const data = new FormData();
            data.append('action', 'get_product_stats');
            data.append('product_id', productId);
            data.append('nonce', MY_PRODUCTS_AJAX.nonce);

            const res = await fetch(MY_PRODUCTS_AJAX.ajaxUrl, { method: 'POST', body: data });
            const json = await res.json();

            if (!json.success || !json.data) {
                renderStatsError(popup, json?.data?.message || tSafe('Не удалось загрузить статистику','Failed to load stats'));
                return;
            }

            renderStatsContent(popup, json.data);
        } catch (err) {
            renderStatsError(popup, err.message || tSafe('Произошла ошибка','An error occurred'));
        }

        // cleanup when removed
        popup.addEventListener('remove', () => {
            document.removeEventListener('keydown', onEscPress);
            if (activePopup === popup) activePopup = null;
        });
    }

    function onEscPress(e) {
        if (e.key === 'Escape' && activePopup) {
            activePopup.remove();
        }
    }

    function renderStatsError(popup, message) {
        const body = popup.querySelector('.stats-body');
        if (!body) return;
        body.innerHTML = `
            <div class="stats-error">
                <h3>${tSafe('Ошибка','Error')}</h3>
                <p>${escapeHtml(message)}</p>
                <div style="text-align:right;margin-top:12px;">
                    <button class="close-popup small">${tSafe('Закрыть','Close')}</button>
                </div>
            </div>
        `;
        body.querySelectorAll('.close-popup').forEach(btn => btn.addEventListener('click', () => popup.remove()));
    }

    function renderStatsContent(popup, data) {
        const body = popup.querySelector('.stats-body');
        if (!body) return;

        const totalViews = data.total_views ?? 0;
        const uniqueViews = data.unique_views ?? '-';
        const lastViewed = data.last_viewed ?? '-';
        const daily = Array.isArray(data.daily) ? data.daily : [];
        const recent = Array.isArray(data.recent) ? data.recent : [];

        const dailyHtml = daily.length
            ? daily.map(d => `<tr><td>${escapeHtml(d.view_date)}</td><td>${escapeHtml(String(d.views))}</td></tr>`).join('')
            : `<tr><td colspan="2">${tSafe('Нет данных','No data')}</td></tr>`;

        const recentHtml = recent.length
            ? recent.map(r => `<tr><td>${escapeHtml(r.user_id ?? 'Guest')}</td><td>${escapeHtml(r.ip_address ?? '-')}</td><td>${escapeHtml(r.viewed_at ?? '-')}</td></tr>`).join('')
            : `<tr><td colspan="3">${tSafe('Нет данных','No data')}</td></tr>`;

        body.innerHTML = `
            <div class="stats-summary">
                <div class="stat-card">
                    <div class="stat-label">${tSafe('Всего просмотров','Total views')}</div>
                    <div class="stat-value">${escapeHtml(String(totalViews))}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">${tSafe('Уникальные просмотры','Unique views')}</div>
                    <div class="stat-value">${escapeHtml(String(uniqueViews))}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">${tSafe('Последний просмотр','Last viewed')}</div>
                    <div class="stat-value">${escapeHtml(String(lastViewed))}</div>
                </div>
            </div>

            <div class="stats-section">
                <h3>${tSafe('По дням (последние 30 дней)','By day (last 30 days)')}</h3>
                <div class="table-wrapper">
                    <table class="stats-table">
                        <thead><tr><th>${tSafe('Дата','Date')}</th><th>${tSafe('Просмотры','Views')}</th></tr></thead>
                        <tbody>${dailyHtml}</tbody>
                    </table>
                </div>
            </div>

            <div class="stats-section">
                <h3>${tSafe('Последние просмотры','Recent views')}</h3>
                <div class="table-wrapper">
                    <table class="stats-table">
                        <thead><tr><th>${tSafe('Пользователь','User')}</th><th>IP</th><th>${tSafe('Время','Time')}</th></tr></thead>
                        <tbody>${recentHtml}</tbody>
                    </table>
                </div>
            </div>

            <div id="stats-chart-container" style="margin-top:12px;"></div>

            <div style="text-align:right;margin-top:12px;">
                <button class="close-popup">${tSafe('Закрыть','Close')}</button>
            </div>
        `;

        body.querySelectorAll('.close-popup').forEach(btn => btn.addEventListener('click', () => popup.remove()));

        // If Chart.js is available and daily data exists, render a line chart
        if (window.Chart && daily.length) {
            try {
                const container = popup.querySelector('#stats-chart-container');
                container.innerHTML = '<canvas id="stats-chart" style="max-width:100%;height:220px"></canvas>';
                const ctx = container.querySelector('#stats-chart').getContext('2d');

                const labels = daily.map(d => d.view_date);
                const values = daily.map(d => Number(d.views) || 0);

                // eslint-disable-next-line no-unused-vars
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: tSafe('Просмотры','Views'),
                            data: values,
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { display: true, ticks: { maxRotation: 45, minRotation: 0 } },
                            y: { beginAtZero: true }
                        }
                    }
                });
            } catch (err) {
                // chart failure should not break popup
                console.warn('Chart render failed', err);
            }
        }
    }

    // Helpers
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Init
    if (statusTabs.length > 0) {
        currentStatus = statusTabs[0].dataset.status || 'all';
    }
    fetchProducts();
});
