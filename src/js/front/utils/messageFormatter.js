import { marked } from "marked";

// Helper function to avoid the Only keys without values
function fieldHasValue(content, field) {
    const regex = new RegExp(field + ":\\s*([^\\n\\r]+)", "i");
    const match = content.match(regex);

    if (!match) return false;

    const value = match[1].trim();
    return value.length > 0 && /[a-zA-Z0-9]/.test(value);
}

const _extractCleanUrl = (rawString) => {
    if (!rawString) return "#";

    const mdMatch = rawString.match(/\[.*?\]\([\s\S]*?\)/);

    if (mdMatch) {
        let content = mdMatch[1];

        if (content.includes('href="')) {
            const hrefMatch = content.match(/href="([^"]+)"/);
            if (hrefMatch) {
                let url = hrefMatch[1];
                return url.replace(/\)+$/, "");
            }
        }

        return content.replace(/\)+$/, "");
    }

    return "#";
};

function getHref(html) {
    const div = document.createElement("div");
    div.innerHTML = html;
    const a = div.querySelector("a");
    return a ? a.href : null;
}

function checkStock(stock) {
    const stockText = String(stock).trim().toLowerCase();

    if (
        stockText.includes("instock") ||
        stockText.includes("in stock") ||
        stockText.includes("available") ||
        stockText.includes("backorder") ||
        stockText.includes("on backorder")
    ) {
        return true;
    }

    const qty = parseInt(stockText.match(/\d+/)?.[0] || 0);

    return qty > 0;
}

function extractVariantsFromJSON(content) {
    const variants = [];
    const variantsStartMatch = content.match(
        /VARIANTS:\s*(\[[\s\S]*?\])\s*(?=\n[A-Z]+:|LINK:|$)/i
    );

    if (variantsStartMatch) {
        try {
            const variantsArray = JSON.parse(variantsStartMatch[1]);

            if (Array.isArray(variantsArray)) {
                variantsArray.forEach((variantGroup) => {
                    if (variantGroup.name && Array.isArray(variantGroup.values)) {
                        variants.push({
                            name: variantGroup.name,
                            values: variantGroup.values,
                        });
                    }
                });

                if (variants.length > 0) {
                    return variants;
                }
            }
        } catch (error) { }
    }

    const standardFields = [
        "TITLE",
        "CATEGORY",
        "PRICE",
        "TYPE",
        "STOCK",
        "LINK",
        "VARIANTS",
        "QUANTITY",
        "SKU",
    ];

    const lines = content.split("\n");
    let currentField = null;
    let currentValues = [];

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;

        const fieldMatch = line.match(/^([A-Z][A-Z\s]*?):(.*)$/i);

        if (fieldMatch) {
            const fieldName = fieldMatch[1].toUpperCase().trim();
            const afterColon = fieldMatch[2].trim();

            if (!standardFields.includes(fieldName)) {
                if (currentField && currentValues.length > 0) {
                    variants.push({
                        name: currentField,
                        values: currentValues,
                    });
                }

                currentField = fieldName;
                currentValues = [];

                if (afterColon && !afterColon.match(/^\d+\)/)) {
                    currentValues.push(afterColon);
                }
            }
        } else if (currentField) {
            const valueMatch = line.match(/^(?:\d+[.)]\s*|[•\-*]\s*)(.+)$/);
            if (valueMatch) {
                currentValues.push(valueMatch[1].trim());
            } else if (line && !line.includes(":")) {
                currentValues.push(line);
            }
        }
    }

    if (currentField && currentValues.length > 0) {
        variants.push({
            name: currentField,
            values: currentValues,
        });
    }

    return variants;
}

function getVariantEmoji(variantName) {
    const emojiMap = {
        COLOR: "🎨",
        COLOUR: "🎨",
        SIZE: "📏",
        "DIAL SIZE": "⌚",
        "STRAP COLOR": "🎀",
        LOGO: "✨",
        EDITION: "📋",
        FORMAT: "💿",
        STYLE: "👔",
        MATERIAL: "🧵",
        VERSION: "🔢",
    };

    return emojiMap[variantName.toUpperCase()] || "🔹";
}

export const processMessage = (message) => {
    if (!message) return "";

    console.log("Response Received:", message);

    // Check if this is a category list response
    if (message.includes("<!-- CATEGORY_LIST_RESPONSE -->")) {
        console.log("Category list detected - applying special formatting");

        // Remove the flag
        const cleanedMessage = message
            .replace("<!-- CATEGORY_LIST_RESPONSE -->", "")
            .trim();

        // Extract categories using regex
        const categoryPattern =
            /•\s*<a href="([^"]+)">([^<]+)<\/a>\s*\((\d+)\s+(product|products)\)/g;
        const categories = [];
        let match;

        while ((match = categoryPattern.exec(cleanedMessage)) !== null) {
            categories.push({
                link: match[1],
                name: match[2],
                count: match[3],
                label: match[4],
            });
        }

        if (categories.length > 0) {
            const totalCategories = categories.length;
            const categoriesPerPage = 5;

            // Generate a unique ID for this category list
            const uniqueId = "cat-list-" + Date.now();

            // Create the initial HTML with only first 5 categories
            let html = `
            <div id="${uniqueId}" style="
                font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
                background:#f8f9fa;
                padding:16px;
                border-radius:12px;
                border:1px solid #e2dedeff;
            ">
                <div style="margin-bottom:16px;color:#1a1a1a;font-size:15px;font-weight:600;">
                    📂 Available Product Categories (${totalCategories} total)
                </div>
                <div id="${uniqueId}-grid" style="
                    display:grid;
                    grid-template-columns:repeat(auto-fill,minmax(200px,1fr));
                    gap:12px;
                    margin:12px 0;
                ">`;

            // Render first 5 categories
            const initialCategories = categories.slice(0, categoriesPerPage);
            initialCategories.forEach((cat) => {
                html += `
                <a href="${cat.link}" target="_self" rel="noopener noreferrer" style="
                    display:block;
                    text-decoration:none;
                    border:1px solid #e2dedeff;
                    border-radius:8px;
                    padding:12px;
                    background:linear-gradient(135deg,#ffffff 0%,#fafafa 100%);
                    transition:all 0.2s ease;
                    box-shadow:0 2px 4px rgba(0,0,0,0.05);
                " onmouseover="this.style.boxShadow='0 4px 12px rgba(0,102,255,0.15)';this.style.borderColor='#0066ff';" onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)';this.style.borderColor='#e0e0e0';">
                    <div style="font-weight:600;color:#1a1a1a;font-size:14px;margin-bottom:6px;">
                        ${cat.name}
                    </div>
                    <div style="font-size:12px;color:#666;">
                        ${cat.count} ${cat.label}
                    </div>
                </a>`;
            });

            html += `</div>`;

            // Add "Show More" button if there are more categories
            if (totalCategories > categoriesPerPage) {
                html += `
                <div id="${uniqueId}-btn-container" style="margin-top:20px;text-align:center;">
                    <button id="${uniqueId}-show-more" style="
                        background:linear-gradient(135deg,#0066ff 0%,#0052cc 100%);
                        color:#ffffff;
                        border:1px solid rgba(255,255,255,0.2);
                        padding:12px 28px;
                        border-radius:8px;
                        font-size:14px;
                        font-weight:600;
                        cursor:pointer;
                        box-shadow:0 4px 12px rgba(0,102,255,0.2);
                        transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        display:inline-flex;
                        align-items:center;
                        gap:8px;
                        position:relative;
                        overflow:hidden;
                    " onmouseover="this.style.transform='translateY(-2px) scale(1.02)';this.style.boxShadow='0 6px 20px rgba(0,102,255,0.35)';this.style.background='linear-gradient(135deg,#0052cc 0%,#003d99 100%)';" onmouseout="this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 4px 12px rgba(0,102,255,0.2)';this.style.background='linear-gradient(135deg,#0066ff 0%,#0052cc 100)';">
                        <span style="font-size:13px;">Show More</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="transition:transform 0.3s ease;">
                            <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>`;
            }

            html += `
            </div>`;

            // Store categories data in a global variable for the button handler
            if (!window.categoryListData) {
                window.categoryListData = {};
            }
            window.categoryListData[uniqueId] = {
                categories: categories,
                currentIndex: categoriesPerPage,
                categoriesPerPage: categoriesPerPage,
            };

            // Add event listener after DOM is updated
            setTimeout(() => {
                const showMoreBtn = document.getElementById(`${uniqueId}-show-more`);
                if (showMoreBtn) {
                    showMoreBtn.addEventListener("click", function () {
                        const data = window.categoryListData[uniqueId];
                        const grid = document.getElementById(`${uniqueId}-grid`);
                        const btnContainer = document.getElementById(
                            `${uniqueId}-btn-container`
                        );

                        if (!data || !grid) return;

                        // Get next batch of categories
                        const nextCategories = data.categories.slice(
                            data.currentIndex,
                            data.currentIndex + data.categoriesPerPage
                        );

                        // Append new categories to grid
                        nextCategories.forEach((cat) => {
                            const categoryHtml = `
                            <a href="${cat.link}" target="_self" rel="noopener noreferrer" style="
                                display:block;
                                text-decoration:none;
                                border:1px solid #e2dedeff;
                                border-radius:8px;
                                padding:12px;
                                background:linear-gradient(135deg,#ffffff 0%,#fafafa 100%);
                                transition:all 0.2s ease;
                                box-shadow:0 2px 4px rgba(0,0,0,0.05);
                            " onmouseover="this.style.boxShadow='0 4px 12px rgba(0,102,255,0.15)';this.style.borderColor='#0066ff';" onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.05)';this.style.borderColor='#e0e0e0';">
                                <div style="font-weight:600;color:#1a1a1a;font-size:14px;margin-bottom:6px;">
                                    ${cat.name}
                                </div>
                                <div style="font-size:12px;color:#666;">
                                    ${cat.count} ${cat.label}
                                </div>
                            </a>`;
                            grid.insertAdjacentHTML("beforeend", categoryHtml);
                        });

                        // Update current index
                        data.currentIndex += data.categoriesPerPage;

                        // Update or remove button
                        const remaining = data.categories.length - data.currentIndex;
                        if (remaining > 0) {
                            showMoreBtn.innerHTML = `
                                <span style="font-size:13px;">Show More</span>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="transition:transform 0.3s ease;">
                                    <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            `;
                        } else {
                            // All categories loaded, remove button
                            if (btnContainer) {
                                btnContainer.remove();
                            }
                        }
                    });
                }
            }, 100);

            return html;
        }
    }

    const cleanMessage = message.replace(/\n/g, "\n");

    // Split by separator
    const parts = cleanMessage.split(/═{4,}/);

    let finalHtml =
        '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; line-height:1.5;">';

    parts.forEach((part) => {
        let content = part.trim();
        if (!content) return;
        const hasTitle = fieldHasValue(content, "TITLE");
        const hasCategory = fieldHasValue(content, "CATEGORY");
        const hasPrice = fieldHasValue(content, "PRICE");
        const hasStock = fieldHasValue(content, "STOCK");
        const hasLink = fieldHasValue(content, "LINK");
        const hasType = fieldHasValue(content, "TYPE");
        const hasSKU = fieldHasValue(content, "SKU");
        const hasQuantity = fieldHasValue(content, "QUANTITY");

        // Check if it's a complete product - ALL fields required except VARIANTS
        const isCompleteProduct =
            hasTitle &&
            hasCategory &&
            hasPrice &&
            hasStock &&
            hasLink &&
            hasType &&
            hasSKU &&
            hasQuantity;

        if (isCompleteProduct) {
            const title = content.match(/TITLE:\s*(.+)/i)?.[1].trim() || "";
            const category = content.match(/CATEGORY:\s*(.+)/i)?.[1].trim() || "";
            const price = content.match(/PRICE:\s*(.+)/i)?.[1].trim() || "";
            const stock = content.match(/STOCK:\s*(.+)/i)?.[1].trim() || "";
            const quantity = content.match(/QUANTITY:\s*(.+)/i)?.[1].trim() || null;
            const type = content.match(/TYPE:\s*(.+)/i)?.[1].trim() || "";
            const sku = content.match(/SKU:\s*(.+)/i)?.[1].trim() || "";
            const link = content.match(/LINK:\s*(.+)/i)?.[1].trim() || "#";

            const href = link.match(/href="([^"]+)"/)?.[1] || link;

            const variants = extractVariantsFromJSON(content);

            finalHtml += `
            <div style="
                border:1px solid #e8e8e8;
                border-radius:10px;
                padding:12px;
                margin:10px 0;
                background:linear-gradient(135deg,#ffffff 0%,#fafafa 100%);
                box-shadow:0 2px 8px rgba(0,0,0,0.06);
                max-width:100%;
                box-sizing:border-box;
            ">
                <div style="font-weight:600;font-size:14px;color:#1a1a1a;margin-bottom:6px;">
                    🛍️ ${title}
                </div>
                
                ${category
                    ? `<div style="
                        font-size:12px;
                        color:#666;
                        margin-bottom:6px;
                        display:inline-block;
                        background:#f5f5f5;
                        padding:3px 8px;
                        border-radius:4px;
                    ">🏷️ ${category}</div>`
                    : ""
                }

                <div style="font-size:15px;color:#2e7d32;font-weight:700;margin-bottom:6px;">
                    💰 Price: ${price}
                </div>

             ${stock
                    ? `<span style="
        display:block;
        background:#f4f4f4;
        color:#2e7d32;
        font-size:12px;
        padding:4px 8px;
        border-radius:6px;
        margin-bottom:6px;
    ">🛒 Stock: ${quantity ?? stock}</span>`
                    : ""
                }



                ${type
                    ? `<div style="
                        font-size:12px;
                        color:#1565c0;
                        margin-bottom:6px;
                        display:inline-block;
                        background:#e3f2fd;
                        padding:3px 8px;
                        border-radius:4px;
                        margin-left:6px;
                    ">📦 Type: ${type}</div>`
                    : ""
                }

                ${sku
                    ? `<div style="font-size:12px;color:#555;margin-top:8px;">
                        <span style="font-weight:600;">🔹 SKU:</span>
                        <span style="margin-left:4px;">${sku}</span>
                    </div>`
                    : ""
                }
            `;

            if ((type === "variable" || type === "variation") && variants.length > 0) {
                finalHtml += `<div style="margin-top:10px;padding:8px;background:#f9f9f9;border-radius:6px;">`;

                variants.forEach((variant, index) => {
                    const emoji = getVariantEmoji(variant.name);
                    const values = variant.values.join(", ");

                    finalHtml += `
                    <div style="margin-bottom:${index < variants.length - 1 ? "6px" : "0"
                        };font-size:13px;color:#333;">
                        <span style="font-weight:600;color:#1565c0;">${emoji} ${variant.name
                        }</span>
                        <span style="color:#555;"> : ${values}</span>
                    </div>`;
                });

                finalHtml += `</div>`;
            }

            finalHtml += `
                <div style="margin-top:10px;">
                    <a href="${href}" target="_self" rel="noopener noreferrer" style="
                        display:inline-block;
                        text-decoration:none;
                        background:linear-gradient(135deg,#0066ff 0%,#0052cc 100%);
                        color:#ffffff;
                        padding:8px 16px;
                        border-radius:6px;
                        font-size:13px;
                        font-weight:500;
                        text-align:center;
                        box-shadow:0 2px 6px rgba(0,102,255,0.25);
                        transition:all .2s ease;
                    ">View Product →</a>
                </div>
            </div>`;
        } else if (hasTitle || hasPrice || hasLink || hasCategory || hasStock) {
            finalHtml += `
            
                ${marked.parse(content)}
            `;
        } else {
            // Check if there is at least one real letter or number
            let hasRealText = /[a-zA-Z0-9]/.test(content);

            if (hasRealText) {
                finalHtml += `
            ${marked.parse(content)}
        `;
            }
        }
    });

    finalHtml += "</div>";
    return finalHtml;
};

export default {
    processMessage,
};
