<template>
    <div class="product-pos" v-if="product && product.xid">
        <div class="product-pos-img-wrap">
            <span class="quantity-box">
                <template v-if="product.product_type == 'service'">
                    {{ $t("product.service") }}
                </template>
                <template v-else>
                    {{ product.stock_quantity }} {{ product.unit.short_name }}
                </template>
            </span>
            <img :src="product.image_url" class="product-pos-img" />
            <span v-if="isAdded" class="added-tag">{{ $t("product.added") }}</span>
        </div>
        <div class="product-pos-bottom">
            <h5 class="product-title">{{ product.name }}</h5>
            <span class="product-subtotal">{{ formatAmountCurrency(product.subtotal) }}</span>
        </div>
    </div>
</template>

<script>
import common from "../../composable/common";

export default {
    props: ["product", "isAdded"],
    setup() {
        const { formatAmountCurrency } = common();
        return { formatAmountCurrency };
    },
};
</script>

<style lang="less">
.product-pos {
    background: #fff;
    border: 2px solid #e8e8e8;
    border-radius: 8px;
    margin-top: 8px;
    position: relative;
    cursor: pointer;
    transition: border-color 0.18s;
    overflow: hidden;

    &:hover {
        border-color: #4096ff;
    }
}

.product-pos-img-wrap {
    position: relative;
    width: 100%;
    padding-top: 75%;
    overflow: hidden;
    background: #f5f5f5;
    border-radius: 6px 6px 0 0;

    .product-pos-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .quantity-box {
        position: absolute;
        top: 5px;
        left: 5px;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #d9d9d9;
        border-radius: 3px;
        padding: 1px 5px;
        font-size: 10px;
        font-weight: 600;
        color: #333;
        z-index: 3;
        line-height: 1.6;
        max-width: 70%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .added-tag {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: #52c41a;
        color: #fff;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        z-index: 3;
        line-height: 1.6;
    }
}

.product-pos-bottom {
    padding: 5px 7px 7px;

    .product-title {
        font-weight: 500;
        font-size: 11px;
        line-height: 1.3;
        height: 30px;
        overflow: hidden;
        margin: 0 0 3px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        color: #222;
    }

    .product-subtotal {
        font-weight: 700;
        font-size: 12px;
        color: #1d3557;
        display: block;
    }
}
</style>
