# Security Policy

## Supported versions

Security fixes are released for the latest minor version only.

| Version | Supported |
|---|---|
| 1.0.x | ✅ |
| < 1.0 (legacy versions) | ❌ Please upgrade |

## Reporting a vulnerability

**Please do not open public issues, discussions or pull requests for security problems.**

Report vulnerabilities privately, using either:

- **GitHub private vulnerability reporting**: the *Security* tab of this repository → *Report a vulnerability*; or
- **E-mail**: support@webkul.com, with the subject `[Security] Autonomous AI Chatbot`.

Please include:

- the affected version, and the WordPress / WooCommerce / PHP versions;
- a description of the issue and its impact;
- steps to reproduce, or a proof of concept;
- any suggested fix.

Do not include real customer data, live API keys, tokens, or credentials.

## What to expect

- We acknowledge your report within **3 working days**.
- We send an initial assessment within **10 working days**.
- We keep you informed while we work on a fix, and we agree a disclosure date with you. Our target is a fixed release within 90 days.
- With your permission, we credit you in the changelog.

## Scope

In scope: the code in this repository. That includes the REST endpoints (`chatgpt-bot/v1`), the conversational frontend assistant, OpenAI / Gemini / ONNX integration, vector store synchronizers (Pinecone, ChromaDB), admin settings, and training background handlers.

Out of scope:

- vulnerabilities in WordPress, WooCommerce, or external LLM / vector providers (OpenAI, Google, Pinecone, ChromaDB);
- the commercial Pro add-on (report those to Webkul support);
- problems that need an already-compromised administrator account.
