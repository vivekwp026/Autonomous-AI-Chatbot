# Contributing

Thank you for helping to improve Autonomous AI Chatbot for WooCommerce. This guide explains how to report problems, propose changes, and submit code.

By taking part, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).

## Reporting bugs

1. Search the existing issues first, to avoid duplicates.
2. Open a new issue with the **Bug report** template. Include:
   - the WordPress, WooCommerce, PHP, and plugin versions;
   - the AI model/endpoint in use (OpenAI GPT-4o / GPT-3.5, Google Gemini, Ternlight, or local ONNX);
   - the embedding engine and vector database (Pinecone, ChromaDB, or local ONNX `e5-small`);
   - steps to reproduce, and what you expected to happen;
   - the relevant log entry from **Autonomous AI Chatbot → History** or browser developer tools console.

Remove secret API keys, authorization tokens, passwords, and real customer data from anything you paste.

**Security issues must not be reported in public issues.** Follow [SECURITY.md](SECURITY.md) instead.

## Suggesting features

Open an issue with the **Feature request** template. Describe the problem you are trying to solve, not only the solution. For larger changes, please discuss them in an issue before you start coding.

## Development setup

```bash
# Inside wp-content/plugins of a local WordPress + WooCommerce site.
git clone <repository-url> autonomous-ai-chatbot
cd autonomous-ai-chatbot
npm ci
npm run buildr   # rebuilds production assets while you work
```

You can test OpenAI-compatible endpoints using OpenAI API keys, local Ollama / LM Studio endpoints, or the local ONNX embedding engine.

## Coding standards

- **PHP** follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) and must run on PHP 7.4+. Run `composer run lint` before you commit.
- **JavaScript and SCSS** follow the WordPress ESLint and Stylelint configs. Run `npm run lint:js`.
- Prefix everything global: `wc_cptbot_` and `wcaichatbot_` for functions, options, transients, and hooks; `WC_CPTBOT_` and `WCAICHATBOT_` for constants; `WCAICHATBOT\` for PHP namespaces.
- All user-facing strings must be translatable with the text domain `autonomous-ai-chatbot`.
- Security rules:
  - check capabilities (`manage_options`) and nonces for admin requests;
  - sanitize input (`sanitize_text_field`, `wp_unslash`) and escape output (`esc_html`, `esc_attr`, `esc_url`);
  - use `$wpdb->prepare()` for all custom SQL statements;
  - never expose LLM API secret keys or sensitive configuration to the frontend browser client.
- Adding or changing a public hook? Document the hook in `README.md` in the same pull request.

## Pull requests

1. Fork the repository and create a branch from `dev` or `main` (for example `fix/rag-vector-upsert`).
2. Keep each pull request focused on one change.
3. If you changed anything in `src/`, run `npm run buildr` and commit the updated `assets/` files.
4. Add an entry under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
5. Make sure all lint checks pass, then open the pull request using the template.

A maintainer reviews every pull request. Please be patient, and be ready to make changes.

## Commit messages

Use the imperative mood in a short summary line, for example "Fix vector upsert timeout during batch sync". Add a body that explains *why*, if the change is not obvious.

## License

By contributing, you agree that your contributions are licensed under the [GNU General Public License v3.0 or later](https://www.gnu.org/licenses/gpl-3.0.html), the same license as the project.

