document.addEventListener('DOMContentLoaded', () => {
    const statusTabs = document.querySelectorAll('.status-tab');
    const searchInput = document.querySelector('.filter-search');
    const categorySelect = document.querySelector('.filter-category');
    const sortSelect = document.querySelector('.filter-sort'); // <-- сортировка
    const productsList = document.getElementById('products-list');
    const selectAllCheckbox = document.getElementById('select-all-products');
    const bulkActions = document.getElementById('bulk-actions');
    const pagination = document.getElementById('pagination');

    let currentStatus = 'all';
    let selectedProducts = new Set();
    let currentPage = 1;
    let totalPages = 1;

    function debounce(fn, delay=300){
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(()=>fn(...args), delay);
        };
    }

    function renderPagination(){
        pagination.innerHTML = '';
        if(totalPages <= 1) return;
        for(let i=1;i<=totalPages;i++){
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'page-btn'+(i===currentPage?' active':'');
            btn.addEventListener('click', ()=> {
                if(i!==currentPage){
                    currentPage=i;
                    fetchProducts();
                }
            });
            pagination.appendChild(btn);
        }
    }

    function fetchProducts(){
        const data = new FormData();
        data.append('action','filter_my_products');
        data.append('statuses[]', currentStatus);
        data.append('search', searchInput.value);
        data.append('category', categorySelect.value);
        data.append('page', currentPage);
        data.append('sort', sortSelect.value);
        data.append('nonce', MY_PRODUCTS_AJAX.nonce);

        productsList.innerHTML = `<tr><td colspan="5">${t('Загрузка товаров...','Loading products...','Se încarcă produsele...')}</td></tr>`;

        fetch(MY_PRODUCTS_AJAX.ajaxUrl,{method:'POST',body:data})
            .then(res=>res.json())
            .then(res=>{
                productsList.innerHTML='';
                selectAllCheckbox.checked=false;

                if(res.success && res.data.products.length){
                    const fragment=document.createDocumentFragment();
                    res.data.products.forEach(p=>{
                        const tr=document.createElement('tr');
                        tr.classList.add('product-'+p.status);
                        const checked = selectedProducts.has(p.id.toString()) ? 'checked' : '';
                        tr.innerHTML = `
                            <td><div class="checkbox-block"><input type="checkbox" class="select-product" value="${p.id}" ${checked}></div></td>
                            <td><img src="${p.thumb}" alt="${p.title}" class="product-thumb"></td>
                            <td><strong>${p.title}</strong><br>${p.categories}<br><span class="product-price">${p.price}</span></td>
                            <td>${p.status} / ${p.date}</td>
                            <td class="product-actions">
                                <span class="action-btn" data-action="republish">
                                    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10h-2a8 8 0 1 1-8-8v4l5-5-5-5v4z"/></svg>
                                </span>
                                <span class="action-btn" data-action="hide">
                                    <svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 12c-2.48 0-4.5-2.02-4.5-4.5S9.52 7.5 12 7.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5z"/><line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <span class="action-btn" data-action="delete">
                                    <svg viewBox="0 0 24 24"><line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/><line x1="6" y1="18" x2="18" y2="6" stroke="currentColor" stroke-width="2"/></svg>
                                </span>
                                <a href="${p.edit_link}" class="action-btn edit">
                                    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75l11.06-11.06-3.75-3.75L3 17.25zM21.41 6.34a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </a>
                            </td>`;
                        fragment.appendChild(tr);
                    });
                    productsList.appendChild(fragment);

                    currentPage=res.data.current_page;
                    totalPages=res.data.total_pages;

                    if(res.data.status_counts){
                        for(const s in res.data.status_counts){
                            const el = document.querySelector(`.status-tab[data-status="${s}"] .count`);
                            if(el) el.textContent = res.data.status_counts[s];
                        }
                    }
                } else {
                    productsList.innerHTML = `<tr><td colspan="5">${t('Товары не найдены','Products not found','Produsele nu au fost găsite')}</td></tr>`;
                    totalPages = 1;
                    currentPage = 1;
                }
                attachRowEvents();
                renderPagination();
            });
    }

    const fetchProductsDebounced = debounce(()=>{ currentPage=1; fetchProducts(); }, 300);

    function attachRowEvents(){
        document.querySelectorAll('.select-product').forEach(cb=>{
            cb.addEventListener('change', e=>{
                const row = e.target.closest('tr');
                if(e.target.checked){
                    selectedProducts.add(e.target.value);
                    row.classList.add('selected');
                } else {
                    selectedProducts.delete(e.target.value);
                    row.classList.remove('selected');
                }
                bulkActions.style.display = selectedProducts.size ? 'flex':'none';
            });
        });

        document.querySelectorAll('.action-btn').forEach(btn=>{
            btn.addEventListener('click', e=>{
                if(btn.classList.contains('edit')) return;
                const actionType = btn.dataset.action;
                const row = btn.closest('tr');
                const id = row.querySelector('.select-product').value;
                performAction([id], actionType);
            });
        });
    }

    function performAction(ids, actionType){
        const messages = {
            delete: t('Вы уверены, что хотите удалить выбранные товары?','Are you sure you want to delete selected products?','Sigur doriți să ștergeți produsele selectate?'),
            hide: t('Вы уверены, что хотите скрыть выбранные товары?','Are you sure you want to hide selected products?','Sigur doriți să ascundeți produsele selectate?'),
            republish: t('Вы уверены, что хотите обновить выбранные товары?','Are you sure you want to republish selected products?','Sigur doriți să republicați produsele selectate?')
        };
        const successMsg = {
            delete: t('Товары удалены','Products deleted','Produsele au fost șterse'),
            hide: t('Товары скрыты','Products hidden','Produsele au fost ascunse'),
            republish: t('Товары обновлены','Products updated','Produsele au fost actualizate')
        };

        function sendAction(){
            const data = new FormData();
            data.append('action','product_action');
            ids.forEach(id=>data.append('product_ids[]',id));
            data.append('action_type',actionType);
            data.append('nonce',MY_PRODUCTS_AJAX.nonce);

            fetch(MY_PRODUCTS_AJAX.ajaxUrl,{method:'POST',body:data})
                .then(res=>res.json())
                .then(res=>{
                    if(res.success){
                        showPopup({ title: t('Успешно','Success','Succes'), message: successMsg[actionType], type:'success' });
                        selectedProducts.clear();
                        document.querySelectorAll('.select-product').forEach(cb=>{
                            cb.checked=false;
                            cb.closest('tr').classList.remove('selected');
                        });
                        selectAllCheckbox.checked=false; // сбросить чекбокс "выбрать все"
                        bulkActions.style.display='none';
                        fetchProducts();
                    } else {
                        showPopup({ title: t('Ошибка','Error','Eroare'), message: res.data || t('Произошла неизвестная ошибка','Unknown error occurred','A apărut o eroare necunoscută'), type:'danger' });
                    }
                });
        }

        if(actionType==='delete'){
            showPopup({
                title: t('Подтверждение','Confirmation','Confirmare'),
                message: messages.delete,
                type:'warning',
                buttons:[
                    { text: t('Отмена','Cancel','Anulare'), className:'secondary' },
                    { text: t('Ок','Ok','Ok'), className:'primary', callback: sendAction }
                ]
            });
        } else {
            sendAction();
        }
    }

    selectAllCheckbox.addEventListener('change', ()=>{
        document.querySelectorAll('.select-product').forEach(cb=>{
            cb.checked=selectAllCheckbox.checked;
            const row = cb.closest('tr');
            if(cb.checked){
                selectedProducts.add(cb.value);
                row.classList.add('selected');
            } else {
                selectedProducts.delete(cb.value);
                row.classList.remove('selected');
            }
        });
        bulkActions.style.display = selectedProducts.size ? 'flex':'none';
    });

    bulkActions.querySelectorAll('.action-btn').forEach(btn=>{
        btn.addEventListener('click', ()=> performAction([...selectedProducts], btn.dataset.action));
    });

    statusTabs.forEach(tab=>{
        tab.addEventListener('click', ()=>{
            statusTabs.forEach(t=>t.classList.remove('active'));
            tab.classList.add('active');
            currentStatus = tab.dataset.status;
            currentPage = 1;
            fetchProducts();
        });
    });

    searchInput.addEventListener('input', fetchProductsDebounced);
    categorySelect.addEventListener('change', ()=>{ currentPage=1; fetchProducts(); });
    sortSelect.addEventListener('change', ()=>{ currentPage=1; fetchProducts(); }); // <-- сортировка

    if(statusTabs.length>0){
        statusTabs[0].classList.add('active');
        currentStatus = statusTabs[0].dataset.status;
        fetchProducts();
    }
});
