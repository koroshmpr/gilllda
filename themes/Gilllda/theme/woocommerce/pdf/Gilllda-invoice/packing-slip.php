<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php do_action( 'wpo_wcpdf_before_document', $this->type, $this->order ); ?>

    <table class="head container">
        <tr>
            <td class="header">
                <?php
                if ( $this->has_header_logo() ) {
                    $this->header_logo();
                } else {
                    echo $this->get_title();
                }
                ?>
            </td>
            <td class="shop-info">
                <div class="shop-name"><h3>فروشگاه گیلدا</h3></div>
                <div class="shop-address"><?php $this->shop_address(); ?></div>
            </td>
        </tr>
    </table>

    <h1 class="document-type-label">لیست بسته‌بندی سفارش</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->type, $this->order ); ?>

    <table class="order-data-addresses">
        <tr>
            <td class="address shipping-address">
                <h3>آدرس گیرنده:</h3>
                <?php do_action( 'wpo_wcpdf_before_shipping_address', $this->type, $this->order ); ?>
                <?php $this->shipping_address(); ?>
                <?php do_action( 'wpo_wcpdf_after_shipping_address', $this->type, $this->order ); ?>
            </td>
            <td class="address billing-address">
                <?php if ( isset($this->settings['display_billing_address']) && $this->ships_to_different_address()) { ?>
                    <h3>آدرس خریدار:</h3>
                    <?php do_action( 'wpo_wcpdf_before_billing_address', $this->type, $this->order ); ?>
                    <?php $this->billing_address(); ?>
                    <?php do_action( 'wpo_wcpdf_after_billing_address', $this->type, $this->order ); ?>
                <?php } ?>
            </td>
            <td class="order-data">
                <table>
                    <?php do_action( 'wpo_wcpdf_before_order_data', $this->type, $this->order ); ?>
                    <tr class="order-number">
                        <th>شماره سفارش:</th>
                        <td><?php $this->order_number(); ?></td>
                    </tr>
                    <tr class="order-date">
                        <th>تاریخ سفارش:</th>
                        <td><?php $this->order_date(); ?></td>
                    </tr>
                    <tr class="shipping-method">
                        <th>روش ارسال:</th>
                        <td><?php $this->shipping_method(); ?></td>
                    </tr>
                    <?php do_action( 'wpo_wcpdf_after_order_data', $this->type, $this->order ); ?>
                </table>
            </td>
        </tr>
    </table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->type, $this->order ); ?>

    <table class="order-details">
        <thead>
        <tr>
            <th class="product">شرح محصول</th>
            <th class="quantity">تعداد</th>
        </tr>
        </thead>
        <tbody>
        <?php $items = $this->get_order_items(); if( sizeof( $items ) > 0 ) : foreach( $items as $item_id => $item ) : ?>
            <tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, $this->type, $this->order, $item_id ); ?>">
                <td class="product">
                    <span class="item-name"><?php echo $item['name']; ?></span>
                    <?php do_action( 'wpo_wcpdf_before_item_meta', $this->type, $item, $this->order  ); ?>
                    <span class="item-meta"><?php echo $item['meta']; ?></span>
                    <?php do_action( 'wpo_wcpdf_after_item_meta', $this->type, $item, $this->order  ); ?>
                </td>
                <td class="quantity"><?php echo $item['quantity']; ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->type, $this->order ); ?>

<?php if ( $this->get_footer() ): ?>
    <div id="footer">
        <?php $this->footer(); ?>
    </div>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $this->type, $this->order ); ?>