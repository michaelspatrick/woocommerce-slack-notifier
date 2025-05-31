# WooCommerce Slack Notifier

A WordPress plugin that sends Slack notifications for important WooCommerce and WordPress events, including:

- 🛒 New orders (with item, quantity, total, and shipping)
- ⚠️ Low stock / ❌ Out of stock / 🔁 Backorders
- 📝 New blog posts
- ⭐ New product reviews
- 👤 New customer registrations
- 🔍 Products missing weight or dimension data

## 🚀 Installation

1. Download the latest `.zip` release of the plugin.
2. In your WordPress dashboard, go to **Plugins → Add New → Upload Plugin**.
3. Select the ZIP file and click **Install Now**, then **Activate**.

## 🔧 Configuration

1. Navigate to **Settings → Slack Notifier** in your WordPress admin.
2. Enter the following:
   - **Slack Bot Token** – your `xoxb-` bot token from your Slack app.
   - **Slack Channel** – the channel name (e.g., `#orders`) or Slack channel ID (e.g., `C01ABC1234`).
3. Check the boxes for the events you want to be notified about.
4. Click **Save Changes**.

> ✅ Use the “Send Test Slack Message” button to verify connectivity.

## 🧠 Supported Notifications

| Event Type          | Toggle Setting             |
|---------------------|----------------------------|
| New Order           | `New Orders`               |
| Low Stock           | `Low Stock`                |
| Out of Stock        | `No Stock`                 |
| Missing Product Info| `Missing Product Info`     |
| New Blog Post       | `New Blog Posts`           |
| New Review          | `New Reviews`              |
| New Customer        | `New Customers`            |
| Backorders          | `Backorders`               |

## 🧪 Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.2+ (recommended 7.4 or newer)

## 💬 Slack Bot Setup

1. Create a Slack app at https://api.slack.com/apps
2. Enable the **chat:write** scope for the Bot Token.
3. Install the app to your workspace.
4. Copy the `xoxb-` token into the plugin settings.
5. Invite the bot to your desired channel:  
   `/invite @your-bot-name`

## 🛠️ Development

To contribute or customize:

```bash
git clone https://github.com/michaelspatrick/woocommerce-slack-notifier.git
