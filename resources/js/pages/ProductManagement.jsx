import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import api from '../lib/api';
import DesignArt from '../components/DesignArt';
import useDocumentMeta from '../hooks/useDocumentMeta';

const STATUS_OPTIONS = ['draft', 'active', 'archived'];
const SIZE_OPTIONS = ['S', 'M', 'L', 'XL', 'XXL'];

const EMPTY_PRODUCT_FORM = {
    design_id: '',
    name: '',
    description: '',
    base_price: '',
    currency: 'USD',
    sku: '',
    status: 'draft',
};

const EMPTY_VARIANT_FORM = {
    size: 'M',
    color: '',
    sku: '',
    stock_quantity: 0,
    price_override: '',
};

export default function ProductManagement() {
    const { t } = useTranslation();

    useDocumentMeta(t('meta_products_management_title', { app: t('app_name') }));

    const [products, setProducts] = useState([]);
    const [productsLoading, setProductsLoading] = useState(true);
    const [designs, setDesigns] = useState([]);

    const [editingProductId, setEditingProductId] = useState(null); // id | 'new' | null
    const [productForm, setProductForm] = useState(EMPTY_PRODUCT_FORM);
    const [productSaving, setProductSaving] = useState(false);
    const [productStatus, setProductStatus] = useState(null); // 'saved' | 'error' | null
    const [confirmDeleteProductId, setConfirmDeleteProductId] = useState(null);

    const [expandedProductId, setExpandedProductId] = useState(null);
    const [editingVariantId, setEditingVariantId] = useState(null); // id | 'new' | null
    const [variantForm, setVariantForm] = useState(EMPTY_VARIANT_FORM);
    const [variantSaving, setVariantSaving] = useState(false);
    const [variantStatus, setVariantStatus] = useState(null); // 'saved' | 'error' | null
    const [confirmDeleteVariantId, setConfirmDeleteVariantId] = useState(null);

    useEffect(() => {
        loadProducts();
        api.get('/api/designs', { params: { status: 'approved' } })
            .then((res) => setDesigns(res.data.data));
    }, []);

    function loadProducts() {
        setProductsLoading(true);
        return api.get('/api/admin/products')
            .then((res) => setProducts(res.data.data))
            .finally(() => setProductsLoading(false));
    }

    function startNewProduct() {
        setEditingProductId('new');
        setProductStatus(null);
        setProductForm(EMPTY_PRODUCT_FORM);
    }

    function startEditProduct(product) {
        setEditingProductId(product.id);
        setProductStatus(null);
        setProductForm({
            design_id: product.design?.id ? String(product.design.id) : '',
            name: product.name,
            description: product.description || '',
            base_price: product.base_price,
            currency: product.currency,
            sku: product.sku,
            status: product.status,
        });
    }

    function cancelProductEdit() {
        setEditingProductId(null);
        setProductStatus(null);
        setProductForm(EMPTY_PRODUCT_FORM);
    }

    function updateProductField(field, value) {
        setProductForm((prev) => ({ ...prev, [field]: value }));
    }

    async function handleProductSubmit(e) {
        e.preventDefault();
        setProductStatus(null);
        setProductSaving(true);
        try {
            const payload = {
                ...productForm,
                design_id: Number(productForm.design_id),
                base_price: Number(productForm.base_price),
            };
            if (editingProductId === 'new') {
                await api.post('/api/admin/products', payload);
            } else {
                const product = products.find((p) => p.id === editingProductId);
                await api.put(`/api/admin/products/${product.slug}`, payload);
            }
            await loadProducts();
            setEditingProductId(null);
            setProductForm(EMPTY_PRODUCT_FORM);
            setProductStatus('saved');
        } catch {
            setProductStatus('error');
        } finally {
            setProductSaving(false);
        }
    }

    async function handleProductDelete(product) {
        setProductStatus(null);
        try {
            await api.delete(`/api/admin/products/${product.slug}`);
            setConfirmDeleteProductId(null);
            await loadProducts();
        } catch {
            setConfirmDeleteProductId(null);
            setProductStatus('error');
        }
    }

    function toggleVariants(product) {
        const next = expandedProductId === product.id ? null : product.id;
        setExpandedProductId(next);
        setEditingVariantId(null);
        setVariantStatus(null);
        setVariantForm(EMPTY_VARIANT_FORM);
    }

    function startNewVariant() {
        setEditingVariantId('new');
        setVariantStatus(null);
        setVariantForm(EMPTY_VARIANT_FORM);
    }

    function startEditVariant(variant) {
        setEditingVariantId(variant.id);
        setVariantStatus(null);
        setVariantForm({
            size: variant.size,
            color: variant.color,
            sku: variant.sku,
            stock_quantity: variant.stock_quantity,
            price_override: variant.price_override ?? '',
        });
    }

    function cancelVariantEdit() {
        setEditingVariantId(null);
        setVariantStatus(null);
        setVariantForm(EMPTY_VARIANT_FORM);
    }

    function updateVariantField(field, value) {
        setVariantForm((prev) => ({ ...prev, [field]: value }));
    }

    async function handleVariantSubmit(e, product) {
        e.preventDefault();
        setVariantStatus(null);
        setVariantSaving(true);
        try {
            const payload = {
                ...variantForm,
                stock_quantity: Number(variantForm.stock_quantity),
                price_override: variantForm.price_override === '' ? null : Number(variantForm.price_override),
            };
            if (editingVariantId === 'new') {
                await api.post(`/api/admin/products/${product.slug}/variants`, payload);
            } else {
                await api.put(`/api/admin/products/${product.slug}/variants/${editingVariantId}`, payload);
            }
            await loadProducts();
            setEditingVariantId(null);
            setVariantForm(EMPTY_VARIANT_FORM);
            setVariantStatus('saved');
        } catch {
            setVariantStatus('error');
        } finally {
            setVariantSaving(false);
        }
    }

    async function handleVariantDelete(product, variant) {
        setVariantStatus(null);
        try {
            await api.delete(`/api/admin/products/${product.slug}/variants/${variant.id}`);
            setConfirmDeleteVariantId(null);
            await loadProducts();
        } catch {
            setConfirmDeleteVariantId(null);
            setVariantStatus('error');
        }
    }

    return (
        <div className="mx-auto max-w-4xl px-6 py-10">
            <h1 className="mb-2 font-serif text-2xl">{t('products_management_title')}</h1>
            <p className="mb-8 max-w-2xl text-sm text-ink-soft">{t('products_management_hint')}</p>

            {productStatus === 'saved' && (
                <p role="status" className="mb-4 text-sm text-green-700">{t('products_management_saved')}</p>
            )}
            {productStatus === 'error' && (
                <p role="alert" className="mb-4 text-sm text-red-700">{t('products_management_error')}</p>
            )}

            {productsLoading ? (
                <p className="text-ink-soft">…</p>
            ) : (
                <ul className="mb-8 space-y-4">
                    {products.length === 0 && (
                        <li className="text-sm text-ink-soft">{t('products_management_empty')}</li>
                    )}
                    {products.map((product) => (
                        <li key={product.id} className="rounded border border-line p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="flex items-center gap-4">
                                    <div className="h-14 w-14 shrink-0">
                                        <DesignArt motif={product.design?.mockup_url} className="rounded" />
                                    </div>
                                    <div>
                                        <p className="font-medium">
                                            {product.name}
                                            <span className="ms-2 text-xs text-ink-soft">
                                                {t(`products_management_status_${product.status}`)}
                                            </span>
                                        </p>
                                        <p className="mt-1 text-sm text-ink-soft">
                                            {product.sku} · {product.currency} {Number(product.base_price).toFixed(2)}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-3">
                                    <button type="button" onClick={() => toggleVariants(product)} className="text-sm underline">
                                        {expandedProductId === product.id
                                            ? t('products_management_variants_hide')
                                            : t('products_management_variants_show', { count: product.variants?.length || 0 })}
                                    </button>
                                    <button type="button" onClick={() => startEditProduct(product)} className="text-sm underline">
                                        {t('products_management_edit')}
                                    </button>
                                    {confirmDeleteProductId === product.id ? (
                                        <span className="flex gap-2 text-sm">
                                            <button
                                                type="button"
                                                onClick={() => handleProductDelete(product)}
                                                className="text-red-700 underline"
                                            >
                                                {t('products_management_confirm_delete_yes')}
                                            </button>
                                            <button type="button" onClick={() => setConfirmDeleteProductId(null)} className="underline">
                                                {t('products_management_confirm_delete_no')}
                                            </button>
                                        </span>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => setConfirmDeleteProductId(product.id)}
                                            className="text-sm text-red-700 underline"
                                        >
                                            {t('products_management_delete')}
                                        </button>
                                    )}
                                </div>
                            </div>

                            {expandedProductId === product.id && (
                                <div className="mt-4 border-t border-line pt-4">
                                    {variantStatus === 'saved' && (
                                        <p role="status" className="mb-3 text-sm text-green-700">{t('products_management_variant_saved')}</p>
                                    )}
                                    {variantStatus === 'error' && (
                                        <p role="alert" className="mb-3 text-sm text-red-700">{t('products_management_variant_error')}</p>
                                    )}

                                    {(product.variants || []).length === 0 && (
                                        <p className="mb-3 text-sm text-ink-soft">{t('products_management_variants_empty')}</p>
                                    )}
                                    {(product.variants || []).length > 0 && (
                                        <div className="mb-3 overflow-x-auto rounded border border-line">
                                            <table className="w-full text-sm">
                                                <thead className="bg-parchment-dim text-left">
                                                    <tr>
                                                        <th className="px-3 py-2">{t('products_management_variant_size_label')}</th>
                                                        <th className="px-3 py-2">{t('products_management_variant_color_label')}</th>
                                                        <th className="px-3 py-2">{t('products_management_variant_sku_label')}</th>
                                                        <th className="px-3 py-2">{t('products_management_variant_stock_label')}</th>
                                                        <th className="px-3 py-2" />
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {product.variants.map((variant) => (
                                                        <tr key={variant.id} className="border-t border-line">
                                                            <td className="px-3 py-2">{variant.size}</td>
                                                            <td className="px-3 py-2">{variant.color}</td>
                                                            <td className="px-3 py-2">{variant.sku}</td>
                                                            <td className="px-3 py-2">{variant.stock_quantity}</td>
                                                            <td className="px-3 py-2 text-end">
                                                                <div className="flex justify-end gap-3">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => startEditVariant(variant)}
                                                                        className="text-sm underline"
                                                                    >
                                                                        {t('products_management_variant_edit')}
                                                                    </button>
                                                                    {confirmDeleteVariantId === variant.id ? (
                                                                        <span className="flex gap-2 text-sm">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => handleVariantDelete(product, variant)}
                                                                                className="text-red-700 underline"
                                                                            >
                                                                                {t('products_management_confirm_delete_yes')}
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => setConfirmDeleteVariantId(null)}
                                                                                className="underline"
                                                                            >
                                                                                {t('products_management_confirm_delete_no')}
                                                                            </button>
                                                                        </span>
                                                                    ) : (
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => setConfirmDeleteVariantId(variant.id)}
                                                                            className="text-sm text-red-700 underline"
                                                                        >
                                                                            {t('products_management_variant_delete')}
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}

                                    {editingVariantId === null && (
                                        <button
                                            type="button"
                                            onClick={startNewVariant}
                                            className="rounded border border-ink px-4 py-2 text-sm tracking-wide uppercase hover:bg-parchment-dim"
                                        >
                                            {t('products_management_variant_add')}
                                        </button>
                                    )}

                                    {editingVariantId !== null && (
                                        <form
                                            onSubmit={(e) => handleVariantSubmit(e, product)}
                                            className="max-w-xl space-y-4 rounded border border-line p-4"
                                        >
                                            <h3 className="font-serif text-base">
                                                {editingVariantId === 'new'
                                                    ? t('products_management_variant_new_title')
                                                    : t('products_management_variant_edit')}
                                            </h3>
                                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label htmlFor="variant-size" className="mb-1 block text-sm">
                                                        {t('products_management_variant_size_label')}
                                                    </label>
                                                    <select
                                                        id="variant-size"
                                                        value={variantForm.size}
                                                        onChange={(e) => updateVariantField('size', e.target.value)}
                                                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                                                    >
                                                        {SIZE_OPTIONS.map((size) => (
                                                            <option key={size} value={size}>{size}</option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div>
                                                    <label htmlFor="variant-color" className="mb-1 block text-sm">
                                                        {t('products_management_variant_color_label')}
                                                    </label>
                                                    <input
                                                        id="variant-color"
                                                        type="text"
                                                        required
                                                        value={variantForm.color}
                                                        onChange={(e) => updateVariantField('color', e.target.value)}
                                                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label htmlFor="variant-sku" className="mb-1 block text-sm">
                                                        {t('products_management_variant_sku_label')}
                                                    </label>
                                                    <input
                                                        id="variant-sku"
                                                        type="text"
                                                        required
                                                        value={variantForm.sku}
                                                        onChange={(e) => updateVariantField('sku', e.target.value)}
                                                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label htmlFor="variant-stock" className="mb-1 block text-sm">
                                                        {t('products_management_variant_stock_label')}
                                                    </label>
                                                    <input
                                                        id="variant-stock"
                                                        type="number"
                                                        min="0"
                                                        required
                                                        value={variantForm.stock_quantity}
                                                        onChange={(e) => updateVariantField('stock_quantity', e.target.value)}
                                                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label htmlFor="variant-price-override" className="mb-1 block text-sm">
                                                        {t('products_management_variant_price_override_label')}
                                                    </label>
                                                    <input
                                                        id="variant-price-override"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={variantForm.price_override}
                                                        onChange={(e) => updateVariantField('price_override', e.target.value)}
                                                        className="w-full rounded border border-line bg-parchment px-3 py-2"
                                                    />
                                                </div>
                                            </div>
                                            <div className="flex gap-4">
                                                <button
                                                    type="submit"
                                                    disabled={variantSaving}
                                                    className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                                                >
                                                    {t('products_management_variant_save')}
                                                </button>
                                                <button type="button" onClick={cancelVariantEdit} className="text-sm underline">
                                                    {t('products_management_variant_cancel')}
                                                </button>
                                            </div>
                                        </form>
                                    )}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {editingProductId === null && (
                <button
                    type="button"
                    onClick={startNewProduct}
                    className="rounded border border-ink px-5 py-2.5 text-sm tracking-wide uppercase hover:bg-parchment-dim"
                >
                    {t('products_management_add')}
                </button>
            )}

            {editingProductId !== null && (
                <form onSubmit={handleProductSubmit} className="max-w-xl space-y-4 rounded border border-line p-5">
                    <h3 className="font-serif text-base">
                        {editingProductId === 'new' ? t('products_management_new_title') : t('products_management_edit')}
                    </h3>
                    <div>
                        <label htmlFor="product-name" className="mb-1 block text-sm">
                            {t('products_management_name_label')}
                        </label>
                        <input
                            id="product-name"
                            type="text"
                            required
                            value={productForm.name}
                            onChange={(e) => updateProductField('name', e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>
                    <div>
                        <label htmlFor="product-description" className="mb-1 block text-sm">
                            {t('products_management_description_label')}
                        </label>
                        <textarea
                            id="product-description"
                            rows={3}
                            value={productForm.description}
                            onChange={(e) => updateProductField('description', e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2"
                        />
                    </div>
                    <div>
                        <label htmlFor="product-design" className="mb-1 block text-sm">
                            {t('products_management_design_label')}
                        </label>
                        <div className="flex items-center gap-3">
                            <select
                                id="product-design"
                                required
                                value={productForm.design_id}
                                onChange={(e) => updateProductField('design_id', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            >
                                <option value="" disabled>{t('products_management_design_placeholder')}</option>
                                {designs.map((design) => (
                                    <option key={design.id} value={design.id}>{design.title}</option>
                                ))}
                            </select>
                            {productForm.design_id && (
                                <div className="h-12 w-12 shrink-0">
                                    <DesignArt
                                        motif={designs.find((d) => String(d.id) === String(productForm.design_id))?.mockup_url}
                                        className="rounded"
                                    />
                                </div>
                            )}
                        </div>
                        <p className="mt-1 text-xs text-ink-soft">{t('products_management_design_hint')}</p>
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label htmlFor="product-price" className="mb-1 block text-sm">
                                {t('products_management_price_label')}
                            </label>
                            <input
                                id="product-price"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                value={productForm.base_price}
                                onChange={(e) => updateProductField('base_price', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="product-currency" className="mb-1 block text-sm">
                                {t('products_management_currency_label')}
                            </label>
                            <input
                                id="product-currency"
                                type="text"
                                maxLength={3}
                                required
                                value={productForm.currency}
                                onChange={(e) => updateProductField('currency', e.target.value.toUpperCase())}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                        <div>
                            <label htmlFor="product-sku" className="mb-1 block text-sm">
                                {t('products_management_sku_label')}
                            </label>
                            <input
                                id="product-sku"
                                type="text"
                                required
                                value={productForm.sku}
                                onChange={(e) => updateProductField('sku', e.target.value)}
                                className="w-full rounded border border-line bg-parchment px-3 py-2"
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="product-status" className="mb-1 block text-sm">
                            {t('products_management_status_label')}
                        </label>
                        <select
                            id="product-status"
                            value={productForm.status}
                            onChange={(e) => updateProductField('status', e.target.value)}
                            className="w-full rounded border border-line bg-parchment px-3 py-2 sm:w-48"
                        >
                            {STATUS_OPTIONS.map((status) => (
                                <option key={status} value={status}>{t(`products_management_status_${status}`)}</option>
                            ))}
                        </select>
                    </div>
                    <div className="flex gap-4">
                        <button
                            type="submit"
                            disabled={productSaving}
                            className="rounded bg-ink px-5 py-2.5 text-sm tracking-wide text-parchment uppercase hover:bg-ink-soft disabled:opacity-50"
                        >
                            {t('products_management_save')}
                        </button>
                        <button type="button" onClick={cancelProductEdit} className="text-sm underline">
                            {t('products_management_cancel')}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}
