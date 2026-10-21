<div align="center">

  <h1>Mobipaid for WooCommerce</h1>
  <p>
    Official WooCommerce payment gateway plugin for Mobipaid!
  </p>

  <h4>
    <a href="https://mobipaid.com">View Demo</a>
    <span> · </span>
    <a href="https://github.com/MobipaidLLC/mobipaid-woocommerce#readme">Documentation</a>
    <span> · </span>
    <a href="https://github.com/MobipaidLLC/mobipaid-woocommerce/issues/">Report Bug</a>
    <span> · </span>
    <a href="https://github.com/MobipaidLLC/mobipaid-woocommerce/issues/">Request Feature</a>
  </h4>
</div>

<br />

<!-- About the Project -->

## 📝 About the Project

The **Mobipaid for WooCommerce** plugin enables merchants to accept card-not-present payments seamlessly and securely through their WooCommerce online stores. Built for modern WordPress architectures, it connects WooCommerce directly with Mobipaid's payment platform to support credit/debit cards, alternative payment methods (Apple Pay, Google Pay, SEPA, Nedbank EFT, and more), hosted checkouts, and recurring billing.

<!-- Features -->

### 🌟 Features

- **Hosted Payment Checkout**: Securely redirects customers to Mobipaid's hosted checkout page with dynamic POS Link generation.
- **WooCommerce Blocks Integration**: Full support for modern block-based Cart and Checkout flows.
- **High-Performance Order Storage (HPOS)**: Officially declared compatible with custom order tables.
- **WooCommerce Subscriptions**: Full recurring billing support, renewal order generation, and backend/frontend subscription cancellation hooks.
- **Refund Management**: Initiate partial and full refunds directly from the WooCommerce admin order dashboard via the Mobipaid API.
- **Robust Webhook Handling**: Timing-attack safe callback verification, idempotent payment status processing, and anti-fraud checks.
- **Internationalization (i18n)**: Ready out of the box with translations across 18+ locales.
- **Sandbox & Live Environments**: Easily switch between test and live modes driven by Mobipaid API access keys.
- **Auditable Logging**: Integrated with WooCommerce Logger with token masking for sensitive credentials.

<!-- Getting Started -->

## 🚀 Getting Started

<!-- Prerequisites -->

### 🔧 Prerequisites

- **WordPress** (v6.2+ required, tested up to v7.0)
- **WooCommerce** (v7.1.0+ required, tested up to v10.3.5)
- **PHP** (v8.0+ required)
- **SSL Certificate** (HTTPS required for production mode)
- An active **Mobipaid Merchant Account** and **API Access Key** from the [Mobipaid Portal](https://merchant.mobipaid.com)

<!-- Run Locally -->

### 🏃 Installation

#### Automatic Installation (WordPress Admin)

1. Log in to your WordPress dashboard.
2. Navigate to **Plugins > Add New Plugin**.
3. In the search box, type `Mobipaid`.
4. Click **Install Now** on the Mobipaid plugin, then click **Activate**.

#### Manual Installation

Clone the project repository:

```bash
  git clone https://github.com/MobipaidLLC/mobipaid-woocommerce.git
```

Move the plugin directory into your WordPress plugins directory:

```bash
  mv mobipaid-woocommerce/mobipaid /path-to-wordpress/wp-content/plugins/mobipaid
```

Activate the plugin through the WordPress admin panel under **Plugins > Installed Plugins**.

<!-- Available Scripts -->

### 📜 Available Settings

| Setting          | Description                                                                                 |
| ---------------- | ------------------------------------------------------------------------------------------- |
| `Enable/Disable` | Enable or disable the Mobipaid payment gateway on the checkout page.                        |
| `Title`          | Payment method title presented to customers during checkout.                                |
| `Description`    | Payment method description displayed to customers during checkout.                          |
| `Access Key`     | API access key issued by the Mobipaid Merchant Portal.                                      |
| `Sandbox Mode`   | Switch between Mobipaid Sandbox (`test.mobipaid.io`) and Production environments.           |
| `Enable Logging` | Write detailed gateway and API transactions to `wp-content/uploads/wc-logs/mobipaid-*.log`. |

<!-- Contributing -->

## 👋 Contributing

Contributions are welcome! Please read our [Contributing Guidelines](.github/CONTRIBUTING.md) for details on our code of conduct, the Git branching and release workflow (`develop-<version>` ➔ `v<version>` ➔ `master`), WordPress.org SVN synchronization, and how to submit pull requests.

<!-- License -->

## ⚠️ License

This project is licensed under the [GNU General Public License v3.0](./LICENSE).

<!-- Contact -->

## 🤝 Contact

Mobipaid Team - [info@mobipaid.com](mailto:info@mobipaid.com)

Project Link: [https://github.com/MobipaidLLC/mobipaid-woocommerce](https://github.com/MobipaidLLC/mobipaid-woocommerce)

<!-- Acknowledgements -->

## 🎓 Acknowledgements

We extend our gratitude to the creators and maintainers of the outstanding tools and libraries that power this project:

- [WordPress](https://wordpress.org/) - For the core CMS foundation and plugin architecture.
- [WooCommerce](https://woocommerce.com/) - For the premier open-source eCommerce platform.
- [WooCommerce Blocks](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce-blocks) - For modern Gutenberg checkout integration.
- [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) - For recurring payment and subscription support.
- [Mobipaid Engineering](https://mobipaid.com) - For payment infrastructure and API v2 services.
