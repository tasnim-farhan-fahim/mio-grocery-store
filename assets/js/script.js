	// Helpers: modal open/close
	const overlay = document.querySelector('.modal-overlay');
	function getBaseUrl() {
		const scr = document.querySelector('script[src*="/assets/js/script.js"]');
		if (!scr) return '';
		const href = scr.getAttribute('src') || '';
		return href.replace(/\/assets\/js\/script\.js.*/, '');
	}

	// Flash helpers
	function ensureFlashWrapper() {
		let wrapper = document.querySelector('.flash-wrapper');
		if (!wrapper) {
			wrapper = document.createElement('div');
			wrapper.className = 'flash-wrapper';
			document.body.appendChild(wrapper);
		}
		return wrapper;
	}
	function showFlash(type, message) {
		const wrapper = ensureFlashWrapper();
		const div = document.createElement('div');
		div.className = 'flash ' + (type || 'success');
		div.setAttribute('role', 'status');
		div.textContent = message || 'Action completed';
		wrapper.appendChild(div);
		setTimeout(() => { div.classList.add('hide'); setTimeout(() => div.remove(), 450); }, 5000);
	}
	function openModal(id) {
		const m = document.getElementById(id);
		if (!m) return;
		if (overlay) overlay.hidden = false;
		m.hidden = false;
		document.body.style.overflow = 'hidden';
	}
	function closeModals() {
		if (overlay) overlay.hidden = true;
		document.querySelectorAll('.modal').forEach(m => m.hidden = true);
		document.body.style.overflow = '';
	}
	if (overlay) overlay.addEventListener('click', closeModals);
	document.querySelectorAll('.modal .modal-close').forEach(btn => btn.addEventListener('click', closeModals));

	// Mobile nav toggle (supports new CSS: .site-nav.open and aria-expanded)
	const navToggle = document.querySelector('.nav-toggle');
	if (navToggle) {
		navToggle.addEventListener('click', () => {
			const siteNav = document.querySelector('.site-nav');
			const ul = document.querySelector('.site-nav ul');
			const expanded = navToggle.getAttribute('aria-expanded') === 'true';
			navToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
			// New style: toggle `.open` on `.site-nav`
			if (siteNav) siteNav.classList.toggle('open');
			// Backward-compat: also toggle `show` on the UL if present
			if (ul) ul.classList.toggle('show');
		});
	}

	// Open add product/category modals
	const openAddProduct = document.querySelector('.js-open-add-product');
	if (openAddProduct) openAddProduct.addEventListener('click', () => openModal('modal-add-product'));
	const openAddCategory = document.querySelector('.js-open-add-category');
	if (openAddCategory) openAddCategory.addEventListener('click', () => openModal('modal-add-category'));

	// Binder: open edit product modal and populate; fallback to full page if modal missing
	function bindEditButtons(scope) {
		(scope || document).querySelectorAll('.js-open-edit-product').forEach(btn => {
			btn.addEventListener('click', () => {
				const id = btn.dataset.id || '';
				const form = document.getElementById('form-edit-product');
				const modal = document.getElementById('modal-edit-product');
				if (!form || !modal) {
					// Graceful fallback: go to server-rendered edit page
					const base = getBaseUrl();
					const target = (base || '') + '/product/edit?id=' + encodeURIComponent(id);
					window.location.assign(target);
					return;
				}
				form.querySelector('input[name=id]').value = id;
				form.querySelector('input[name=name]').value = btn.dataset.name || '';
				form.querySelector('input[name=price]').value = btn.dataset.price || '';
				form.querySelector('input[name=stock_quantity]').value = btn.dataset.stock || '0';
				form.querySelector('input[name=image_url]').value = btn.dataset.image || '';
				const sel = form.querySelector('select[name=category_id]');
				if (sel) sel.value = btn.dataset.categoryId || '';
				openModal('modal-edit-product');
			});
		});
	}
	// Initial bind
	bindEditButtons(document);

	// AJAX submit add category
	const catForm = document.getElementById('form-add-category');
	if (catForm) {
		catForm.addEventListener('submit', e => {
			e.preventDefault();
			const fd = new FormData(catForm);
			fd.append('ajax','1');
			fetch(catForm.action, { method: 'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'} })
				.then(r => r.json()).then(data => {
					if (data && data.success) {
						// Flash
						showFlash('success', data.message || 'Category added');
						// Reset and close
						catForm.reset();
						closeModals();
						// Refresh category selects dynamically
						const baseUrl = (function(){
							const scr = document.querySelector('script[src*="/assets/js/script.js"]');
							if (!scr) return '';
							const href = scr.getAttribute('src');
							return href.replace(/\/assets\/js\/script\.js.*/, '');
						})();
						fetch(baseUrl + '/product/categories', { headers: {'X-Requested-With':'XMLHttpRequest'} })
							.then(r => r.json())
							.then(json => {
								if (!json || !json.success) return;
								const cats = json.categories || [];
								const updateSelect = sel => {
									if (!sel) return;
									const oldVal = sel.value;
									sel.innerHTML = '';
									const optNone = document.createElement('option'); optNone.value=''; optNone.textContent = sel.name==='category_id' ? '-- None --' : 'All categories';
									sel.appendChild(optNone);
									cats.forEach(c => { const o=document.createElement('option'); o.value=c.id; o.textContent=c.category_name; sel.appendChild(o); });
									sel.value = oldVal;
								};
								updateSelect(document.querySelector('.product-search select[name=category_id]'));
								updateSelect(document.querySelector('#form-add-product select[name=category_id]'));
								updateSelect(document.querySelector('#form-edit-product select[name=category_id]'));
							})
							.catch(err => { console.error('Fetch categories failed', err); showFlash('error','Failed to refresh categories'); });
					}
					}).catch(err => { console.error('Add category failed', err); showFlash('error','Add category failed'); });
		});
	}

	// AJAX submit add/edit product (lightweight)
	const addProd = document.getElementById('form-add-product');
	if (addProd) {
		addProd.addEventListener('submit', e => {
			e.preventDefault();
			const fd = new FormData(addProd); fd.append('ajax','1');
			fetch(addProd.action, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
				.then(() => { closeModals(); location.href = location.href; })
				.catch(err => console.error('Add product failed', err));
		});
	}
	const editProd = document.getElementById('form-edit-product');
	if (editProd) {
		editProd.addEventListener('submit', e => {
			e.preventDefault();
			const id = editProd.querySelector('input[name=id]').value;
			const action = editProd.action + '?id=' + encodeURIComponent(id);
			const fd = new FormData(editProd); fd.append('ajax','1');
			fetch(action, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
				.then(() => { closeModals(); location.href = location.href; })
				.catch(err => console.error('Update product failed', err));
		});
	}

	// Debounced live search for text/category/sort and single price sort range
	(function(){
		const textInput = document.getElementById('search-text');
		const catSelect = document.getElementById('search-category');
		const searchForm = document.querySelector('form.product-search');
		if (!searchForm || (!textInput && !catSelect)) return;
		let timer;
		function replaceProductGrid(fromDoc){
			const newGrid = fromDoc.querySelector('.product-grid');
			const curGrid = document.querySelector('.product-grid');
			if (newGrid && curGrid) {
				curGrid.innerHTML = newGrid.innerHTML;
				curGrid.querySelectorAll('.product-card').forEach(card => { card.style.opacity = '0'; });
				curGrid.querySelectorAll('.product-card').forEach((card,i)=>{ setTimeout(()=>card.classList.add('animate-card-in'), 80*i); });
				// Rebind add-to-cart inline forms
				curGrid.querySelectorAll('form.add-cart-inline').forEach(form => {
					const qtyInput = form.querySelector('input[name=quantity]');
					const decBtn = form.querySelector('.qty-btn.decrement');
					const incBtn = form.querySelector('.qty-btn.increment');
					if (qtyInput) {
						if (decBtn) decBtn.addEventListener('click', () => { const v = parseInt(qtyInput.value||'1',10)-1; qtyInput.value = Math.max(1, v); });
						if (incBtn) incBtn.addEventListener('click', () => { const v = parseInt(qtyInput.value||'1',10)+1; qtyInput.value = v; });
					}
					form.addEventListener('submit', e => {
						e.preventDefault();
						const fd = new FormData(form); fd.append('ajax','1');
						fetch(form.action, { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} })
							.then(r=>r.json()).then(data => {
								if (data && data.success) {
									const badge = document.querySelector('.cart-count'); if (badge) badge.textContent = data.cartCount;
									const floatBadge = document.querySelector('.floating-cart .float-count'); if (floatBadge) floatBadge.textContent = data.cartCount;
									showFlash('success', data.message || 'Added');
								}
							}).catch(err => { console.error('Add to cart failed', err); showFlash('error','Add to cart failed'); });
					});
				});
				// Rebind admin edit buttons (lost after grid replacement)
				bindEditButtons(curGrid);
			}
		}
		function buildUrl(){
			const baseAction = searchForm.getAttribute('action') || window.location.pathname;
			const params = new URLSearchParams();
			const qVal = textInput ? textInput.value.trim() : '';
			const cVal = catSelect ? catSelect.value : '';
			const sortEl = document.getElementById('search-sort');
			if (qVal) params.set('q', qVal);
			if (cVal) params.set('category_id', cVal);
			let sortVal = sortEl && sortEl.value ? sortEl.value : '';
			if (sortVal) params.set('sort', sortVal);
			return baseAction + (params.toString() ? ('?' + params.toString()) : '');
		}
		function triggerSearch(){
			clearTimeout(timer);
			timer = setTimeout(() => {
				const url = buildUrl();
				fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(r=>r.text())
					.then(html => {
						const parser = new DOMParser();
						const doc = parser.parseFromString(html, 'text/html');
						const newGrid = doc.querySelector('.product-grid');
						if (!newGrid) { window.location.assign(url); return; }
						replaceProductGrid(doc);
						window.history.pushState({}, '', url);
					})
					.catch(() => window.location.assign(url));
			}, 400);
		}
		if (textInput) ['input','change','keyup'].forEach(ev => textInput.addEventListener(ev, triggerSearch));
		// Category changes submit the form natively; no AJAX binding here
		// Sort changes are handled by native form submission via onchange on the select


	})();
	// Initial bind for add-to-cart forms on page load
	const addCartForms = document.querySelectorAll('form.add-cart-inline');
	addCartForms.forEach(form => {
		// Wire +/- controls on product cards
		const qtyInput = form.querySelector('input[name=quantity]');
		const decBtn = form.querySelector('.qty-btn.decrement');
		const incBtn = form.querySelector('.qty-btn.increment');
		if (qtyInput) {
			if (decBtn) decBtn.addEventListener('click', () => { const v = parseInt(qtyInput.value||'1',10)-1; qtyInput.value = Math.max(1, v); });
			if (incBtn) incBtn.addEventListener('click', () => { const v = parseInt(qtyInput.value||'1',10)+1; qtyInput.value = v; });
		}
		form.addEventListener('submit', e => {
			e.preventDefault();
			const fd = new FormData(form);
			fd.append('ajax','1');
			fetch(form.action, {
				method: 'POST',
				body: fd,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			}).then(r => r.json()).then(data => {
				if (data && data.success) {
					// Update cart badge
					const badge = document.querySelector('.cart-count');
					if (badge) badge.textContent = data.cartCount;
					const floatBadge = document.querySelector('.floating-cart .float-count');
					if (floatBadge) floatBadge.textContent = data.cartCount;
					// Flash
					showFlash('success', data.message || 'Added');
				}
			}).catch(err => { console.error('Add to cart failed', err); showFlash('error','Add to cart failed'); });
		});
	});

	