<?php
/*
Plugin Name: WooCommerce Slack Notifier
Description: Sends order and inventory notifications to Slack.
Version: 1.1
Author: Michael Patrick
*/

if (!defined('ABSPATH')) exit;

add_action('woocommerce_new_order', 'wsn_notify_new_order', 10, 1);
add_action('woocommerce_low_stock', 'wsn_notify_low_stock');
add_action('woocommerce_no_stock', 'wsn_notify_no_stock');
add_action('woocommerce_product_set_stock', 'wsn_check_product_details_on_stock_change', 10, 1);
add_action('publish_post', 'wsn_notify_new_post', 10, 2);
add_action('user_register', 'wsn_notify_new_customer');
add_action('comment_post', 'wsn_notify_new_review', 10, 2);
add_action('woocommerce_product_set_stock_status', 'wsn_notify_backorder', 10, 2);

add_action('admin_menu', 'wsn_admin_menu');
add_action('admin_init', 'wsn_register_settings');

function wsn_admin_menu() {
    add_options_page(
        'Woo Slack Notifier',
        'Slack Notifier',
        'manage_options',
        'wsn-settings',
        'wsn_settings_page'
    );
}

function wsn_register_settings() {
    register_setting('wsn_settings_group', 'wsn_settings');
}

function wsn_settings_page() {
    $options = get_option('wsn_settings');
    ?>
    <div class="wrap">
        <h1>WooCommerce Slack Notifier Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wsn_settings_group'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Slack Bot Token</th>
                    <td><input type="text" name="wsn_settings[token]" value="<?php echo esc_attr($options['token'] ?? ''); ?>" size="50" /></td>
                </tr>
                <tr>
                    <th scope="row">Slack Channel (e.g., #orders)</th>
                    <td><input type="text" name="wsn_settings[channel]" value="<?php echo esc_attr($options['channel'] ?? ''); ?>" size="30" /></td>
                </tr>
                <tr>
                    <th scope="row">Enable Notifications</th>
                    <td>
                        <label><input type="checkbox" name="wsn_settings[enable_new_order]" value="1" <?php checked($options['enable_new_order'] ?? '', 1); ?> /> New Orders</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_low_stock]" value="1" <?php checked($options['enable_low_stock'] ?? '', 1); ?> /> Low Stock</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_no_stock]" value="1" <?php checked($options['enable_no_stock'] ?? '', 1); ?> /> No Stock</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_missing_details]" value="1" <?php checked($options['enable_missing_details'] ?? '', 1); ?> /> Missing Product Info</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_new_post]" value="1" <?php checked($options['enable_new_post'] ?? '', 1); ?> /> New Blog Posts</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_new_customer]" value="1" <?php checked($options['enable_new_customer'] ?? '', 1); ?> /> New Customers</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_new_review]" value="1" <?php checked($options['enable_new_review'] ?? '', 1); ?> /> New Reviews</label><br>
                        <label><input type="checkbox" name="wsn_settings[enable_backorder]" value="1" <?php checked($options['enable_backorder'] ?? '', 1); ?> /> Backorders</label><br>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <hr>
        <form method="post">
            <?php submit_button('Send Test Slack Message', 'secondary', 'wsn_send_test'); ?>
        </form>
    </div>
    <?php
    if (isset($_POST['wsn_send_test'])) {
        $response = wsn_send_to_slack(":white_check_mark: *Test message sent from WooCommerce Slack Notifier!*");
        if ($response === true) {
            echo '<div class="notice notice-success"><p>Test message sent!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($response) . '</p></div>';
        }
    }
}

function wsn_notify_new_order($order_id) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_new_order'] ?? false)) return;

    $order = wc_get_order($order_id);
    $items = $order->get_items();
    $lines = [];

    foreach ($items as $item) {
        $product = $item->get_product();
        $lines[] = "- *{$item->get_name()}*: {$item->get_quantity()} × " . wc_price($item->get_total());
    }

    $shipping = $order->get_shipping_total() > 0 ? "Shipping: " . wc_price($order->get_shipping_total()) : "Free Shipping";
    $message = "*New Order #$order_id*
"
             . implode("\n", $lines) . "\n"
             . "$shipping\n"
             . "Total: *" . wc_price($order->get_total()) . "*";

    wsn_send_to_slack($message);
}

function wsn_notify_low_stock($product) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_low_stock'] ?? false)) return;

    $message = ":warning: *Low stock* alert for `{$product->get_name()}` (ID: {$product->get_id()}). Remaining: {$product->get_stock_quantity()}";
    wsn_send_to_slack($message);
}

function wsn_notify_no_stock($product) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_no_stock'] ?? false)) return;

    $message = ":x: *Out of stock* - `{$product->get_name()}` (ID: {$product->get_id()})";
    wsn_send_to_slack($message);
}

function wsn_check_product_details_on_stock_change($product) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_missing_details'] ?? false)) return;

    $missing = [];

    if (!$product->has_weight()) $missing[] = 'weight';
    if (!$product->has_dimensions()) $missing[] = 'dimensions';

    if (!empty($missing)) {
        $msg = ":mag: *Product missing details* - `{$product->get_name()}` (ID: {$product->get_id()}) is missing: " . implode(', ', $missing);
        wsn_send_to_slack($msg);
    }
}

function wsn_notify_new_post($ID, $post) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_new_post'] ?? false)) return;

    $title = get_the_title($ID);
    $link = get_permalink($ID);
    $message = ":memo: *New Post Published*: <$link|$title>";
    wsn_send_to_slack($message);
}

function wsn_notify_new_customer($user_id) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_new_customer'] ?? false)) return;

    $user = get_userdata($user_id);
    if (!in_array('customer', $user->roles)) return;

    $message = ":bust_in_silhouette: *New Customer Registered*: `{$user->user_login}` ({$user->user_email})";
    wsn_send_to_slack($message);
}

function wsn_notify_new_review($comment_ID, $approved) {
    if (1 !== $approved) return;

    $comment = get_comment($comment_ID);
    if ('product' !== get_post_type($comment->comment_post_ID)) return;

    $opt = get_option('wsn_settings');
    if (!($opt['enable_new_review'] ?? false)) return;

    $product = get_the_title($comment->comment_post_ID);
    $link = get_permalink($comment->comment_post_ID);
    $message = ":star: *New Review on* <$link|$product>: \"" . $comment->comment_content . "\" by `{$comment->comment_author}`";
    wsn_send_to_slack($message);
}

function wsn_notify_backorder($product_id, $stock_status) {
    $opt = get_option('wsn_settings');
    if (!($opt['enable_backorder'] ?? false)) return;

    if ($stock_status === 'onbackorder') {
        $product = wc_get_product($product_id);
        $message = ":repeat: *Backorder Alert* - `{$product->get_name()}` (ID: $product_id)";
        wsn_send_to_slack($message);
    }
}

function wsn_send_to_slack($message) {
    $options = get_option('wsn_settings');
    $token = $options['token'] ?? '';
    $channel = $options['channel'] ?? '';

    if (empty($token) || empty($channel)) return 'Missing Slack token or channel.';

    $payload = [
        'channel' => $channel,
        'text' => $message,
        'mrkdwn' => true,
    ];

    $response = wp_remote_post('https://slack.com/api/chat.postMessage', [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ],
        'body' => json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['ok'] ? true : $body['error'];
}
