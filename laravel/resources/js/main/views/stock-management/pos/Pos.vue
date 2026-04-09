<template>
    <!-- ── Top Header Bar ──────────────────────────────────────── -->
    <div class="pos-topbar">
        <div class="pos-topbar-left">
            <button class="pos-back-btn" @click="() => $router.go(-1)"><LeftOutlined /></button>
            <span class="pos-topbar-title"><ShoppingCartOutlined /> Point of Sale</span>
            <span class="pos-branch-chip"><BankOutlined /> {{ selectedWarehouse?.name || 'Branch' }}</span>
        </div>
        <div class="pos-topbar-right">
            <template v-if="cashRegister">
                <span class="pos-reg-open"><span class="pos-reg-dot pos-dot-green"></span> Register Open</span>
                <button class="pos-reg-close-btn" @click="cashRegisterCloseVis = true"><LockOutlined /> Close Register</button>
            </template>
            <template v-else>
                <button class="pos-reg-open-btn" @click="cashRegisterOpenVis = true">
                    <span class="pos-reg-dot pos-dot-red"></span> Register Closed — Open Now
                </button>
            </template>
        </div>
    </div>

    <!-- ── Desktop Layout ─────────────────────────────────────── -->
    <div v-if="innerWidth >= 768" class="pos-layout">

        <!-- LEFT: Cart Panel -->
        <div class="pos-cart-panel">

            <!-- Customer Section -->
            <div class="pos-section pos-cust-section">
                <div class="pos-section-title"><UserOutlined /> Customer</div>
                <div class="pos-cust-inputs">
                    <a-input
                        v-model:value="quickAddPhone"
                        placeholder="Phone Number"
                        class="pos-input"
                        allow-clear
                        @change="searchCustomerByPhone"
                    >
                        <template #prefix><PhoneOutlined class="pos-input-icon" /></template>
                    </a-input>
                    <a-input
                        v-model:value="quickAddName"
                        placeholder="Customer Name"
                        class="pos-input"
                    >
                        <template #prefix><UserOutlined class="pos-input-icon" /></template>
                    </a-input>
                </div>
                <div v-if="selectedCustomerInfo" class="pos-cust-found">
                    <div class="pos-cust-avatar">{{ selectedCustomerInfo.name.charAt(0).toUpperCase() }}</div>
                    <div class="pos-cust-info">
                        <div class="pos-cust-name">{{ selectedCustomerInfo.name }}</div>
                        <div v-if="selectedCustomerInfo.phone" class="pos-cust-phone">{{ selectedCustomerInfo.phone }}</div>
                    </div>
                    <button class="pos-cust-clear" @click="clearSelectedCustomer"><CloseOutlined /></button>
                </div>
                <div v-else-if="phoneSearchDone && quickAddPhone.length >= 3" class="pos-cust-new">
                    <PlusCircleOutlined /> New customer — will be created on checkout
                </div>
            </div>

            <!-- Product Search -->
            <div class="pos-section pos-search-section">
                <div class="pos-section-title"><SearchOutlined /> Product Search / Scan</div>
                <a-select
                    :value="null"
                    :searchValue="orderSearchTerm"
                    show-search
                    :filter-option="false"
                    :placeholder="$t('product.search_scan_product')"
                    class="pos-search-select"
                    :not-found-content="productFetching ? undefined : null"
                    @search="(v) => { orderSearchTerm = v; fetchProducts(v); }"
                    option-label-prop="label"
                    @focus="products = []"
                    @select="searchValueSelected"
                    @inputKeyDown="inputValueChanged"
                >
                    <template #suffixIcon><BarcodeOutlined class="pos-search-icon" /></template>
                    <template v-if="productFetching" #notFoundContent><a-spin size="small" /></template>
                    <a-select-option v-for="product in products" :key="product.xid" :value="product.xid" :label="product.name" :product="product">
                        <span class="pos-product-opt"><TagOutlined style="margin-right:6px;color:#0d9488" /> {{ product.name }}</span>
                    </a-select-option>
                </a-select>
            </div>

            <!-- Cart Table -->
            <div class="pos-section pos-cart-section">
                <div class="pos-section-title">
                    <ShoppingCartOutlined /> Cart
                    <span class="pos-cart-count" v-if="selectedProducts.length">{{ selectedProducts.length }}</span>
                </div>
                <a-form layout="vertical">
                    <div v-if="selectedProducts.length === 0" class="pos-empty-cart">
                        <ShoppingCartOutlined class="pos-empty-icon" />
                        <div class="pos-empty-text">Cart is empty</div>
                        <div class="pos-empty-sub">Search or scan a product to begin</div>
                    </div>
                    <div v-else class="pos-cart-items">
                        <div v-for="record in selectedProducts" :key="record.cart_key || record.xid" class="pos-cart-item">
                            <div class="pos-ci-left">
                                <div class="pos-ci-name">{{ record.name }}</div>
                                <div class="pos-ci-meta">
                                    <span v-if="record.product_type !== 'service'" class="pos-ci-stock">
                                        Stock: {{ record.stock_quantity }}{{ record.unit_short_name }}
                                    </span>
                                    <span v-if="record.warehouse_name" class="pos-ci-wh"><BankOutlined /> {{ record.warehouse_name }}</span>
                                </div>
                            </div>
                            <div class="pos-ci-right">
                                <a-input-number
                                    v-model:value="record.quantity"
                                    :min="0"
                                    class="pos-qty-input"
                                    @change="quantityChanged(record)"
                                />
                                <a-input-number
                                    v-model:value="record.unit_price"
                                    :min="0" :step="1" :precision="2"
                                    class="pos-price-input"
                                    @change="quantityChanged(record)"
                                />
                                <div class="pos-ci-sub">{{ formatAmountCurrency(record.subtotal) }}</div>
                                <div class="pos-ci-actions">
                                    <button class="pos-ci-edit" @click="editItem(record)"><EditOutlined /></button>
                                    <button class="pos-ci-del" @click="showDeleteConfirm(record)"><DeleteOutlined /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </a-form>
            </div>

            <!-- Totals & Checkout -->
            <div class="pos-checkout-panel">
                <!-- Adjustments row -->
                <div class="pos-adj-row">
                    <div class="pos-adj-field">
                        <label class="pos-adj-lbl">{{ $t('stock.order_tax') }}</label>
                        <a-select v-model:value="formData.tax_id" :allowClear="true" class="pos-adj-select" @change="taxChanged" :placeholder="$t('common.select_default_text', [$t('stock.order_tax')])">
                            <a-select-option v-for="tax in taxes" :key="tax.xid" :value="tax.xid" :tax="tax">{{ tax.name }} ({{ tax.rate }}%)</a-select-option>
                        </a-select>
                    </div>
                    <div class="pos-adj-field">
                        <label class="pos-adj-lbl">{{ $t('stock.discount') }}</label>
                        <div class="pos-disc-wrap">
                            <a-select v-model:value="formData.discount_type" class="pos-disc-type" @change="recalculateFinalTotal">
                                <a-select-option value="percentage">%</a-select-option>
                                <a-select-option value="fixed">{{ appSetting.currency.symbol }}</a-select-option>
                            </a-select>
                            <a-input-number v-model:value="formData.discount_value" :min="0" class="pos-disc-val" @change="recalculateFinalTotal" />
                        </div>
                    </div>
                    <div class="pos-adj-field">
                        <label class="pos-adj-lbl">{{ $t('stock.shipping') }}</label>
                        <a-input-number v-model:value="formData.shipping" :min="0" class="pos-adj-input" @change="recalculateFinalTotal">
                            <template #addonBefore>{{ appSetting.currency.symbol }}</template>
                        </a-input-number>
                    </div>
                </div>

                <!-- Summary bar -->
                <div class="pos-summary-bar">
                    <div class="pos-summary-item">
                        <span class="pos-sum-lbl">Tax</span>
                        <span class="pos-sum-val">{{ formatAmountCurrency(formData.tax_amount) }}</span>
                    </div>
                    <div class="pos-summary-item">
                        <span class="pos-sum-lbl">Discount</span>
                        <span class="pos-sum-val">{{ formatAmountCurrency(formData.discount) }}</span>
                    </div>
                    <div class="pos-summary-total">
                        <span class="pos-total-lbl">Grand Total</span>
                        <span class="pos-total-val">{{ formatAmountCurrency(formData.subtotal) }}</span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="pos-action-btns">
                    <button class="pos-btn-reset" @click="resetPos"><RedoOutlined /> Reset</button>
                    <button class="pos-btn-quote" :disabled="formData.subtotal <= 0 || (!formData.user_id && quickAddPhone.length < 3)" @click="viewQuote">
                        <FileTextOutlined /> Quote
                    </button>
                    <button class="pos-btn-pay" :disabled="formData.subtotal <= 0 || (!formData.user_id && quickAddPhone.length < 3)" @click="payNow">
                        <CreditCardOutlined /> Pay Now
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT: Product Grid Panel -->
        <div class="pos-products-panel">
            <perfect-scrollbar :options="{ wheelSpeed:1, swipeEasing:true, suppressScrollX:true }">
                <PosLayout1 v-if="postLayout == 1" :brands="brands" :categories="categories" :formData="formData" @changed="reFetchProducts" />
                <PosLayout2 v-else :brands="brands" :categories="categories" :formData="formData" @changed="reFetchProducts" />

                <div v-if="productLists.length > 0" class="pos-product-grid">
                    <div v-for="item in productLists" :key="item.xid" class="pos-product-col" @click="showStockPopup(item)">
                        <ProductCardNew :product="item" :isAdded="selectedProducts.some(p => p.xid === item.xid)" />
                    </div>
                </div>
                <div v-else class="pos-no-products">
                    <a-result :title="$t('stock.no_product_found')" />
                </div>
            </perfect-scrollbar>
        </div>
    </div>

    <!-- ── Mobile Layout ──────────────────────────────────────── -->
    <div v-else class="pos-mobile">
        <!-- Customer -->
        <div class="pos-section pos-cust-section">
            <div class="pos-section-title"><UserOutlined /> Customer</div>
            <div class="pos-cust-inputs">
                <a-input v-model:value="quickAddPhone" placeholder="Phone Number" class="pos-input" allow-clear @change="searchCustomerByPhone">
                    <template #prefix><PhoneOutlined class="pos-input-icon" /></template>
                </a-input>
                <a-input v-model:value="quickAddName" placeholder="Customer Name" class="pos-input">
                    <template #prefix><UserOutlined class="pos-input-icon" /></template>
                </a-input>
            </div>
            <div v-if="selectedCustomerInfo" class="pos-cust-found">
                <div class="pos-cust-avatar">{{ selectedCustomerInfo.name.charAt(0).toUpperCase() }}</div>
                <div class="pos-cust-info">
                    <div class="pos-cust-name">{{ selectedCustomerInfo.name }}</div>
                    <div v-if="selectedCustomerInfo.phone" class="pos-cust-phone">{{ selectedCustomerInfo.phone }}</div>
                </div>
                <button class="pos-cust-clear" @click="clearSelectedCustomer"><CloseOutlined /></button>
            </div>
            <div v-else-if="phoneSearchDone && quickAddPhone.length >= 3" class="pos-cust-new">
                <PlusCircleOutlined /> New customer — will be created on checkout
            </div>
        </div>

        <!-- Search + toggle -->
        <div class="pos-mob-search-row">
            <a-select :value="null" :searchValue="orderSearchTerm" show-search :filter-option="false" :placeholder="$t('product.search_scan_product')"
                class="pos-mob-search" :not-found-content="productFetching ? undefined : null"
                @search="(v) => { orderSearchTerm = v; fetchProducts(v); }" option-label-prop="label"
                @focus="products = []" @select="searchValueSelected" @inputKeyDown="inputValueChanged">
                <template #suffixIcon><BarcodeOutlined /></template>
                <template v-if="productFetching" #notFoundContent><a-spin size="small" /></template>
                <a-select-option v-for="product in products" :key="product.xid" :value="product.xid" :label="product.name" :product="product">
                    {{ product.name }}
                </a-select-option>
            </a-select>
            <button class="pos-mob-toggle" @click="() => (showMobileCart = !showMobileCart)">
                <ShoppingCartOutlined v-if="!showMobileCart" />
                <UnorderedListOutlined v-else />
            </button>
        </div>

        <!-- Products or Cart -->
        <div v-if="!showMobileCart">
            <PosLayout1 v-if="postLayout == 1" :brands="brands" :categories="categories" :formData="formData" @changed="reFetchProducts" />
            <PosLayout2 v-else :brands="brands" :categories="categories" :formData="formData" @changed="reFetchProducts" />
            <div v-if="productLists.length > 0" class="pos-mob-grid">
                <div v-for="item in productLists" :key="item.xid" class="pos-mob-prod-col" @click="showStockPopup(item)">
                    <ProductCardNew :product="item" :isAdded="selectedProducts.some(p => p.xid === item.xid)" />
                </div>
            </div>
        </div>
        <div v-else class="pos-cart-items pos-mob-cart">
            <div v-for="record in selectedProducts" :key="record.cart_key || record.xid" class="pos-cart-item">
                <div class="pos-ci-left">
                    <div class="pos-ci-name">{{ record.name }}</div>
                    <div class="pos-ci-meta">
                        <span v-if="record.product_type !== 'service'" class="pos-ci-stock">Stock: {{ record.stock_quantity }}{{ record.unit_short_name }}</span>
                        <span v-if="record.warehouse_name" class="pos-ci-wh"><BankOutlined /> {{ record.warehouse_name }}</span>
                    </div>
                </div>
                <div class="pos-ci-right">
                    <a-input-number v-model:value="record.quantity" :min="0" class="pos-qty-input" @change="quantityChanged(record)" />
                    <a-input-number v-model:value="record.unit_price" :min="0" :step="1" :precision="2" class="pos-price-input" @change="quantityChanged(record)" />
                    <div class="pos-ci-sub">{{ formatAmountCurrency(record.subtotal) }}</div>
                    <div class="pos-ci-actions">
                        <button class="pos-ci-edit" @click="editItem(record)"><EditOutlined /></button>
                        <button class="pos-ci-del" @click="showDeleteConfirm(record)"><DeleteOutlined /></button>
                    </div>
                </div>
            </div>
            <!-- Adjustments -->
            <div class="pos-adj-row pos-adj-mobile">
                <div class="pos-adj-field">
                    <label class="pos-adj-lbl">{{ $t('stock.order_tax') }}</label>
                    <a-select v-model:value="formData.tax_id" :allowClear="true" class="pos-adj-select" @change="taxChanged">
                        <a-select-option v-for="tax in taxes" :key="tax.xid" :value="tax.xid" :tax="tax">{{ tax.name }} ({{ tax.rate }}%)</a-select-option>
                    </a-select>
                </div>
                <div class="pos-adj-field">
                    <label class="pos-adj-lbl">{{ $t('stock.discount') }}</label>
                    <div class="pos-disc-wrap">
                        <a-select v-model:value="formData.discount_type" class="pos-disc-type" @change="recalculateFinalTotal">
                            <a-select-option value="percentage">%</a-select-option>
                            <a-select-option value="fixed">{{ appSetting.currency.symbol }}</a-select-option>
                        </a-select>
                        <a-input-number v-model:value="formData.discount_value" :min="0" class="pos-disc-val" @change="recalculateFinalTotal" />
                    </div>
                </div>
                <div class="pos-adj-field">
                    <label class="pos-adj-lbl">{{ $t('stock.shipping') }}</label>
                    <a-input-number v-model:value="formData.shipping" :min="0" class="pos-adj-input" @change="recalculateFinalTotal">
                        <template #addonBefore>{{ appSetting.currency.symbol }}</template>
                    </a-input-number>
                </div>
            </div>
        </div>

        <!-- Mobile footer -->
        <div class="pos-mob-footer">
            <div class="pos-mob-total">
                <span class="pos-mob-total-lbl">Total</span>
                <span class="pos-mob-total-val">{{ formatAmountCurrency(formData.subtotal) }}</span>
            </div>
            <div class="pos-mob-btns">
                <button class="pos-btn-reset pos-btn-sm" @click="resetPos"><RedoOutlined /></button>
                <button class="pos-btn-quote pos-btn-sm" :disabled="formData.subtotal<=0 || (!formData.user_id && quickAddPhone.length<3)" @click="viewQuote"><FileTextOutlined /></button>
                <button class="pos-btn-pay pos-btn-sm" :disabled="formData.subtotal<=0 || (!formData.user_id && quickAddPhone.length<3)" @click="payNow"><CreditCardOutlined /> Pay</button>
            </div>
        </div>
    </div>

    <!-- ── Edit Item Modal ───────────────────────────────────── -->
    <a-modal :open="addEditVisible" :closable="false" :centered="true" :footer="null" :title="null" width="420px" class="pos-modal">
        <div class="pos-modal-hd">
            <div class="pos-modal-hd-icon"><EditOutlined /></div>
            <div>
                <div class="pos-modal-hd-title">Edit Item</div>
                <div class="pos-modal-hd-sub">{{ addEditPageTitle }}</div>
            </div>
        </div>
        <div class="pos-modal-body">
            <a-form layout="vertical">
                <a-form-item :label="$t('product.unit_price')" :help="addEditRules.unit_price?.message" :validateStatus="addEditRules.unit_price?'error':null">
                    <a-input-number v-model:value="addEditFormData.unit_price" :min="0" style="width:100%">
                        <template #addonBefore>{{ appSetting.currency.symbol }}</template>
                    </a-input-number>
                </a-form-item>
                <a-form-item :label="$t('product.discount')" :help="addEditRules.discount_rate?.message" :validateStatus="addEditRules.discount_rate?'error':null">
                    <a-input-number v-model:value="addEditFormData.discount_rate" :min="0" style="width:100%">
                        <template #addonAfter>%</template>
                    </a-input-number>
                </a-form-item>
                <a-form-item :label="$t('product.tax')" :help="addEditRules.tax_id?.message" :validateStatus="addEditRules.tax_id?'error':null">
                    <a-select v-model:value="addEditFormData.tax_id" :allowClear="true" style="width:100%">
                        <a-select-option v-for="tax in taxes" :key="tax.xid" :value="tax.xid">{{ tax.name }} ({{ tax.rate }}%)</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item :label="$t('product.tax_type')" :help="addEditRules.tax_type?.message" :validateStatus="addEditRules.tax_type?'error':null">
                    <a-select v-model:value="addEditFormData.tax_type" :allowClear="true" style="width:100%">
                        <a-select-option v-for="taxType in taxTypes" :key="taxType.key" :value="taxType.key">{{ taxType.value }}</a-select-option>
                    </a-select>
                </a-form-item>
            </a-form>
        </div>
        <div class="pos-modal-footer">
            <button class="pos-mf-cancel" @click="onAddEditClose">Cancel</button>
            <button class="pos-mf-save" :disabled="addEditFormSubmitting" @click="onAddEditSubmit"><SaveOutlined /> Update</button>
        </div>
    </a-modal>

    <!-- ── Stock Availability Popup ──────────────────────────── -->
    <a-modal :open="stockPopupVisible" :centered="true" :maskClosable="true" :footer="null" :title="null" width="500px" class="pos-modal" @cancel="stockPopupVisible = false">
        <div class="pos-modal-hd pos-modal-hd-stock">
            <div class="pos-modal-hd-icon pos-modal-hd-icon-green"><BankOutlined /></div>
            <div>
                <div class="pos-modal-hd-title">Stock Availability</div>
                <div class="pos-modal-hd-sub">{{ stockPopupProduct?.name }}</div>
            </div>
        </div>
        <div class="pos-modal-body">
            <p class="pos-stock-hint">Select a warehouse to sell from. Only warehouses with available stock can be selected.</p>
            <a-spin :spinning="stockPopupLoading">
                <div class="pos-wh-list">
                    <div
                        v-for="row in stockPopupData" :key="row.warehouse_xid"
                        class="pos-wh-card"
                        :class="{ 'pos-wh-selected': selectedPopupWarehouseXid === row.warehouse_xid, 'pos-wh-disabled': row.stock_quantity <= 0 }"
                        @click="row.stock_quantity > 0 && selectPopupWarehouse(row)"
                    >
                        <div class="pos-wh-radio">
                            <a-radio :value="row.warehouse_xid" :checked="selectedPopupWarehouseXid === row.warehouse_xid" :disabled="row.stock_quantity <= 0" />
                        </div>
                        <div class="pos-wh-info">
                            <div class="pos-wh-name">{{ row.warehouse_name }}</div>
                        </div>
                        <div class="pos-wh-qty">
                            <span class="pos-qty-badge" :class="row.stock_quantity > 0 ? 'pos-qty-ok' : 'pos-qty-zero'">
                                {{ row.stock_quantity }}
                            </span>
                            <span class="pos-qty-unit">units</span>
                        </div>
                    </div>
                    <div v-if="!stockPopupLoading && stockPopupData.length === 0" class="pos-wh-empty">
                        No warehouse data found
                    </div>
                </div>
                <div v-if="!stockPopupLoading && selectedPopupWarehouseXid" class="pos-wh-selected-info">
                    <CheckCircleOutlined style="color:#16a34a;margin-right:6px" />
                    Selected: <strong>{{ selectedPopupWarehouseName }}</strong>
                </div>
            </a-spin>
        </div>
        <div class="pos-modal-footer">
            <button class="pos-mf-cancel" @click="stockPopupVisible = false">Cancel</button>
            <button class="pos-mf-save" :disabled="stockPopupLoading || !selectedPopupWarehouseXid" @click="addToCartFromPopup">
                <ShoppingCartOutlined /> Add to Cart
            </button>
        </div>
    </a-modal>

    <!-- Cash Register Modals -->
    <CashRegisterOpen :visible="cashRegisterOpenVis" @opened="onRegisterOpened" />
    <CashRegisterClose :visible="cashRegisterCloseVis" @cancelled="cashRegisterCloseVis = false" @closed="onRegisterClosed" />

    <PayNow
        :visible="payNowVisible"
        @closed="payNowClosed"
        @success="payNowSuccess"
        :data="formData"
        :selectedProducts="selectedProducts"
        :sellingWarehouseXid="selectedPosWarehouseXid"
        :quickAddPhone="quickAddPhone"
        :quickAddName="quickAddName"
    />

    <InvoiceModal
        :visible="printInvoiceModalVisible"
        :order="printInvoiceOrder"
        :sellingWarehouseName="selectedPosWarehouseName"
        @closed="printInvoiceModalVisible = false"
    />
</template>

<script>
import { ref, onMounted, reactive, toRefs, nextTick } from "vue";
import {
    ShoppingCartOutlined,
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    SearchOutlined,
    SaveOutlined,
    SettingOutlined,
    UnorderedListOutlined,
    LeftOutlined,
    BankOutlined,
    LockOutlined,
    UserOutlined,
    PhoneOutlined,
    CloseOutlined,
    PlusCircleOutlined,
    BarcodeOutlined,
    TagOutlined,
    CreditCardOutlined,
    FileTextOutlined,
    RedoOutlined,
    CheckCircleOutlined,
} from "@ant-design/icons-vue";
import { debounce } from "lodash-es";
import { useI18n } from "vue-i18n";
import { message } from "ant-design-vue";
import { includes, find } from "lodash-es";
import common from "../../../../common/composable/common";
import { OrderSummary } from "../../../../common/components/product/style";
import fields from "./fields";
import ProductCardNew from "../../../../common/components/product/ProductCardNew.vue";
import PayNow from "./PayNow.vue";
import CashRegisterOpen from "./CashRegisterOpen.vue";
import CashRegisterClose from "./CashRegisterClose.vue";
import CustomerAddButton from "../../users/CustomerAddButton.vue";
import InvoiceModal from "./Invoice.vue";
import PosLayout1 from "./PosLayout1.vue";
import PosLayout2 from "./PosLayout2.vue";
import apiAdmin from "../../../../common/composable/apiAdmin";

export default {
    components: {
        PlusOutlined, SearchOutlined, EditOutlined, DeleteOutlined, SaveOutlined,
        SettingOutlined, ShoppingCartOutlined, UnorderedListOutlined,
        LeftOutlined, BankOutlined, LockOutlined, UserOutlined, PhoneOutlined,
        CloseOutlined, PlusCircleOutlined, BarcodeOutlined, TagOutlined,
        CreditCardOutlined, FileTextOutlined, RedoOutlined, CheckCircleOutlined,
        PosLayout1, PosLayout2,
        ProductCardNew, OrderSummary, PayNow, CashRegisterOpen, CashRegisterClose,
        CustomerAddButton, InvoiceModal,
    },
    setup() {
        const {
            taxes, customers, brands, categories, productLists,
            orderItemColumns, formData, customerUrl, getPreFetchData, posDefaultCustomer,
        } = fields();

        const selectedProducts = ref([]);
        const selectedProductIds = ref([]);
        const removedOrderItemsIds = ref([]);
        const postLayout = ref(1);

        const state = reactive({ orderSearchTerm: undefined, productFetching: false, products: [] });
        const { formatAmount, formatAmountCurrency, appSetting, taxTypes, permsArray, selectedWarehouse, allWarehouses } = common();

        const posWarehouses = ref([]);
        const selectedPosWarehouseXid = ref(null);
        const selectedPosWarehouseName = ref("");

        const stockPopupVisible = ref(false);
        const stockPopupProduct = ref(null);
        const stockPopupLoading = ref(false);
        const stockPopupData = ref([]);
        const selectedPopupWarehouseXid = ref(null);
        const selectedPopupWarehouseName = ref('');

        const quickAddPhone = ref('');
        const quickAddName = ref('');
        const selectedCustomerInfo = ref(null);
        const phoneSearchDone = ref(false);
        const addCustomerModalVisible = ref(false);
        const addCustomerLoading = ref(false);
        const modalCustomerName = ref('');
        const modalCustomerPhone = ref('');

        onMounted(() => {
            axiosAdmin.get("pos/warehouses").then((resp) => {
                posWarehouses.value = resp.data.warehouses || [];
                if (posWarehouses.value.length > 0 && !selectedPosWarehouseXid.value) {
                    const defaultW = posWarehouses.value.find(
                        (w) => w.xid === (selectedWarehouse.value && selectedWarehouse.value.xid)
                    ) || posWarehouses.value[0];
                    selectedPosWarehouseXid.value = defaultW.xid;
                    selectedPosWarehouseName.value = defaultW.name;
                }
            });
        });

        const onPosWarehouseChange = (xid) => {
            const w = posWarehouses.value.find((wh) => wh.xid === xid);
            selectedPosWarehouseName.value = w ? w.name : "";
        };

        const selectPopupWarehouse = (row) => {
            selectedPopupWarehouseXid.value = row.warehouse_xid;
            selectedPopupWarehouseName.value = row.warehouse_name;
        };

        const showStockPopup = (product) => {
            stockPopupProduct.value = product;
            stockPopupLoading.value = true;
            stockPopupVisible.value = true;
            stockPopupData.value = [];
            selectedPopupWarehouseXid.value = null;
            selectedPopupWarehouseName.value = '';

            axiosAdmin.post("pos/all-warehouse-stock", { product_xid: product.xid })
                .then((resp) => { stockPopupData.value = resp.data.stock || []; stockPopupLoading.value = false; })
                .catch(() => { stockPopupLoading.value = false; });
        };

        const addToCartFromPopup = () => {
            if (!selectedPopupWarehouseXid.value) { message.warning('Please select a warehouse before adding to cart.'); return; }
            const selectedRow = stockPopupData.value.find(r => r.warehouse_xid === selectedPopupWarehouseXid.value);
            stockPopupVisible.value = false;
            if (stockPopupProduct.value) {
                selectSaleProduct({
                    ...stockPopupProduct.value,
                    warehouse_xid: selectedPopupWarehouseXid.value,
                    warehouse_name: selectedPopupWarehouseName.value,
                    stock_quantity: selectedRow ? selectedRow.stock_quantity : stockPopupProduct.value.stock_quantity,
                });
            }
        };

        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const { t } = useI18n();

        const addEditVisible = ref(false);
        const addEditFormSubmitting = ref(false);
        const addEditFormData = ref({});
        const addEditRules = ref([]);
        const addEditPageTitle = ref("");

        const payNowVisible = ref(false);
        const printInvoiceModalVisible = ref(false);
        const printInvoiceOrder = ref({});

        const showMobileCart = ref(true);
        const innerWidth = ref(window.innerWidth);

        // ── Cash Register ──────────────────────────────────────────
        const cashRegister = ref(null);
        const cashRegisterLoading = ref(true);
        const cashRegisterOpenVis = ref(false);
        const cashRegisterCloseVis = ref(false);

        onMounted(() => {
            window.addEventListener('resize', () => { innerWidth.value = window.innerWidth; });
            axiosAdmin.get('cash-registers/current').then(res => {
                cashRegister.value = res.data.cash_register || null;
            }).catch(() => { cashRegister.value = null; }).finally(() => { cashRegisterLoading.value = false; });

            getPreFetchData();
        });

        const onRegisterOpened = (register) => {
            cashRegister.value = register;
            cashRegisterOpenVis.value = false;
        };

        const onRegisterClosed = () => {
            cashRegister.value = null;
            cashRegisterCloseVis.value = false;
        };

        const reFetchProducts = (searchTerm = "") => {
            axiosAdmin.post("pos/products", {
                brand_id: formData.value.brand_id,
                category_id: formData.value.category_id,
                search_term: searchTerm || "",
            }).then((productResponse) => { productLists.value = productResponse.data.products; });
        };

        const fetchProducts = debounce((value) => { fetchAllSearchedProduct(value); }, 300);

        const fetchAllSearchedProduct = (value) => {
            state.products = [];
            reFetchProducts(value || "");
            if (value != "") {
                state.productFetching = true;
                axiosAdmin.post(`search-product`, { order_type: "sales", search_term: value })
                    .then((response) => {
                        if (response.data.length == 1) {
                            searchValueSelected("", { product: response.data[0] });
                        } else {
                            state.products = response.data;
                        }
                        state.productFetching = false;
                    });
            }
        };

        const inputValueChanged = (keydownEvent) => {
            nextTick(() => { if (keydownEvent.keyCode == 13) { fetchAllSearchedProduct(keydownEvent.target.value); } });
        };

        const searchValueSelected = (value, option) => {
            const newProduct = option.product;
            if (!includes(selectedProductIds.value, newProduct.xid)) {
                showStockPopup(newProduct);
            } else {
                selectSaleProduct(newProduct);
            }
        };

        const getCartKey = (product) => product.warehouse_xid ? product.xid + '__' + product.warehouse_xid : product.xid;

        const selectSaleProduct = (newProduct) => {
            const cartKey = getCartKey(newProduct);
            if (!includes(selectedProductIds.value, cartKey)) {
                selectedProductIds.value.push(cartKey);
                selectedProducts.value.push({
                    ...newProduct, cart_key: cartKey, sn: selectedProducts.value.length + 1,
                    unit_price: formatAmount(newProduct.unit_price),
                    tax_amount: formatAmount(newProduct.tax_amount),
                    subtotal: formatAmount(newProduct.subtotal),
                });
                state.orderSearchTerm = undefined; state.products = [];
                recalculateFinalTotal();
                var audioObj = new Audio(appSetting.value.beep_audio_url);
                audioObj.play();
            } else {
                const newProductSelection = selectedProducts.value.find(
                    (p) => p.cart_key === cartKey || (!p.cart_key && p.xid === newProduct.xid)
                );
                if (newProductSelection && (newProductSelection.quantity < newProductSelection.stock_quantity || newProductSelection.product_type == "service")) {
                    const newResults = [];
                    var foundRecord = {};
                    selectedProducts.value.map((selectedProduct) => {
                        var newQuantity = selectedProduct.quantity;
                        const selCartKey = selectedProduct.cart_key || selectedProduct.xid;
                        if (selCartKey == cartKey) { newQuantity += 1; selectedProduct.quantity = newQuantity; foundRecord = selectedProduct; }
                        newResults.push(selectedProduct);
                    });
                    selectedProducts.value = newResults;
                    var audioObj = new Audio(appSetting.value.beep_audio_url);
                    audioObj.play();
                    state.orderSearchTerm = undefined; state.products = [];
                    quantityChanged(foundRecord);
                } else {
                    state.orderSearchTerm = undefined; state.products = [];
                    message.error(t("common.out_of_stock"));
                }
            }
        };

        const recalculateValues = (product) => {
            var quantityValue = parseFloat(product.quantity);
            var maxQuantity = parseFloat(product.stock_quantity);
            const unitPrice = parseFloat(product.unit_price);
            if (product.product_type != "service") { quantityValue = quantityValue > maxQuantity ? maxQuantity : quantityValue; }
            const discountRate = product.discount_rate;
            const totalDiscount = discountRate > 0 ? (discountRate / 100) * unitPrice : 0;
            const totalPriceAfterDiscount = unitPrice - totalDiscount;
            var taxAmount = 0; var subtotal = totalPriceAfterDiscount; var singleUnitPrice = unitPrice;
            if (product.tax_rate > 0) {
                if (product.tax_type == "inclusive") {
                    singleUnitPrice = (totalPriceAfterDiscount * 100) / (100 + product.tax_rate);
                    taxAmount = singleUnitPrice * (product.tax_rate / 100);
                } else {
                    taxAmount = totalPriceAfterDiscount * (product.tax_rate / 100);
                    subtotal = totalPriceAfterDiscount + taxAmount;
                    singleUnitPrice = totalPriceAfterDiscount;
                }
            }
            return { ...product, total_discount: totalDiscount * quantityValue, subtotal: subtotal * quantityValue, quantity: quantityValue, total_tax: taxAmount * quantityValue, max_quantity: maxQuantity, single_unit_price: singleUnitPrice };
        };

        const quantityChanged = (record) => {
            const newResults = [];
            selectedProducts.value.map((selectedProduct) => {
                if (selectedProduct.xid == record.xid) { newResults.push(recalculateValues(record)); }
                else { newResults.push(selectedProduct); }
            });
            selectedProducts.value = newResults;
            recalculateFinalTotal();
        };

        const recalculateFinalTotal = () => {
            let total = 0;
            selectedProducts.value.map((selectedProduct) => { total += selectedProduct.subtotal; });
            var discountAmount = 0;
            if (formData.value.discount_type == "percentage") {
                discountAmount = formData.value.discount_value != "" ? (parseFloat(formData.value.discount_value) * total) / 100 : 0;
            } else if (formData.value.discount_type == "fixed") {
                discountAmount = formData.value.discount_value != "" ? parseFloat(formData.value.discount_value) : 0;
            }
            const taxRate = formData.value.tax_rate != "" ? parseFloat(formData.value.tax_rate) : 0;
            total = total - discountAmount;
            const tax = total * (taxRate / 100);
            total = total + parseFloat(formData.value.shipping);
            formData.value.subtotal = formatAmount(total + tax);
            formData.value.tax_amount = formatAmount(tax);
            formData.value.discount = discountAmount;
        };

        const showDeleteConfirm = (product) => {
            const newResults = []; let counter = 1;
            selectedProducts.value.map((selectedProduct) => {
                if (selectedProduct.item_id != null) { removedOrderItemsIds.value = [...removedOrderItemsIds.value, selectedProduct.item_id]; }
                const selCartKey = selectedProduct.cart_key || selectedProduct.xid;
                const delCartKey = product.cart_key || product.xid;
                if (selCartKey != delCartKey) {
                    newResults.push({ ...selectedProduct, sn: counter, single_unit_price: formatAmount(selectedProduct.single_unit_price), tax_amount: formatAmount(selectedProduct.tax_amount), subtotal: formatAmount(selectedProduct.subtotal) });
                    counter++;
                }
            });
            selectedProducts.value = newResults;
            const deletedCartKey = product.cart_key || product.xid;
            selectedProductIds.value = selectedProductIds.value.filter((newId) => newId != deletedCartKey);
            recalculateFinalTotal();
        };

        const taxChanged = (value, option) => {
            formData.value.tax_rate = value == undefined ? 0 : option.tax.rate;
            recalculateFinalTotal();
        };

        const editItem = (product) => {
            addEditFormData.value = { id: product.xid, discount_rate: product.discount_rate, unit_price: product.unit_price, tax_id: product.x_tax_id, tax_type: product.tax_type == null ? undefined : product.tax_type };
            addEditVisible.value = true;
            addEditPageTitle.value = product.name;
        };

        const autoCreateAndSelectCustomer = () => {
            return new Promise((resolve, reject) => {
                const phone = quickAddPhone.value; const name = quickAddName.value || phone;
                addCustomerLoading.value = true;
                axiosAdmin.post('customers', { name, phone, user_type: 'customers', status: 'enabled' })
                    .then(() => axiosAdmin.get(customerUrl))
                    .then((resp) => {
                        customers.value = resp.data;
                        const added = customers.value.find(c => c.phone === phone || c.name === name);
                        if (added) { selectedCustomerInfo.value = { xid: added.xid, name: added.name, phone: added.phone || '' }; formData.value.user_id = added.xid; quickAddName.value = added.name; }
                        addCustomerLoading.value = false; message.success('Customer created!'); resolve();
                    }).catch((err) => { addCustomerLoading.value = false; message.error('Failed to create customer.'); reject(err); });
            });
        };

        const payNow = async () => {
            if (!cashRegister.value) { cashRegisterOpenVis.value = true; message.warning('Please open the cash register before making a sale.'); return; }
            if (!formData.value.user_id && quickAddPhone.value && quickAddPhone.value.length >= 3) {
                if (!quickAddName.value) { message.warning('Please enter a customer name to proceed.'); return; }
                try { await autoCreateAndSelectCustomer(); } catch (e) { return; }
            }
            payNowVisible.value = true;
        };

        const payNowClosed = () => { payNowVisible.value = false; };

        const resetPos = () => {
            selectedProducts.value = []; selectedProductIds.value = [];
            formData.value = { ...formData.value, tax_id: undefined, category_id: undefined, brand_id: undefined, tax_rate: 0, tax_amount: 0, discount_value: 0, discount: 0, shipping: 0, subtotal: 0 };
            selectedCustomerInfo.value = null; phoneSearchDone.value = false; quickAddPhone.value = ''; quickAddName.value = '';
            recalculateFinalTotal();
        };

        const onAddEditSubmit = () => {
            const record = selectedProducts.value.filter((selectedProduct) => selectedProduct.xid == addEditFormData.value.id);
            const selecteTax = taxes.value.filter((tax) => tax.xid == addEditFormData.value.tax_id);
            const taxType = addEditFormData.value.tax_type != undefined ? addEditFormData.value.tax_type : "exclusive";
            quantityChanged({ ...record[0], discount_rate: parseFloat(addEditFormData.value.discount_rate), unit_price: parseFloat(addEditFormData.value.unit_price), tax_id: addEditFormData.value.tax_id, tax_rate: selecteTax[0] ? selecteTax[0].rate : 0, tax_type: taxType });
            onAddEditClose();
        };

        const onAddEditClose = () => { addEditFormData.value = {}; addEditVisible.value = false; };

        const customerAdded = () => { axiosAdmin.get(customerUrl).then((response) => { customers.value = response.data; }); };

        const searchCustomerByPhone = debounce(() => {
            const phone = quickAddPhone.value ? quickAddPhone.value.trim() : '';
            if (phone.length < 3) { phoneSearchDone.value = false; selectedCustomerInfo.value = null; formData.value.user_id = posDefaultCustomer.value?.xid || undefined; quickAddName.value = ''; return; }
            const found = customers.value.find((c) => c.phone && c.phone.replace(/\s/g, '').includes(phone.replace(/\s/g, '')));
            phoneSearchDone.value = true;
            if (found) { selectedCustomerInfo.value = { xid: found.xid, name: found.name, phone: found.phone || '' }; formData.value.user_id = found.xid; quickAddName.value = found.name; }
            else { selectedCustomerInfo.value = null; formData.value.user_id = posDefaultCustomer.value?.xid || undefined; }
        }, 300);

        const openAddCustomerModal = () => { modalCustomerPhone.value = quickAddPhone.value; modalCustomerName.value = quickAddName.value; addCustomerModalVisible.value = true; };

        const handleQuickAddCustomer = () => {
            if (!modalCustomerName.value || !modalCustomerPhone.value) { message.warning('Please enter both name and phone number.'); return; }
            addCustomerLoading.value = true;
            axiosAdmin.post('customers', { name: modalCustomerName.value, phone: modalCustomerPhone.value, user_type: 'customers', status: 'enabled', warehouse_id: selectedPosWarehouseXid.value || undefined })
                .then(() => {
                    axiosAdmin.get(customerUrl).then((resp) => {
                        customers.value = resp.data;
                        const added = customers.value.find((c) => c.phone === modalCustomerPhone.value || c.name === modalCustomerName.value);
                        if (added) { selectedCustomerInfo.value = { xid: added.xid, name: added.name, phone: added.phone || '' }; formData.value.user_id = added.xid; quickAddPhone.value = added.phone || modalCustomerPhone.value; quickAddName.value = added.name; }
                    });
                    addCustomerLoading.value = false; addCustomerModalVisible.value = false; message.success('Customer added successfully!');
                }).catch(() => { addCustomerLoading.value = false; message.error('Failed to add customer. Please check the details.'); });
        };

        const clearSelectedCustomer = () => { selectedCustomerInfo.value = null; phoneSearchDone.value = false; quickAddPhone.value = ''; quickAddName.value = ''; formData.value.user_id = posDefaultCustomer.value?.xid || undefined; };

        const payNowSuccess = (invoiceOrder) => {
            resetPos();
            formData.value = { ...formData.value, user_id: posDefaultCustomer.value?.xid ? posDefaultCustomer.value.xid : undefined };
            reFetchProducts();
            payNowVisible.value = false;
            printInvoiceOrder.value = invoiceOrder;
            printInvoiceModalVisible.value = true;
        };

        const viewQuote = async () => {
            if (!formData.value.user_id && quickAddPhone.value && quickAddPhone.value.length >= 3) {
                if (!quickAddName.value) { message.warning('Please enter a customer name to proceed.'); return; }
                try { await autoCreateAndSelectCustomer(); } catch (e) { return; }
            }
            addEditRequestAdmin({
                url: "pos/save",
                data: { product_items: selectedProducts.value, details: formData.value, order_type: "quotations" },
                successMessage: t("stock.quote_saved"),
                success: (res) => { printInvoiceOrder.value = res.order; printInvoiceModalVisible.value = true; resetPos(); },
            });
        };

        return {
            taxes, customers, categories, brands, productLists, formData, reFetchProducts, selectSaleProduct,
            taxChanged, quantityChanged, recalculateFinalTotal, viewQuote, payNow, payNowVisible, payNowClosed, resetPos,
            appSetting, permsArray, ...toRefs(state), fetchProducts, searchValueSelected, selectedProducts,
            orderItemColumns, formatAmount, formatAmountCurrency,
            containerStyle: { height: window.innerHeight - 110 + "px", overflow: "scroll", "overflow-y": "scroll" },
            customerAdded,
            quickAddPhone, quickAddName, selectedCustomerInfo, phoneSearchDone, searchCustomerByPhone, clearSelectedCustomer,
            editItem, addEditVisible, addEditFormData, addEditFormSubmitting, addEditRules, addEditPageTitle, onAddEditSubmit, onAddEditClose,
            taxTypes, showDeleteConfirm, payNowSuccess,
            printInvoiceModalVisible, printInvoiceOrder,
            postLayout, innerWidth, inputValueChanged, showMobileCart,
            posWarehouses, selectedPosWarehouseXid, selectedPosWarehouseName, onPosWarehouseChange,
            stockPopupVisible, stockPopupProduct, stockPopupLoading, stockPopupData,
            selectedPopupWarehouseXid, selectedPopupWarehouseName, selectPopupWarehouse, showStockPopup, addToCartFromPopup,
            cashRegister, cashRegisterLoading, cashRegisterOpenVis, cashRegisterCloseVis, onRegisterOpened, onRegisterClosed,
        };
    },
};
</script>

<style lang="less">
/* ══ TOPBAR ══════════════════════════════════════════════════════════ */
.pos-topbar {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 60%, #1a4a6e 100%);
    padding: 0 20px; height: 52px; position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 12px rgba(0,0,0,.25);
}
.pos-topbar-left  { display: flex; align-items: center; gap: 12px; }
.pos-topbar-right { display: flex; align-items: center; gap: 10px; }

.pos-back-btn {
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    color: #fff; border-radius: 8px; width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s; font-size: 14px;
    &:hover { background: rgba(255,255,255,.2); }
}
.pos-topbar-title {
    font-size: 15px; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 7px;
}
.pos-branch-chip {
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    color: rgba(255,255,255,.85); border-radius: 20px;
    padding: 2px 12px; font-size: 12px; font-weight: 600;
    display: flex; align-items: center; gap: 5px;
}

.pos-reg-open {
    display: flex; align-items: center; gap: 6px;
    color: #86efac; font-size: 12.5px; font-weight: 600;
}
.pos-reg-dot  { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.pos-dot-green { background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.3); }
.pos-dot-red   { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.3); animation: pos-pulse 1.4s infinite; }
@keyframes pos-pulse { 0%,100%{opacity:1} 50%{opacity:.55} }

.pos-reg-close-btn {
    background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3);
    color: #fca5a5; border-radius: 8px; padding: 0 12px; height: 30px;
    font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;
    transition: all .15s;
    &:hover { background: rgba(239,68,68,.25); }
}
.pos-reg-open-btn {
    background: rgba(239,68,68,.2); border: 1px solid rgba(239,68,68,.35);
    color: #fca5a5; border-radius: 8px; padding: 0 14px; height: 30px;
    font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px;
    animation: pos-pulse 1.4s infinite;
    &:hover { background: rgba(239,68,68,.3); }
}

/* ══ LAYOUT ══════════════════════════════════════════════════════════ */
.pos-layout {
    display: flex; height: calc(100vh - 52px); overflow: hidden;
}

/* ── Cart Panel (LEFT) ── */
.pos-cart-panel {
    width: 44%; min-width: 380px; max-width: 560px;
    display: flex; flex-direction: column;
    background: #f8fafc; border-right: 1px solid #e2e8f0;
    overflow: hidden;
}

/* ── Product Grid Panel (RIGHT) ── */
.pos-products-panel {
    flex: 1; overflow: hidden;
    background: #fff;
    .ps { height: calc(100vh - 52px); padding: 16px; }
}

.pos-product-grid {
    display: flex; flex-wrap: wrap; gap: 12px; margin-top: 12px;
    > div { width: calc(33.33% - 8px); }
}
.pos-no-products { display: flex; align-items: center; justify-content: center; height: 200px; }

/* ══ SECTIONS ════════════════════════════════════════════════════════ */
.pos-section {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}
.pos-section-title {
    font-size: 11.5px; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: .6px;
    display: flex; align-items: center; gap: 6px;
    margin-bottom: 10px;
}
.pos-cart-count {
    background: #0d9488; color: #fff; border-radius: 20px;
    padding: 0 7px; font-size: 10.5px; min-width: 20px; text-align: center;
}

/* Customer Section */
.pos-cust-section { }
.pos-cust-inputs { display: flex; gap: 8px; margin-bottom: 8px; }
.pos-input { border-radius: 10px !important; flex: 1; }
.pos-input-icon { color: #94a3b8; }

.pos-cust-found {
    display: flex; align-items: center; gap: 10px;
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: 8px 12px;
}
.pos-cust-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: linear-gradient(135deg,#0d9488,#0f766e);
    color: #fff; font-size: 14px; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pos-cust-info { flex: 1; }
.pos-cust-name  { font-weight: 700; color: #0f172a; font-size: 13.5px; }
.pos-cust-phone { color: #64748b; font-size: 12px; }
.pos-cust-clear {
    background: none; border: none; color: #94a3b8; cursor: pointer;
    font-size: 14px; padding: 4px; border-radius: 5px;
    &:hover { color: #ef4444; background: #fee2e2; }
}
.pos-cust-new {
    background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;
    padding: 7px 12px; font-size: 12.5px; color: #1d4ed8;
    display: flex; align-items: center; gap: 6px;
}

/* Search Section */
.pos-search-section { }
.pos-search-select { width: 100% !important; }
.pos-search-icon   { color: #0d9488; font-size: 16px; }
.pos-product-opt   { display: flex; align-items: center; }

/* Cart Section */
.pos-cart-section {
    flex: 1; overflow-y: auto; background: #f8fafc !important;
    padding-bottom: 0 !important;
}
.pos-empty-cart {
    text-align: center; padding: 40px 20px;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.pos-empty-icon { font-size: 42px; color: #cbd5e1; }
.pos-empty-text { font-weight: 700; color: #94a3b8; font-size: 15px; }
.pos-empty-sub  { color: #cbd5e1; font-size: 12.5px; }

.pos-cart-items { padding: 4px 0; }
.pos-cart-item {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
    padding: 10px 16px; background: #fff; border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
    &:hover { background: #f0fdfa; }
}
.pos-ci-left { flex: 1; min-width: 0; }
.pos-ci-name { font-weight: 700; color: #0f172a; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pos-ci-meta { display: flex; gap: 8px; margin-top: 3px; flex-wrap: wrap; }
.pos-ci-stock { font-size: 11px; color: #64748b; background: #f1f5f9; border-radius: 4px; padding: 1px 6px; }
.pos-ci-wh    { font-size: 11px; color: #0f766e; background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 4px; padding: 1px 6px; display: flex; align-items: center; gap: 3px; }

.pos-ci-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.pos-qty-input   { width: 70px !important; border-radius: 8px !important; }
.pos-price-input { width: 90px !important; border-radius: 8px !important; }
.pos-ci-sub { font-weight: 800; color: #0d9488; font-size: 13px; min-width: 76px; text-align: right; font-variant-numeric: tabular-nums; }
.pos-ci-actions { display: flex; gap: 3px; }
.pos-ci-edit {
    background: #eff6ff; border: none; color: #3b82f6; border-radius: 7px;
    width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; transition: all .15s;
    &:hover { background: #dbeafe; }
}
.pos-ci-del {
    background: #fef2f2; border: none; color: #ef4444; border-radius: 7px;
    width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 12px; transition: all .15s;
    &:hover { background: #fee2e2; }
}

/* ══ CHECKOUT PANEL ══════════════════════════════════════════════════ */
.pos-checkout-panel {
    background: #fff; border-top: 2px solid #e2e8f0;
    flex-shrink: 0;
}

.pos-adj-row {
    display: flex; gap: 10px; padding: 12px 16px;
    background: #fafbfc; border-bottom: 1px solid #f1f5f9;
}
.pos-adj-field { display: flex; flex-direction: column; gap: 4px; flex: 1; }
.pos-adj-lbl { font-size: 10.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; }
.pos-adj-select { width: 100% !important; }
.pos-adj-input  { width: 100% !important; }
.pos-disc-wrap  { display: flex; gap: 0; }
.pos-disc-type  { width: 55px !important; border-radius: 8px 0 0 8px !important; }
.pos-disc-val   { flex: 1 !important; border-radius: 0 8px 8px 0 !important; }

.pos-summary-bar {
    display: flex; align-items: center; gap: 0;
    padding: 10px 16px; background: #f0fdfa;
    border-bottom: 1px solid #99f6e4;
}
.pos-summary-item { display: flex; flex-direction: column; gap: 1px; flex: 1; padding: 0 12px; border-right: 1px solid #99f6e4; }
.pos-sum-lbl  { font-size: 10px; font-weight: 700; color: #0f766e; text-transform: uppercase; letter-spacing: .4px; }
.pos-sum-val  { font-size: 13px; font-weight: 700; color: #0f172a; }
.pos-summary-total { flex: 2; display: flex; justify-content: space-between; align-items: center; padding: 0 12px 0 16px; }
.pos-total-lbl { font-size: 12px; font-weight: 800; color: #0f766e; text-transform: uppercase; }
.pos-total-val { font-size: 20px; font-weight: 900; color: #0d9488; font-variant-numeric: tabular-nums; }

.pos-action-btns {
    display: flex; gap: 8px; padding: 12px 16px;
}
.pos-btn-reset, .pos-btn-quote, .pos-btn-pay {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    border: none; border-radius: 10px; cursor: pointer; font-size: 13.5px; font-weight: 700;
    height: 42px; transition: all .2s;
    &:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }
}
.pos-btn-reset {
    background: #f1f5f9; color: #64748b; padding: 0 16px;
    &:hover:not(:disabled) { background: #fee2e2; color: #dc2626; }
}
.pos-btn-quote {
    background: #eff6ff; color: #2563eb; flex: 1;
    &:hover:not(:disabled) { background: #dbeafe; }
}
.pos-btn-pay {
    background: linear-gradient(135deg, #059669, #0d9488); color: #fff; flex: 2;
    box-shadow: 0 4px 14px rgba(13,148,136,.4); letter-spacing: .2px;
    &:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(13,148,136,.5); }
    &:active:not(:disabled) { transform: translateY(0); }
}
.pos-btn-sm { padding: 0 14px; height: 38px; font-size: 13px; }

/* ══ MOBILE ══════════════════════════════════════════════════════════ */
.pos-mobile { padding: 12px; padding-bottom: 70px; }
.pos-mob-search-row { display: flex; gap: 8px; margin-bottom: 12px; }
.pos-mob-search { flex: 1 !important; }
.pos-mob-toggle {
    background: #0d9488; border: none; color: #fff;
    border-radius: 10px; width: 44px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; cursor: pointer; flex-shrink: 0;
}
.pos-mob-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.pos-mob-prod-col { width: calc(50% - 5px); }
.pos-mob-cart { padding: 8px 0; }
.pos-adj-mobile { flex-wrap: wrap; }

.pos-mob-footer {
    position: fixed; left: 0; bottom: 0; width: 100%;
    background: #fff; border-top: 2px solid #e2e8f0;
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px; z-index: 50;
    box-shadow: 0 -4px 12px rgba(0,0,0,.08);
}
.pos-mob-total { display: flex; flex-direction: column; }
.pos-mob-total-lbl { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; }
.pos-mob-total-val { font-size: 18px; font-weight: 900; color: #0d9488; }
.pos-mob-btns { display: flex; gap: 6px; }

/* ══ MODALS ══════════════════════════════════════════════════════════ */
.pos-modal .ant-modal-content { border-radius: 16px !important; overflow: hidden; padding: 0 !important; }
.pos-modal .ant-modal-header { display: none !important; }

.pos-modal-hd {
    display: flex; align-items: center; gap: 12px;
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    padding: 18px 22px;
}
.pos-modal-hd-stock { background: linear-gradient(135deg, #064e3b, #0f766e); }

.pos-modal-hd-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,.15); color: #fff; font-size: 18px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,.2);
}
.pos-modal-hd-icon-green { background: rgba(255,255,255,.15); }
.pos-modal-hd-title { font-size: 15px; font-weight: 800; color: #fff; }
.pos-modal-hd-sub   { font-size: 12px; color: rgba(255,255,255,.7); margin-top: 2px; }

.pos-modal-body  { padding: 20px 22px; }
.pos-modal-footer {
    display: flex; gap: 8px; justify-content: flex-end;
    padding: 12px 22px 18px; border-top: 1px solid #f1f5f9;
}
.pos-mf-cancel {
    background: #f1f5f9; color: #64748b; border: none;
    border-radius: 9px; padding: 0 18px; height: 36px; cursor: pointer;
    font-size: 13px; font-weight: 600; transition: all .15s;
    &:hover { background: #e2e8f0; }
}
.pos-mf-save {
    display: flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, #0f766e, #0d9488); color: #fff;
    border: none; border-radius: 9px; padding: 0 22px; height: 36px;
    font-size: 13px; font-weight: 700; cursor: pointer; transition: all .2s;
    box-shadow: 0 4px 12px rgba(13,148,136,.3);
    &:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }
    &:disabled { opacity: .45; cursor: not-allowed; }
}

/* Stock / Warehouse cards */
.pos-stock-hint { font-size: 13px; color: #64748b; margin-bottom: 14px; }
.pos-wh-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
.pos-wh-card {
    display: flex; align-items: center; gap: 12px;
    border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;
    cursor: pointer; transition: all .18s;
    &:hover { border-color: #0d9488; background: #f0fdfa; }
}
.pos-wh-selected { border-color: #0d9488 !important; background: #f0fdfa !important; }
.pos-wh-disabled { opacity: .5; cursor: not-allowed !important; &:hover { border-color: #e2e8f0 !important; background: #fff !important; } }
.pos-wh-radio { flex-shrink: 0; }
.pos-wh-info  { flex: 1; }
.pos-wh-name  { font-weight: 700; color: #0f172a; font-size: 13.5px; }
.pos-wh-qty   { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.pos-qty-badge { font-size: 16px; font-weight: 800; font-variant-numeric: tabular-nums; }
.pos-qty-ok   { color: #16a34a; }
.pos-qty-zero { color: #ef4444; }
.pos-qty-unit { font-size: 10px; color: #94a3b8; font-weight: 600; }
.pos-wh-empty { text-align: center; color: #94a3b8; padding: 20px; font-size: 13px; }
.pos-wh-selected-info {
    background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
    padding: 8px 14px; font-size: 13px; color: #15803d;
    display: flex; align-items: center;
}

/* Sidebar scroll overrides */
.right-pos-sidebar .ps { height: calc(100vh - 90px); }
.pos-products-panel .ps { height: calc(100vh - 52px) !important; }
</style>
