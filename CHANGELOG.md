# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-11-24

First release of the open-source Autonomous AI Chatbot.

### Added

- **Conversational Assistant**
  - Real-time frontend chat widget supporting natural language text and voice input (Speech-to-Text) with voice playback (Text-to-Speech).
  - Rich HTML, markdown formatting, and interactive quick-reply prompts.
  - Dynamic theme colors, custom bot avatars, floating position placement, sound alerts, and customizable greeting messages.
- **Post & Page Content Training (RAG)**
  - Batch-sync posts, pages, and website content into vector databases (Pinecone, ChromaDB) or local ONNX embeddings (`e5-small`).
  - Persistent synchronization state across browser reloads for posts and pages with zero-flicker auto-resume.
  - Automated vector database data cleanup and training reset when switching between vector stores.
- **Smart Query Routing**
  - Autonomous query router automatically directs visitor questions to vector search (RAG) or direct database lookup.
  - TOON structure for optimized token usage and reduced API costs.
  - AI Model Unification supporting any OpenAI-compatible LLM endpoint as well as Google Gemini and local Ternlight.
- **Admin Content Creation**
  - In-admin AI writing assistant to draft SEO blog posts, pages, and marketing content with single-click topic suggestions.
  - Configurable model temperature, max tokens, and prompt templates.
- **Analytics Dashboard & Logs**
  - Comprehensive admin dashboard featuring 10 KPI metric cards, dynamic AI latency tracking (rolling round-trip response benchmark), AI query resolution rates, and traffic visualization charts.
  - Complete conversation history transcripts with date filters, user blocking, and one-click Excel/CSV export.
- **Pro Add-on Integration**
  - Clean preview placeholders and reflection lock overlays for commercial Pro capabilities (WooCommerce product catalog training, HPOS compatibility, guided checkout, Stripe MCP).
  - Official Webkul Store upgrade links configured with one-click activation when installed.
- **Standards & Health Files**
  - Standard open-source community health documentation: [CHANGELOG.md](CHANGELOG.md), [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md), [CONTRIBUTING.md](CONTRIBUTING.md), and [SECURITY.md](SECURITY.md).

[Unreleased]: https://github.com/webkul/autonomous-ai-chatbot/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/webkul/autonomous-ai-chatbot/releases/tag/v1.0.0
