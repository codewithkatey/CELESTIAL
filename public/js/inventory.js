(function ($) {
    'use strict';

    const API = {
        products: '/data/products',
        categories: '/data/categories',
        images: '/data/images',
    };

    let categories = [];
    let stockImages = [];
    let deleteCallback = null;
    let removeImageFlag = false;
    let selectedImagePath = '';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
        },
    });

    function init() {
        bindNavigation();
        bindModals();
        bindProductForm();
        bindCategoryForm();
        bindFilters();
        bindImageUpload();
        bindImageZoom();
        bindDelete();

        loadCategories().then(loadStockImages).then(loadProducts);
    }

    function bindNavigation() {
        $('.nav-item').on('click', function () {
            const panel = $(this).data('panel');
            $('.nav-item').removeClass('active');
            $(this).addClass('active');
            $('.panel').removeClass('active');
            $(`#panel-${panel}`).addClass('active');
        });
    }

    function bindModals() {
        $('[data-close]').on('click', function () {
            closeModal($(this).data('close'));
        });

        $('.modal-overlay').on('click', function (e) {
            if (e.target === this) {
                closeModal($(this).attr('id'));
            }
        });

        $('#btn-add-product').on('click', openProductModal);
        $('#btn-add-category').on('click', openCategoryModal);
    }

    function openModal(id) {
        $(`#${id}`).addClass('active');
    }

    function closeModal(id) {
        $(`#${id}`).removeClass('active');
    }

    function showToast(message, type = 'info') {
        const toast = $(`<div class="toast toast-${type}">${escapeHtml(message)}</div>`);
        $('#toast-container').append(toast);
        setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3500);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatPrice(amount) {
        return '$' + parseFloat(amount || 0).toFixed(2);
    }

    function loadCategories() {
        return $.get(API.categories).done(function (res) {
            if (!res.success) return;
            categories = res.data;
            renderCategoryOptions();
            renderCategoriesList();
        }).fail(function () {
            showToast('Failed to load categories', 'error');
        });
    }

    function renderCategoryOptions() {
        const $selects = $('#filter-category, #product-category');
        $selects.each(function () {
            const isFilter = $(this).attr('id') === 'filter-category';
            const current = $(this).val();
            $(this).empty();
            if (isFilter) {
                $(this).append('<option value="">All Categories</option>');
            } else {
                $(this).append('<option value="">Select category</option>');
            }
            categories.forEach(cat => {
                $(this).append(`<option value="${cat.id}">${escapeHtml(cat.name)}</option>`);
            });
            if (current) $(this).val(current);
        });
    }

    function renderCategoriesList() {
        const $list = $('#categories-list');
        if (!categories.length) {
            $list.html('<div class="empty-state"><h4>No categories yet</h4><p>Create your first category to organize products.</p></div>');
            return;
        }

        const html = categories.map(cat => `
            <div class="category-card" data-id="${cat.id}">
                <div>
                    <h4>${escapeHtml(cat.name)}</h4>
                    <p>${escapeHtml(cat.description || 'No description')}</p>
                </div>
                <div class="category-actions">
                    <button class="btn btn-ghost btn-sm btn-edit-category" data-id="${cat.id}">Edit</button>
                    <button class="btn btn-ghost btn-sm btn-delete-category" data-id="${cat.id}" data-name="${escapeHtml(cat.name)}">Delete</button>
                </div>
            </div>
        `).join('');

        $list.html(html);

        $('.btn-edit-category').on('click', function () {
            const cat = categories.find(c => c.id === $(this).data('id'));
            if (cat) editCategory(cat);
        });

        $('.btn-delete-category').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            confirmDelete(`Delete category "${name}"?`, () => deleteCategory(id));
        });
    }

    function loadProducts() {
        const params = {
            search: $('#search-input').val(),
            category_id: $('#filter-category').val(),
            status: $('#filter-status').val(),
        };

        const $grid = $('#products-grid');
        $grid.html('<div class="loading-state">Loading products...</div>');

        $.get(API.products, params).done(function (res) {
            if (!res.success) return;
            updateStats(res.stats);
            renderProducts(res.data);
        }).fail(function () {
            $grid.html('<div class="empty-state"><h4>Failed to load</h4><p>Please refresh the page.</p></div>');
            showToast('Failed to load products', 'error');
        });
    }

    function updateStats(stats) {
        if (!stats) return;
        $('#stat-total').text(stats.total_products);
        $('#stat-active').text(stats.active_products);
        $('#stat-low-stock').text(stats.low_stock);
        $('#stat-value').text(formatPrice(stats.inventory_value));
    }

    function renderProducts(products) {
        const $grid = $('#products-grid');

        if (!products.length) {
            $grid.html('<div class="empty-state"><h4>No products found</h4><p>Add a product or adjust your filters.</p></div>');
            return;
        }

        const html = products.map(p => {
            const isLowStock = (p.stock || 0) <= 5;
            const hasSale = p.sale_price && p.sale_price < p.price;
            const displayPrice = hasSale ? p.sale_price : p.price;
            const statusClass = `badge-${p.status || 'active'}`;

            const sizes = (p.sizes || []).slice(0, 4).map(s => `<span class="tag">${escapeHtml(s)}</span>`).join('');
            const colors = (p.colors || []).slice(0, 3).map(c => `<span class="tag">${escapeHtml(c)}</span>`).join('');

            const imageSrc = p.image_url || p.image;
            const imageHtml = imageSrc
                ? `<button type="button" class="product-image-zoom" data-src="${imageSrc}" aria-label="Zoom ${escapeHtml(p.name)}">
                        <img src="${imageSrc}" alt="${escapeHtml(p.name)}">
                        <span class="zoom-hint">Click to zoom</span>
                   </button>`
                : '<span class="no-image">—</span>';

            return `
                <div class="product-card" data-id="${p.id}">
                    <div class="product-image">
                        ${imageHtml}
                        <span class="product-badge ${statusClass}">${p.status || 'active'}</span>
                        ${isLowStock ? '<span class="product-badge badge-low-stock product-badge-bottom">Low Stock</span>' : ''}
                    </div>
                    <div class="product-body">
                        <div class="product-category">${escapeHtml(p.category_name || 'Uncategorized')}</div>
                        <div class="product-name">${escapeHtml(p.name)}</div>
                        <div class="product-sku">SKU: ${escapeHtml(p.sku)}</div>
                        <div class="product-meta">
                            <div class="product-price">
                                <span class="price">${formatPrice(displayPrice)}</span>
                                ${hasSale ? `<span class="sale-price">${formatPrice(p.price)}</span>` : ''}
                            </div>
                            <div class="product-stock ${isLowStock ? 'low' : ''}">${p.stock} in stock</div>
                        </div>
                        <div class="product-tags">${sizes}${colors}</div>
                        <div class="product-actions">
                            <button class="btn btn-secondary btn-sm btn-edit-product" data-id="${p.id}">Edit</button>
                            <button class="btn btn-ghost btn-sm btn-delete-product" data-id="${p.id}" data-name="${escapeHtml(p.name)}">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $grid.html(html);

        $('.btn-edit-product').on('click', function () {
            editProduct($(this).data('id'));
        });

        $('.btn-delete-product').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            confirmDelete(`Delete product "${name}"? This cannot be undone.`, () => deleteProduct(id));
        });

        $('.product-image-zoom').on('click', function () {
            const $img = $(this).find('img');
            openImageZoom($(this).data('src'), $img.attr('alt') || 'Product image');
        });
    }

    function bindFilters() {
        let searchTimer;
        $('#search-input').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadProducts, 300);
        });
        $('#filter-category, #filter-status').on('change', loadProducts);
    }

    function loadStockImages() {
        return $.get(API.images).done(function (res) {
            if (res.success) {
                stockImages = res.data;
                renderImageLibrary();
            }
        });
    }

    function renderImageLibrary(selectedPath = '') {
        const $grid = $('#image-library-grid');

        if (!stockImages.length) {
            $grid.html('<span class="placeholder-text">No images in library</span>');
            return;
        }

        const html = stockImages.map(img => `
            <button type="button" class="library-thumb ${selectedPath === img.path ? 'selected' : ''}" data-path="${img.path}" data-url="${img.url}">
                <img src="${img.url}" alt="${escapeHtml(img.filename)}">
            </button>
        `).join('');

        $grid.html(html);

        $('.library-thumb').on('click', function () {
            selectLibraryImage($(this).data('path'), $(this).data('url'));
        });
    }

    function selectLibraryImage(path, url) {
        selectedImagePath = path;
        $('#product-image-path').val(path);
        $('#product-image').val('');
        removeImageFlag = false;
        setImagePreview(url, path);
        $('.library-thumb').removeClass('selected');
        $(`.library-thumb[data-path="${path}"]`).addClass('selected');
    }

    function openProductModal(product = null) {
        removeImageFlag = false;
        selectedImagePath = '';
        $('#product-form')[0].reset();
        $('#product-id').val('');
        $('#product-image-path').val('');
        resetImagePreview();
        renderImageLibrary();

        if (product) {
            $('#product-modal-title').text('Edit Product');
            $('#product-id').val(product.id);
            $('#product-name').val(product.name);
            $('#product-sku').val(product.sku);
            $('#product-category').val(product.category_id);
            $('#product-price').val(product.price);
            $('#product-sale-price').val(product.sale_price || '');
            $('#product-stock').val(product.stock);
            $('#product-status').val(product.status || 'active');
            $('#product-sizes').val((product.sizes || []).join(', '));
            $('#product-colors').val((product.colors || []).join(', '));
            $('#product-description').val(product.description || '');
            if (product.image) {
                const previewUrl = product.image_url || product.image;
                setImagePreview(previewUrl, product.image);
                selectedImagePath = product.image;
                $('#product-image-path').val(product.image);
                renderImageLibrary(product.image);
            }
        } else {
            $('#product-modal-title').text('Add Product');
            $('#product-status').val('active');
        }

        openModal('product-modal');
    }

    function editProduct(id) {
        $.get(`${API.products}/${id}`).done(function (res) {
            if (res.success) {
                openProductModal(res.data);
            }
        }).fail(function () {
            showToast('Failed to load product', 'error');
        });
    }

    function bindProductForm() {
        $('#product-form').on('submit', function (e) {
            e.preventDefault();

            const id = $('#product-id').val();
            const formData = new FormData(this);

            const sizes = $('#product-sizes').val().split(',').map(s => s.trim()).filter(Boolean);
            const colors = $('#product-colors').val().split(',').map(c => c.trim()).filter(Boolean);

            formData.delete('sizes');
            formData.delete('colors');
            sizes.forEach(s => formData.append('sizes[]', s));
            colors.forEach(c => formData.append('colors[]', c));

            if (removeImageFlag) {
                formData.append('remove_image', '1');
                formData.delete('image_path');
            } else if (selectedImagePath && !$('#product-image')[0].files.length) {
                formData.set('image_path', selectedImagePath);
            }

            const url = id ? `${API.products}/${id}` : API.products;
            const $btn = $('#product-submit-btn');
            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
            }).done(function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    closeModal('product-modal');
                    loadProducts();
                } else {
                    showToast(res.message || 'Save failed', 'error');
                }
            }).fail(function (xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to save product';
                showToast(msg, 'error');
            }).always(function () {
                $btn.prop('disabled', false).text('Save Product');
            });
        });
    }

    function deleteProduct(id) {
        $.ajax({
            url: `${API.products}/${id}`,
            method: 'DELETE',
        }).done(function (res) {
            if (res.success) {
                showToast(res.message, 'success');
                loadProducts();
            }
        }).fail(function (xhr) {
            showToast(xhr.responseJSON?.message || 'Delete failed', 'error');
        });
    }

    function openCategoryModal(category = null) {
        $('#category-form')[0].reset();
        $('#category-id').val('');

        if (category) {
            $('#category-modal-title').text('Edit Category');
            $('#category-id').val(category.id);
            $('#category-name').val(category.name);
            $('#category-description').val(category.description || '');
        } else {
            $('#category-modal-title').text('Add Category');
        }

        openModal('category-modal');
    }

    function editCategory(category) {
        openCategoryModal(category);
    }

    function bindCategoryForm() {
        $('#category-form').on('submit', function (e) {
            e.preventDefault();

            const id = $('#category-id').val();
            const data = {
                name: $('#category-name').val(),
                description: $('#category-description').val(),
            };

            const url = id ? `${API.categories}/${id}` : API.categories;
            const method = id ? 'PUT' : 'POST';

            $.ajax({ url, method, data }).done(function (res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    closeModal('category-modal');
                    loadCategories().then(loadProducts);
                }
            }).fail(function (xhr) {
                showToast(xhr.responseJSON?.message || 'Failed to save category', 'error');
            });
        });
    }

    function deleteCategory(id) {
        $.ajax({
            url: `${API.categories}/${id}`,
            method: 'DELETE',
        }).done(function (res) {
            if (res.success) {
                showToast(res.message, 'success');
                loadCategories().then(loadProducts);
            }
        }).fail(function (xhr) {
            showToast(xhr.responseJSON?.message || 'Delete failed', 'error');
        });
    }

    function bindImageUpload() {
        $('#btn-choose-image').on('click', () => $('#product-image').click());

        $('#product-image').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            if (file.size > 5 * 1024 * 1024) {
                showToast('Image must be under 5MB', 'error');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                selectedImagePath = '';
                $('#product-image-path').val('');
                $('.library-thumb').removeClass('selected');
                setImagePreview(e.target.result);
                removeImageFlag = false;
            };
            reader.readAsDataURL(file);
        });

        $('#btn-remove-image').on('click', function () {
            $('#product-image').val('');
            $('#product-image-path').val('');
            selectedImagePath = '';
            $('.library-thumb').removeClass('selected');
            resetImagePreview();
            removeImageFlag = true;
        });
    }

    function setImagePreview(src, path = '') {
        $('#image-preview').html(`
            <button type="button" class="image-preview-zoom" data-src="${src}" aria-label="Zoom image">
                <img src="${src}" alt="Preview">
                <span class="zoom-hint">Click to zoom</span>
            </button>
        `);
        $('#btn-remove-image').show();
        if (path) {
            selectedImagePath = path;
            $('#product-image-path').val(path);
        }
    }

    function resetImagePreview() {
        $('#image-preview').html('<span class="placeholder-text">No image</span>');
        $('#btn-remove-image').hide();
        selectedImagePath = '';
        $('#product-image-path').val('');
        $('.library-thumb').removeClass('selected');
    }

    function bindDelete() {
        $('#confirm-delete-btn').on('click', function () {
            if (deleteCallback) {
                deleteCallback();
                deleteCallback = null;
            }
            closeModal('delete-modal');
        });
    }

    function confirmDelete(message, callback) {
        $('#delete-message').text(message);
        deleteCallback = callback;
        openModal('delete-modal');
    }

    function bindImageZoom() {
        $(document).on('click', '.image-preview-zoom', function () {
            openImageZoom($(this).data('src'), $('#product-name').val() || 'Product image');
        });

        $('#image-zoom-close, #image-zoom-modal').on('click', function (e) {
            if (e.target === this || $(e.target).is('#image-zoom-close')) {
                closeImageZoom();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                closeImageZoom();
            }
        });

        $('.image-zoom-content').on('click', function (e) {
            e.stopPropagation();
        });
    }

    function openImageZoom(src, caption = '') {
        if (!src) return;
        $('#image-zoom-img').attr('src', src);
        $('#image-zoom-caption').text(caption || '');
        $('#image-zoom-modal').addClass('active');
        $('body').addClass('zoom-open');
    }

    function closeImageZoom() {
        $('#image-zoom-modal').removeClass('active');
        $('body').removeClass('zoom-open');
        $('#image-zoom-img').attr('src', '');
    }

    $(document).ready(init);
})(jQuery);
