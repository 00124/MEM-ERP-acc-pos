<template>
    <div class="product-pos" v-if="product && product.xid">
        <div class="product-pos-top">
            <a href="javascript:void(0)">
                <span
                    v-if="product.product_type == 'service'"
                    class="quantity-box"
                    to="#"
                >
                    {{ $t("product.service") }}
                </span>
                <span v-else class="quantity-box" to="#">
                    {{ product.stock_quantity }} {{ product.unit.short_name }}
                </span>

                <img :src="product.image_url" class="img-fit" />
            </a>
        </div>
        <div class="product-pos-bottom">
            <div>
                <h5 class="product-title">
                    {{ product.name }}
                </h5>
            </div>
            <div class="product-details">
                <div class="product-details-row">
                    <span class="product-subtotal">{{
                        formatAmountCurrency(product.subtotal)
                    }}</span>
                    <span v-if="isAdded" class="added-tag">{{
                        $t("product.added")
                    }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import common from "../../composable/common";

export default {
    props: ["product", "isAdded"],
    setup(props) {
        const { formatAmountCurrency } = common();

        return {
            formatAmountCurrency,
        };
    },
};
</script>

<style lang="less">
.product-pos {
    background: #fff;
    border: 2px solid #e8e8e8;
    border-radius: 7px;
    margin-top: 8px;
    position: relative;
    cursor: pointer;
    transition: border-color 0.15s;

    &:hover {
        border-color: #4096ff;
    }
}

.product-pos-top {
    display: flex;
    justify-content: center;
    width: 100%;

    a {
        text-align: center;
        width: 100%;
        display: block;
    }

    .quantity-box {
        position: absolute;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 18px;
        top: 6px;
        left: 6px;
        background-color: rgba(255,255,255,0.92);
        padding: 0 5px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 500;
        color: #333;
        z-index: 2;
        border: 1px solid #d9d9d9;
    }

    img {
        height: 100px;
        max-width: 100%;
        width: 100%;
        -o-object-fit: cover;
        object-fit: cover;
        padding: 8px;
        border-radius: 7px;
    }
}

.product-pos-bottom {
    padding: 4px 8px 8px;

    .product-title {
        font-weight: 500;
        font-size: 12px;
        line-height: 1.3;
        height: 32px;
        overflow: hidden;
        margin: 0 0 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        color: #333;
    }

    .product-details {
        display: flex;
        justify-content: space-between;
        align-items: center;

        .product-details-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .product-subtotal {
            font-weight: 700;
            font-size: 13px;
            color: #1d3557;
        }
    }
}

.added-tag {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: #52c41a;
    color: #fff;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 11px;
    z-index: 2;
}
</style>
