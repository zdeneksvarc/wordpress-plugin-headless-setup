# Headless Setup for WordPress

This WordPress plugin enforces headless mode and protects key API endpoints. It provides an admin UI to toggle the following features:

### ✅ Features

- **Headless mode** – blocks all frontend templates.
- **Disable XML-RPC** – completely disables access to `/xmlrpc.php`.
- **Protect REST API** – requires authentication for `/wp-json` requests.
- **Protect GraphQL** – requires authentication for `/graphql` requests.

All features are **enabled by default** and can be configured in:

> **Settings > Headless Setup**

---

### 🔧 Requirements

- WordPress 6.0+
- PHP 7.4+

---

### 📁 Installation

1. Clone or download this repository into your WordPress `/wp-content/plugins` directory.
2. Activate the plugin via **Plugins > Headless Setup**.
3. You will be automatically redirected to the settings page.

---

### 🔒 Protected Endpoints

Once activated, the following endpoints will return HTTP 401 or 403 unless the user is authenticated:

- `/` (homepage)
- `/wp-json`
- `/graphql`
- `/xmlrpc.php`

WordPress admin will remain accessible at:

- `/wp-admin`