@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div id="panel-products" class="panel active">
    <header class="page-header">
        <div>
            <h2>Products</h2>
            <p>Manage inventory, pricing, and stock levels</p>
        </div>
        <button class="btn btn-primary" id="btn-add-product">
            <span>+</span> Add Product
        </button>
    </header>

    <section class="stats-grid" id="stats-grid">
        <div class="stat-card">
            <span class="stat-label">Total Products</span>
            <span class="stat-value" id="stat-total">0</span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Active</span>
            <span class="stat-value" id="stat-active">0</span>
        </div>
        <div class="stat-card warning">
            <span class="stat-label">Low Stock</span>
            <span class="stat-value" id="stat-low-stock">0</span>
        </div>
        <div class="stat-card accent">
            <span class="stat-label">Inventory Value</span>
            <span class="stat-value" id="stat-value">$0</span>
        </div>
    </section>

    <section class="toolbar">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Search by name or SKU...">
        </div>
        <select id="filter-category">
            <option value="">All Categories</option>
        </select>
        <select id="filter-status">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="draft">Draft</option>
        </select>
    </section>

    <section class="products-grid" id="products-grid">
        <div class="loading-state">Loading products...</div>
    </section>
</div>

<div id="panel-categories" class="panel">
    <header class="page-header">
        <div>
            <h2>Categories</h2>
            <p>Organize products into categories</p>
        </div>
        <button class="btn btn-primary" id="btn-add-category">
            <span>+</span> New Category
        </button>
    </header>

    <section class="categories-list" id="categories-list">
        <div class="loading-state">Loading categories...</div>
    </section>
</div>

<!-- Product Modal -->
<div class="modal-overlay" id="product-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="product-modal-title">Add Product</h3>
            <button class="modal-close" data-close="product-modal">&times;</button>
        </div>
        <form id="product-form" enctype="multipart/form-data">
            <input type="hidden" id="product-id" name="id">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group span-2">
                        <label for="product-name">Product Name *</label>
                        <input type="text" id="product-name" name="name" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label for="product-sku">SKU *</label>
                        <input type="text" id="product-sku" name="sku" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category *</label>
                        <select id="product-category" name="category_id" required></select>
                    </div>
                    <div class="form-group">
                        <label for="product-price">Price ($) *</label>
                        <input type="number" id="product-price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-sale-price">Sale Price ($)</label>
                        <input type="number" id="product-sale-price" name="sale_price" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="product-stock">Stock *</label>
                        <input type="number" id="product-stock" name="stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="product-status">Status</label>
                        <select id="product-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="form-group span-2">
                        <label for="product-sizes">Sizes (comma-separated)</label>
                        <input type="text" id="product-sizes" name="sizes" placeholder="S, M, L, XL">
                    </div>
                    <div class="form-group span-2">
                        <label for="product-colors">Colors (comma-separated)</label>
                        <input type="text" id="product-colors" name="colors" placeholder="Black, White, Navy">
                    </div>
                    <div class="form-group span-2">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" name="description" rows="3" maxlength="1000"></textarea>
                    </div>
                    <div class="form-group span-2">
                        <label>Product Image</label>
                        <input type="hidden" id="product-image-path" name="image_path">
                        <div class="image-upload">
                            <div class="image-preview" id="image-preview">
                                <span class="placeholder-text">No image</span>
                            </div>
                            <div class="image-actions">
                                <input type="file" id="product-image" name="image" accept="image/*" hidden>
                                <button type="button" class="btn btn-secondary btn-sm" id="btn-choose-image">Upload Image</button>
                                <button type="button" class="btn btn-ghost btn-sm" id="btn-remove-image" style="display:none;">Remove</button>
                            </div>
                        </div>
                        <div class="image-library">
                            <span class="image-library-label">Or choose from library</span>
                            <div class="image-library-grid" id="image-library-grid"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close="product-modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="product-submit-btn">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Category Modal -->
<div class="modal-overlay" id="category-modal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3 id="category-modal-title">Add Category</h3>
            <button class="modal-close" data-close="category-modal">&times;</button>
        </div>
        <form id="category-form">
            <input type="hidden" id="category-id" name="id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="category-name">Category Name *</label>
                    <input type="text" id="category-name" name="name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="category-description">Description</label>
                    <textarea id="category-description" name="description" rows="3" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-close="category-modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="delete-modal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" data-close="delete-modal">&times;</button>
        </div>
        <div class="modal-body">
            <p id="delete-message">Are you sure you want to delete this item?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" data-close="delete-modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
        </div>
    </div>
</div>

<!-- Image Zoom Lightbox -->
<div class="image-zoom-overlay" id="image-zoom-modal">
    <button type="button" class="image-zoom-close" id="image-zoom-close" aria-label="Close">&times;</button>
    <div class="image-zoom-content">
        <img id="image-zoom-img" src="" alt="Product image">
        <p id="image-zoom-caption"></p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
@endpush
