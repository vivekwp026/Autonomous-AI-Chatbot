# Autonomous AI Chatbot

Autonomous AI-driven conversational assistant and customer support chatbot for WordPress, powered by OpenAI models (GPT-4o, GPT-4, GPT-3.5) and vector embeddings (Pinecone, ChromaDB, and local ONNX models). Train the assistant on your site's posts and pages, assist visitors in real-time with voice and text, generate SEO content in admin, and track performance with live analytics.

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
![WordPress 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-21759b.svg)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)

The plugin connects with **OpenAI-compatible LLM endpoints** and supports **Retrieval-Augmented Generation (RAG)** using vector databases or in-browser ONNX embeddings.

---

## Contents

- [Features](#features)
- [Pro Add-on](#pro-add-on)
- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
- [Development](#development)
- [Extending the Plugin](#extending-the-plugin)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

---

## Features

| Area | What you get |
|---|---|
| **Conversational Assistant** | Real-time frontend chat widget supporting text and voice input (Speech-to-Text) with voice playback (Text-to-Speech). Render rich HTML, markdown formatting, and interactive quick-reply prompts. |
| **Website Content Training (RAG)** | Batch-sync posts, pages, and website content into vector databases (Pinecone, ChromaDB) or generate embeddings locally using the ONNX `e5-small` model. Accurately answers visitor questions based on your site content with persistent background synchronization. |
| **Smart Query Routing** | Autonomous routing agent automatically directs visitor questions to vector search (RAG) or direct database lookup. |
| **Content Creation** | In-admin AI writing assistant to generate SEO blog posts, pages, and voice script content in a single click with configurable model temperature, max tokens, and prompt templates. |
| **Conversation History** | Complete session logs with date filters, user message details, conversation transcripts, and user blocking/unblocking for abusive sessions. Export logs to Excel/CSV with one click. |
| **Analytics Dashboard** | Comprehensive admin dashboard featuring 10 KPI metric cards, dynamic AI latency tracking (rolling round-trip response benchmark), AI query resolution rates, and traffic visualization charts (Bar, Area, Line, Scatter). |
| **Chatbot Customization** | Dynamic color themes, background images, custom bot avatars, floating position placement, sound alerts, and customizable greeting messages. |
| **Vector DB Management** | Automatic data cleanup and training reset when switching between vector engines (Pinecone, ChromaDB, ONNX). |

---

### Pro Add-on

The plugin is fully functional on its own as an open-source solution for WordPress. A commercial add-on, **Autonomous AI Chatbot Pro**, adds WooCommerce e-commerce capabilities:

- **WooCommerce Product Catalog Training**: Train and query Single, Variable, Grouped, and External/Affiliate products (tested to scale with 10k+ variable and 100k+ simple products).
- **WooCommerce HPOS & Block Checkout Compatibility**: Declared compatibility with High-Performance Order Storage (HPOS) and block checkout.
- **Autonomous Commerce Agents**: Natural language Add to Cart, Remove from Cart, Coupon Application, and Live Shipping Rate Discovery directly within the chat flow.
- **AI-Guided Checkout**: End-to-end conversational checkout with default WooCommerce payment gateways and Stripe MCP payment verification.
- **Product Content Generation**: AI drafting of complete WooCommerce products with short and long descriptions directly into your store catalog.
- **Pro Commerce Analytics**: Bot-driven revenue tracking, bot cart additions, top converted products, and automated coupon usage tracking.
- **Multi-Source Knowledge Ingestion**: Advanced external documentation syncing and vector retries.

Pro features appear in this plugin as clean preview placeholders or upgrade prompts. Pro is built on public WordPress hooks, and this repository contains no proprietary Pro code. Learn more or get the Pro addon from the [Webkul Store](https://store.webkul.com/woocommerce-plugins/WooCommerce.html?p=1).

---

## Requirements

| Component | Minimum | Tested up to |
|---|---|---|
| **WordPress** | 6.5 | 7.0 |
| **PHP** | 7.4 | 8.4 |
| **OpenAI API** | API key with GPT-3.5-Turbo, GPT-4, or GPT-4o access | Any OpenAI-compatible endpoint |

---

## Installation

### From a release zip

1. Download `autonomous-ai-chatbot.zip` from the releases page.
2. In your WordPress admin, navigate to **Plugins → Add New → Upload Plugin**.
3. Choose the zip file, upload it, and click **Activate**.

### From source

The compiled JavaScript and CSS assets in `assets/` are included in the repository, so a clone works out of the box:

```bash
cd wp-content/plugins
git clone <repository-url> autonomous-ai-chatbot
wp plugin activate autonomous-ai-chatbot
```

After activation, navigate to **Autonomous AI Chatbot** in the WordPress admin menu.

---

## Getting Started

1. **Connect OpenAI API**:
   - Go to **Autonomous AI Chatbot → Settings**.
   - Enter your OpenAI API key and choose your desired model (e.g., `gpt-4o-mini`, `gpt-4o`, `gpt-3.5-turbo`).
   - Configure temperature and token limits if desired.

2. **Train Your Site Content**:
   - Navigate to **Autonomous AI Chatbot → Data Training**.
   - Choose your embedding engine (e.g., Local ONNX Model, Google Gemini, Pinecone, or ChromaDB).
   - Click **Synchronize All Posts and Pages** to index your site content.

3. **Customize the Chatbot**:
   - Go to the **Configuration** tab.
   - Set the bot name, avatar image, primary colors, greeting message, and widget position.

4. **Verify on Front End**:
   - Visit your site's frontend. The floating chatbot icon will appear at your selected corner, ready to assist visitors.

---

## Development

### Prerequisites

- Node.js 18+ or 20+ (with npm)
- PHP 7.4+
- A local WordPress development environment (e.g., LocalWP, Docker, or `wp-env`)

### Setup

```bash
cd wp-content/plugins/autonomous-ai-chatbot
npm install
```

### Common Tasks

| Command | Description |
|---|---|
| `npm start` / `npm run watch` | Watch SCSS and React sources and rebuild assets on change (development mode). |
| `npm run buildr` | Build optimized production bundles into `assets/js` and `assets/css`. |
| `npm run build` | Run full build pipeline via `bin/build.sh`. |
| `npm run i18n` | Extract gettext translation strings and build `.pot`, `.po`, and JSON files. |
| `npm run zip` | Package an installable release archive via `bin/zip.sh`. |

> [!NOTE]
> Run `npm run buildr` and verify changes whenever you modify files under `src/js/` or `src/scss/`.

### Project Layout

```
helper/        Core backend helpers (AI prompt generation, dynamic latency, RAG queries, KPI metrics)
includes/      Plugin lifecycle, admin controllers, frontend hooks, translations
templates/     Admin views and frontend chat widget markup
src/           React components and SCSS source stylesheets
assets/        Compiled JavaScript, CSS, icons, and pre-packaged ONNX models
languages/     Localization templates (.pot, .po, .mo, and JSON)
bin/           Build and release packaging scripts
```

---

## Extending the Plugin

The plugin exposes standard WordPress action and filter hooks so developers and add-ons can customize behavior:

### Customizing Dashboard KPI Cards

```php
add_filter( 'wcaichatbot_dashboard_kpis', function ( array $kpis, int $days ) {
    $kpis['custom_metric'] = array(
        'id'             => 'custom_metric',
        'title'          => esc_html__( 'Custom KPI', 'my-plugin' ),
        'value'          => '98%',
        'trend'          => '+5%',
        'trendDirection' => 'up',
        'subtitle'       => esc_html__( 'Custom integration metric', 'my-plugin' ),
        'icon'           => 'chart',
    );
    return $kpis;
}, 10, 2 );
```

### Attaching to Plugin Initialization

```php
add_action( 'wcaichatbot_loaded', function () {
    // Register custom handlers or connect commerce add-ons
} );
```

---

## Documentation

- [Official Documentation & Guides](https://webkul.com/blog/woocommerce-chatgpt-chatbot-documentation/)
- [Support & Ticket System](https://webkul.com/ticket/)

---

## Contributing

Bug reports, feature suggestions, and pull requests are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct, development setup, coding standards, and the pull request process.

All participants are expected to adhere to our [Code of Conduct](CODE_OF_CONDUCT.md).

---

## Security

Please do not disclose security vulnerabilities publicly. Follow our [Security Policy](SECURITY.md) to report security issues privately to the Webkul security team at [support@webkul.com](mailto:support@webkul.com).

---

## License

Autonomous AI Chatbot is open-source software, licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.html).

Copyright © Webkul Software Pvt. Ltd.

*WordPress and WooCommerce are trademarks of Automattic Inc. OpenAI, ChatGPT, and GPT-4 are trademarks of OpenAI, LLC. This project is not affiliated with or endorsed by Automattic Inc. or OpenAI, LLC.*
