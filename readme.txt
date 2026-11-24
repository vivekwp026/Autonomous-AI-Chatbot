=== Autonomous AI Chatbot ===
Contributors: Webkul
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
Tested up to PHP: 8.4
Tags: chatbot, chatgpt, openai, aichatassistant, ai assistant
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Autonomous AI chatbot for WordPress with voice chat, RAG posts and pages training, OpenAI and ONNX embeddings, and live analytics.

== Features ==

Post and Page Content Training with persistent background synchronization across browser reloads.
Dynamic Chatbot Theme, Colors, and Background.
ChatGPT Chat Assistant with Voice Commands (STT & TTS).
Content Creation: In-admin AI writing assistant for blog posts, pages, and voice scripts.
Track Complete Conversation History with date filters, user blocking, and Excel/CSV export.
Live Analytics Dashboard with 10 KPI metric cards, dynamic AI latency tracking, and query resolution rates.
Weekly Traffic Visualization charts (Bar, Area, Line, Scatter).

Integration with ONNX embedding models (e5-small) for local embedding generation.
Support for Google Gemini embeddings alongside OpenAI and Ternlight.
Support for rich HTML and Markdown formatting in frontend chat messages.
Support for Pinecone and ChromaDB vector databases.
Automated Vector DB data cleanup and training reset when switching between vector stores.
TOON structure for optimized token usage and cost reduction.
Independent Smart AI Agent to intelligently route queries between RAG and direct database search.
AI Model Unification to connect any OpenAI-compatible model endpoint.

== Installation ==

1. Upload the `autonomous-ai-chatbot` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress.

== Frequently Asked Questions ==

= How do I train the chatbot? =
Go to Autonomous AI Chatbot → Data Training, select your vector embedding engine, and click "Synchronize All Posts and Pages".

= Can I use my own OpenAI API key? =
Yes. Under Settings, enter your OpenAI API key and select your preferred model.

== Feel free to do so. ==

For any Query please generate a ticket at [Webkul](https://webkul.com/ticket/)

== Changelog ==

= 1.0.0 =
* Initial open-source release of Autonomous AI Chatbot.
* Natural language conversational assistant with text and voice input (STT), speech playback (TTS), and rich formatting.
* RAG website content training for posts and pages with auto-resuming background synchronization across browser reloads.
* Support for OpenAI (GPT-4o, GPT-4, GPT-3.5), Google Gemini, Ternlight, and local ONNX embedding models (e5-small).
* In-admin AI content assistant to draft SEO blog posts, pages, and marketing copy.
* Performance analytics dashboard with dynamic response latency benchmarking and complete conversation history logging.
* Clean preview placeholders and reflection lock overlays for commercial Pro capabilities.
