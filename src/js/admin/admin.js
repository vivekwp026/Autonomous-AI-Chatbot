import { loadModel, upsertData, upsertPostPageData } from "./../action/index.js";

const cptbot = jQuery.noConflict();
const TRANSLATION = wc_cptbot.translation;
const product_count = wc_cptbot.product_count.count;

let session = null;

window.debugCheckpoint = function () {
    const localCheckpoints = Object.keys(localStorage).filter((k) =>
        k.includes("onnx_sync_checkpoint"),
    );
    const sessionCheckpoints = Object.keys(sessionStorage).filter((k) =>
        k.includes("onnx_sync_checkpoint"),
    );
    const defaultKey = "onnx_sync_checkpoint_0";
    const localData = localStorage.getItem(defaultKey);
    const sessionData = sessionStorage.getItem(defaultKey);
    const result = window.getSyncCheckpointKey();
};

export function showAdminNotice(message, type = "error") {
    const existing = document.querySelector(".cptbot-admin-toast-notice");
    if (existing) existing.remove();

    const noticeEl = document.createElement("div");
    noticeEl.className = `notice notice-${type} is-dismissible cptbot-admin-toast-notice`;
    noticeEl.style.cssText = "margin: 15px 0; padding: 10px 14px; position: relative;";
    noticeEl.innerHTML = `<p style="margin: 0.25em 0; font-size: 13px;">${message}</p><button type="button" class="notice-dismiss" style="position: relative; float: right; padding: 9px; margin-top: -24px;"><span class="screen-reader-text">Dismiss</span></button>`;

    noticeEl.querySelector(".notice-dismiss").addEventListener("click", (e) => {
        e.preventDefault();
        noticeEl.remove();
    });

    const targetHeader =
        document.querySelector("hr.wp-header-end") ||
        document.querySelector(".wrap h1") ||
        document.querySelector(".wc-pos-wrapper");

    if (targetHeader && targetHeader.parentNode) {
        targetHeader.parentNode.insertBefore(noticeEl, targetHeader.nextSibling);
    } else {
        document.body.prepend(noticeEl);
    }
}

export function showAdminConfirmModal({
    title = "Confirmation",
    message,
    confirmText = "Confirm",
    cancelText = "Cancel",
    onConfirm,
}) {
    const modalId = "cptbot-admin-confirm-modal";
    const existing = document.getElementById(modalId);
    if (existing) existing.remove();

    const modalHtml = `
        <div id="${modalId}" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; justify-content: center; align-items: center;">
            <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <h2 style="margin-top: 0; color: #d63638; font-size: 20px;">⚠️ ${title}</h2>
                <p style="font-size: 14px; line-height: 1.5; margin: 16px 0; color: #3c434a;">${message}</p>
                <div style="margin-top: 25px; display: flex; justify-content: center; gap: 14px;">
                    <button id="cptbot-confirm-action-btn" class="button button-primary button-large" style="background-color: #d63638; border-color: #d63638;">${confirmText}</button>
                    <button id="cptbot-cancel-action-btn" class="button button-large">${cancelText}</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHtml);
    const modal = document.getElementById(modalId);

    document.getElementById("cptbot-cancel-action-btn").addEventListener("click", () => {
        modal.remove();
    });

    document.getElementById("cptbot-confirm-action-btn").addEventListener("click", () => {
        modal.remove();
        if (typeof onConfirm === "function") {
            onConfirm();
        }
    });
}

window.clearOnnxCheckpoints = function () {
    const keysToRemove = [
        ...Object.keys(localStorage).filter((k) =>
            k.startsWith("onnx_sync_checkpoint_"),
        ),
        ...Object.keys(sessionStorage).filter((k) =>
            k.startsWith("onnx_sync_checkpoint_"),
        ),
        "cptbot_sync_checkpoint",
        "lastIndex",
        "lastProductId",
        "cptbot_posts_pages_sync_state",
    ];
    keysToRemove.forEach((key) => {
        localStorage.removeItem(key);
        sessionStorage.removeItem(key);
    });
};

// Helper function to get the sync checkpoint key (looks for any checkpoint or default to 0)
window.getSyncCheckpointKey = function () {
    let key = "onnx_sync_checkpoint_0";
    let data = sessionStorage.getItem(key);

    if (data) {
        return key;
    }

    // Fallback to localStorage
    data = localStorage.getItem(key);

    if (data) {
        return key;
    }

    // Look for any checkpoint in sessionStorage
    let allCheckpoints = Object.keys(sessionStorage).filter((k) =>
        k.startsWith("onnx_sync_checkpoint_"),
    );

    if (allCheckpoints.length > 0) {
        return allCheckpoints[0];
    }

    // Look for any checkpoint in localStorage
    allCheckpoints = Object.keys(localStorage).filter((k) =>
        k.startsWith("onnx_sync_checkpoint_"),
    );

    if (allCheckpoints.length > 0) {
        return allCheckpoints[0];
    }

    return null;
};

// Helper function to resume sync after page reload
window.resumeOnnxSync = function () {
    const checkpointKey = window.getSyncCheckpointKey();

    if (!checkpointKey) {
        return false;
    }

    // Read from BOTH storages - prefer sessionStorage
    let checkpointData = sessionStorage.getItem(checkpointKey);
    if (!checkpointData) {
        checkpointData = localStorage.getItem(checkpointKey);
    }

    if (!checkpointData) {
        return false;
    }

    const checkpoint = JSON.parse(checkpointData);

    // Check if session exists (only required for onnx)
    if (wc_cptbot.modal_type === "onnx" && !session) {
        return false;
    }

    const limit = wc_cptbot.limit || 10;
    const lastIndex = checkpoint.lastIndex || 0;
    const offset = lastIndex * limit;
    const i = lastIndex + 1;

    // Get and update UI elements
    const loader = document.querySelector(".lds-dual-ring");
    const export_p = document.querySelector("#cptbot-export-products");

    if (loader) {
        loader.style.display = "inline-block";
    }

    if (export_p) {
        export_p.disabled = true;
    }

    try {
        wc_cptbot_sync_all_product(limit, offset, i);
        return true;
    } catch (error) {
        return false;
    }
};

// Helper function to check for unfinished sync on page load
window.checkForUnfinishedSync = function () {
    const checkpointKey = window.getSyncCheckpointKey();

    if (!checkpointKey) {
        return null;
    }

    // Read from BOTH storages - prefer sessionStorage
    let checkpointData = sessionStorage.getItem(checkpointKey);
    if (!checkpointData) {
        checkpointData = localStorage.getItem(checkpointKey);
    }

    if (!checkpointData) {
        return null;
    }

    try {
        const checkpoint = JSON.parse(checkpointData);
        return {
            hasCheckpoint: true,
            lastIndex: checkpoint.lastIndex,
            lastProductId: checkpoint.lastProductId,
            lastOffset: checkpoint.lastOffset || 0,
            timestamp: checkpoint.timestamp,
            error: checkpoint.error,
        };
    } catch (e) {
        return null;
    }
};

// Helper function to display checkpoint status
window.checkpointStatus = function () {
    const checkpointKey = window.getSyncCheckpointKey();

    if (!checkpointKey) {
        return;
    }

    try {
        // Read from BOTH storages - prefer sessionStorage
        let checkpointData = sessionStorage.getItem(checkpointKey);
        let source = "sessionStorage";

        if (!checkpointData) {
            checkpointData = localStorage.getItem(checkpointKey);
            source = "localStorage";
        }

        if (!checkpointData) {
            return;
        }

        const checkpoint = JSON.parse(checkpointData);
    } catch (e) {
        return;
    }
};

// Expose ONNX update function globally
window.wc_cptbot_update_onnx_data = async function (
    data,
    actionId = 0,
    offset = 0,
) {

    if (wc_cptbot.modal_type === "onnx" && !session) {
        console.error(
            "ONNX Session not initialized. Please wait for the model to load.",
        );
        return { success: false, error: "ONNX Session not initialized" };
    }

    return await upsertData(data, cptbot.ajax, session, actionId, offset);
};

// Expose ONNX update function for posts and pages
window.wc_cptbot_upsert_post_page_onnx = async function (postId, text, force = true) {
    if (wc_cptbot.modal_type === "onnx" && !session) {
        console.error(
            "ONNX Session not initialized. Please wait for the model to load.",
        );
        return { success: false, error: "ONNX Session not initialized" };
    }

    return await upsertPostPageData(postId, text, cptbot.ajax, session, force);
};

window.wc_cptbot_get_onnx_session = function () {
    return session;
};

document.addEventListener("DOMContentLoaded", () => {
    if (wc_cptbot.modal_type === "onnx") {
        loadModel("e5-small", wc_cptbot.onnx_e5small_modal);

        document.addEventListener("onOnnxModelLoad", (e) => {
            session = !!e?.detail?.session ? e.detail.session : null;
            const modelReadyEvent = new CustomEvent("onOnnxModelReady", {
                detail: { session: session },
            });
            document.dispatchEvent(modelReadyEvent);

            // Auto-resume sync if checkpoint exists - ONNX only
            const progressBar = document.querySelector(
                "#cptbot_products_progressbar",
            );
            const trainedEl = document.querySelector(
                ".wc_cptbot_trained_products",
            );
            const trained = trainedEl
                ? parseInt(trainedEl.textContent || trainedEl.innerText || "0")
                : 0;
            const alreadyComplete =
                (progressBar && progressBar.value >= 100) ||
                trained >= product_count;

            if (alreadyComplete) {
                window.clearOnnxCheckpoints();
                return;
            }
            window.checkpointStatus();

            const syncStatus = window.checkForUnfinishedSync();

            if (syncStatus && syncStatus.hasCheckpoint) {
                setTimeout(() => {
                    window.resumeOnnxSync();
                }, 2000);
            }
        });
    } else if (wc_cptbot.modal_type === "ternlight") {
        // Auto-resume sync if checkpoint exists - Ternlight
        const progressBar = document.querySelector(
            "#cptbot_products_progressbar",
        );
        const trainedEl = document.querySelector(
            ".wc_cptbot_trained_products",
        );
        const trained = trainedEl
            ? parseInt(trainedEl.textContent || trainedEl.innerText || "0")
            : 0;
        const alreadyComplete =
            (progressBar && progressBar.value >= 100) ||
            trained >= product_count;

        if (alreadyComplete) {
            window.clearOnnxCheckpoints();
            return;
        }
        window.checkpointStatus();

        const syncStatus = window.checkForUnfinishedSync();

        if (syncStatus && syncStatus.hasCheckpoint) {
            setTimeout(() => {
                window.resumeOnnxSync();
            }, 2000);
        }
    }

    const footer_thankyou = document.querySelector("#footer-thankyou");
    const footer_upgrade = document.querySelector("#footer-upgrade");
    const export_p = document.querySelector("#cptbot-export-products");
    const client_form = document.querySelector("#client_form");
    const submit_form = document.querySelector("#submit_form");
    const createindex = document.querySelector("#cptbot_create_index");
    const chatbot_avatar = document.querySelector("#chatbot_avatar");
    const index_host_edit = document.querySelector(
        "#wc_cptbot_pinecone_index_host_edit",
    );
    const index_name_edit = document.querySelector(
        "#wc_cptbot_pinecone_index_name_edit",
    );
    const index_host = document.querySelector("#wc_cptbot_pinecone_index_host");
    const index_name = document.querySelector("#wc_cptbot_pinecone_index_name");
    const pinecone_host = document.querySelector(
        "input[name=wc_cptbot_pinecone_host]",
    );
    const pinecone_index_host = document.querySelector(
        "input[name=wc_cptbot_pinecone_index_host]",
    );
    const pinecone_indexname = document.querySelector(
        "input[name=wc_cptbot_pinecone_indexname]",
    );
    const chatbot_bg_image = document.querySelector("#chatbot_bg_image");
    const chatbot_ai_logo = document.querySelector("#chatbot_ai_logo");
    const reschedule_product = document.querySelectorAll(".reschedule_product");
    let loader = document.querySelector(".lds-dual-ring");

    if (!!footer_thankyou) {
        footer_thankyou.innerHTML = TRANSLATION.thank_you_msg;
    }

    if (!!footer_upgrade) {
        footer_upgrade.innerHTML = TRANSLATION.version;
    }

    if (!!index_host_edit && !!index_host) {
        index_host_edit.addEventListener("click", () => {
            index_host.disabled = false;
            index_host.focus();
        });
    }

    if (!!index_name_edit && !!index_name) {
        index_name_edit.addEventListener("click", () => {
            index_name.disabled = false;
            index_name.focus();
        });
    }

    if (!!index_host) {
        const syncPineconeHost = (e) => {
            let val = e.target.value.trim().replace(/^https?:\/\//i, "").replace(/\/+$/, "");
            if (pinecone_host) {
                pinecone_host.value = val;
            }
            if (pinecone_index_host) {
                pinecone_index_host.value = val;
            }
        };
        ["keyup", "input", "change", "paste"].forEach((evt) => {
            index_host.addEventListener(evt, syncPineconeHost);
        });
    }

    if (!!index_name) {
        const syncPineconeIndexName = (e) => {
            if (pinecone_indexname) {
                pinecone_indexname.value = e.target.value.trim();
            }
        };
        ["keyup", "input", "change", "paste"].forEach((evt) => {
            index_name.addEventListener(evt, syncPineconeIndexName);
        });
    }

    const configForm = index_host ? index_host.closest("form") : null;
    if (configForm) {
        configForm.addEventListener("submit", () => {
            if (index_host) {
                let val = index_host.value.trim().replace(/^https?:\/\//i, "").replace(/\/+$/, "");
                if (pinecone_host) {
                    pinecone_host.value = val;
                }
                if (pinecone_index_host) {
                    pinecone_index_host.value = val;
                }
            }
            if (index_name && pinecone_indexname) {
                pinecone_indexname.value = index_name.value.trim();
            }
        });
    }

    if (!!export_p) {
        export_p.addEventListener("click", (e) => {
            e.target.disabled = true;
            loader.style.display = "inline-block";
            let limit = wc_cptbot.limit;
            let trained_count = parseInt(wc_cptbot.trained_count || 0);
            let offset = trained_count;
            let i = Math.floor(trained_count / limit) + 1;

            if (parseInt(limit) > parseInt(product_count)) {
                limit = product_count;
            }

            try {
                const checkpointData = {
                    lastIndex: 0,
                    lastProductId: "starting",
                    status: "started",
                    timestamp: new Date().toISOString(),
                };
                localStorage.setItem(
                    "onnx_sync_checkpoint_0",
                    JSON.stringify(checkpointData),
                );
                sessionStorage.setItem(
                    "onnx_sync_checkpoint_0",
                    JSON.stringify(checkpointData),
                );
            } catch (error) {
                return;
            }

            wc_cptbot_sync_all_product(limit, offset, i);
        });

        // Show checkpoint info if exists
        const checkpointKey = window.getSyncCheckpointKey();
        if (checkpointKey) {
            const checkpoint = JSON.parse(localStorage.getItem(checkpointKey));
        }

        const reset_sync = document.querySelector("#cptbot-reset-sync");
        if (!!reset_sync) {
            reset_sync.addEventListener("click", (e) => {
                showAdminConfirmModal({
                    title: TRANSLATION.warning || "Reset Confirmation",
                    message:
                        TRANSLATION.reset_confirm ||
                        "Are you sure you want to reset synchronization progress? This will NOT delete vectors from the database, but will restart the local tracking from zero.",
                    confirmText: TRANSLATION.yes_delete || "Yes, Reset",
                    cancelText: TRANSLATION.cancel || "Cancel",
                    onConfirm: () => {
                        e.target.disabled = true;
                        cptbot.ajax({
                            type: "POST",
                            url: wc_cptbot.admin_ajax,
                            data: {
                                action: "wc_cptbot_reset_sync",
                                nonce: wc_cptbot.nonce,
                            },
                            success: function (response) {
                                if (response.success) {
                                    window.clearOnnxCheckpoints();
                                    showAdminNotice(
                                        TRANSLATION.done || "Synchronization reset successfully.",
                                        "success",
                                    );
                                    setTimeout(() => location.reload(), 900);
                                } else {
                                    showAdminNotice(response.status || "Reset failed", "error");
                                    e.target.disabled = false;
                                }
                            },
                            error: function () {
                                showAdminNotice("Server error occurred while resetting.", "error");
                                e.target.disabled = false;
                            },
                        });
                    },
                });
            });
        }
    }

    if (!!client_form) {
        client_form.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(client_form);
            const query = [...formData.entries()];
            cptbot.ajax({
                type: "POST",
                url: wc_cptbot.admin_ajax,
                data: {
                    action: "wc_cptbot_client_query",
                    nonce: wc_cptbot.nonce,
                    query: query,
                },
                beforeSend: function () {
                    submit_form.value = TRANSLATION.thinking;
                },
                success: function (response) {
                    submit_form.value = TRANSLATION.ask_again;
                },
            });
        });
    }

    if (!!createindex) {
        createindex.addEventListener("click", (e) => {
            e.preventDefault();
            e.target.disabled = true;
            cptbot.ajax({
                type: "POST",
                url: wc_cptbot.admin_ajax,
                data: {
                    action: "wc_cptbot_create_pinecone_index",
                    nonce: wc_cptbot.nonce,
                },
                beforeSend: function () {
                    createindex.innerHTML = TRANSLATION.index_creating;
                },
                success: function (response) {
                    e.target.disabled = false;
                    let container = document.querySelector(".wc-pos-container");
                    let msg = "";
                    if (response.success) {
                        createindex.innerHTML = TRANSLATION.index_created;
                        if (response.data && response.data.host) {
                            const newHost = response.data.host.replace(/^https?:\/\//i, "").replace(/\/+$/, "");
                            if (index_host) {
                                index_host.value = "https://" + newHost + "/";
                            }
                            if (pinecone_host) {
                                pinecone_host.value = newHost;
                            }
                            if (pinecone_index_host) {
                                pinecone_index_host.value = newHost;
                            }
                        }
                        msg = `<div class="notice notice-success">
                            <p><strong>${TRANSLATION.pinecone_success}</strong></p>
                        </div>`;
                    } else {
                        createindex.innerHTML = TRANSLATION.recreate_msg;
                        msg = `<div class="notice notice-error">
                            <p><strong>${response.data}</strong></p>
                        </div>`;
                    }
                    if (!!container) {
                        container.children[1].insertAdjacentHTML(
                            "afterbegin",
                            msg,
                        );
                    }
                },
            });
        });
    }

    if (!!chatbot_avatar) {
        chatbot_avatar.addEventListener("click", (e) => {
            e.preventDefault();

            let args = {
                header_text: TRANSLATION.cpt_avtar_txt,
                button_text: TRANSLATION.upl_logo,
                img_src_class: "wc-chatbot-avatar",
                img_path: "chatbot_avatar_url",
            };
            wc_chatbot_media(args, e);
        });
    }

    if (!!chatbot_bg_image) {
        chatbot_bg_image.addEventListener("click", (e) => {
            e.preventDefault();
            let args = {
                header_text: TRANSLATION.bg_img,
                button_text: TRANSLATION.upl_bg_img,
                img_src_class: "wc-chatbot-bg-image",
                img_path: "chatbot_bg_image_path",
            };
            wc_chatbot_media(args, e);
        });
    }

    if (!!chatbot_ai_logo) {
        chatbot_ai_logo.addEventListener("click", (e) => {
            e.preventDefault();
            let args = {
                header_text: TRANSLATION.ai_logo,
                button_text: TRANSLATION.upl_ai_logo,
                img_src_class: "wc-chatbot-logo-img",
                img_path: "chatbot_ai_logo_path",
            };
            wc_chatbot_media(args, e);
        });
    }

    if (!!reschedule_product) {
        reschedule_product.forEach((sche_product) => {
            sche_product.addEventListener("click", (e) => {
                e.preventDefault();

                if (!!wc_cptbot?.modal_type) {
                    let action_id = e.target.getAttribute("data-id");
                    e.target.disabled = true;
                    e.target.innerHTML = TRANSLATION.running;
                    switch (wc_cptbot.modal_type) {
                        case "onnx":
                            if (!!session) {
                                cptbot.ajax({
                                    type: "POST",
                                    url: wc_cptbot.admin_ajax,
                                    data: {
                                        action: "wc_cptbot_get_product_data",
                                        nonce: wc_cptbot.nonce,
                                        action_id: action_id,
                                        type: "single",
                                    },
                                    success: async function (response) {
                                        if (
                                            !!response?.success &&
                                            response?.data
                                        ) {
                                            await upsertData(
                                                response.data,
                                                cptbot.ajax,
                                                session,
                                                action_id,
                                            );
                                            showAdminNotice(TRANSLATION.pr_trained, "success");
                                            setTimeout(() => location.reload(), 900);
                                        }
                                    },
                                });
                            }
                            break;
                        case "ternlight":
                            cptbot.ajax({
                                type: "POST",
                                url: wc_cptbot.admin_ajax,
                                data: {
                                    action: "wc_cptbot_get_product_data",
                                    nonce: wc_cptbot.nonce,
                                    action_id: action_id,
                                    type: "single",
                                },
                                success: async function (response) {
                                    if (
                                        !!response?.success &&
                                        response?.data
                                    ) {
                                        await upsertData(
                                            response.data,
                                            cptbot.ajax,
                                            null, // no session for ternlight
                                            action_id,
                                        );
                                        showAdminNotice(TRANSLATION.pr_trained, "success");
                                        setTimeout(() => location.reload(), 900);
                                    }
                                },
                            });
                            break;
                        case "openai":
                        case "gemini":
                            cptbot.ajax({
                                type: "POST",
                                url: wc_cptbot.admin_ajax,
                                data: {
                                    action: "wc_cptbot_reschedule_action",
                                    nonce: wc_cptbot.nonce,
                                    action_id: action_id,
                                },
                                success: function (response) {
                                    if (response) {
                                        showAdminNotice(TRANSLATION.pr_trained, "success");
                                        setTimeout(() => location.reload(), 900);
                                    }
                                },
                            });
                            break;
                        default:
                            return;
                    }
                }
            });
        });
    }
});

function wc_cptbot_sync_all_product(limit, offset, i) {
    const export_p = document.querySelector("#cptbot-export-products");
    new Promise((resolve) => {
        switch (wc_cptbot.modal_type) {
            case "gemini":
                // Gemini embeddings are generated server-side via Gemini API
                cptbot.ajax({
                    type: "POST",
                    url: wc_cptbot.admin_ajax,
                    data: {
                        action: "wc_cptbot_sync_all_product",
                        nonce: wc_cptbot.nonce,
                        limit: limit,
                        offset: offset,
                    },
                    beforeSend: function () {
                        export_p.innerHTML = TRANSLATION.pro_syncing;
                    },
                    success: function (response) {
                        resolve(response);
                    },
                    error: function (error) {
                        console.error("❌ GEMINI SYNC ERROR:", {
                            message: error?.statusText || "AJAX error",
                            status: error?.status,
                            timestamp: new Date().toISOString(),
                        });
                        logToServer(
                            {
                                error_type: "gemini_sync_ajax",
                                error_message:
                                    error?.statusText || "AJAX error",
                                http_status: error?.status,
                                timestamp: new Date().toISOString(),
                            },
                            "error",
                        );
                        resolve({
                            checkpoint: true,
                            error:
                                "AJAX request failed: " +
                                (error?.statusText || "Unknown error"),
                        });
                    },
                });
                break;
            case "openai":
                cptbot.ajax({
                    type: "POST",
                    url: wc_cptbot.admin_ajax,
                    data: {
                        action: "wc_cptbot_sync_all_product",
                        nonce: wc_cptbot.nonce,
                        limit: limit,
                        offset: offset,
                    },
                    beforeSend: function () {
                        export_p.innerHTML = TRANSLATION.pro_syncing;
                    },
                    success: function (response) {
                        resolve(response);
                    },
                });
                break;
            case "onnx":
                if (!!session) {
                    cptbot.ajax({
                        type: "POST",
                        url: wc_cptbot.admin_ajax,
                        data: {
                            action: "wc_cptbot_get_product_data",
                            nonce: wc_cptbot.nonce,
                            limit: limit,
                            offset: offset,
                            type: "all",
                        },
                        beforeSend: function () {
                            export_p.innerHTML = TRANSLATION.pro_syncing;
                        },
                        success: async function (response) {
                            console.log(response, 'response');
                            // Check if training is complete
                            if (response?.complete) {
                                resolve({
                                    code: 200,
                                    complete: true,
                                    products_trained: response.products_trained,
                                    total_count: response.total_count,
                                });
                                return;
                            }

                            if (
                                !!response?.success &&
                                response?.data &&
                                Object.keys(response.data).length > 0
                            ) {
                                try {
                                    let res = await upsertData(
                                        response.data,
                                        cptbot.ajax,
                                        session,
                                    );
                                    resolve(res);
                                } catch (upsertError) {
                                    resolve({
                                        checkpoint: true,
                                        error: upsertError.message,
                                    });
                                }
                            } else if (
                                response?.success === false &&
                                response?.message
                            ) {
                                resolve({
                                    code: 400,
                                    status: response.message,
                                    error: true,
                                });
                            } else {
                                resolve(response);
                            }
                        },
                        error: function (error) {
                            resolve({
                                checkpoint: true,
                                error: "AJAX request failed",
                            });
                        },
                    });
                }
                break;
            case "ternlight":
                cptbot.ajax({
                    type: "POST",
                    url: wc_cptbot.admin_ajax,
                    data: {
                        action: "wc_cptbot_get_product_data",
                        nonce: wc_cptbot.nonce,
                        limit: limit,
                        offset: offset,
                        type: "all",
                    },
                    beforeSend: function () {
                        export_p.innerHTML = TRANSLATION.pro_syncing;
                    },
                    success: async function (response) {
                        console.log(response, 'response');
                        // Check if training is complete
                        if (response?.complete) {
                            resolve({
                                code: 200,
                                complete: true,
                                products_trained: response.products_trained,
                                total_count: response.total_count,
                            });
                            return;
                        }

                        if (
                            !!response?.success &&
                            response?.data &&
                            Object.keys(response.data).length > 0
                        ) {
                            try {
                                let res = await upsertData(
                                    response.data,
                                    cptbot.ajax,
                                    null, // no session for ternlight
                                    0,
                                    offset
                                );
                                resolve(res);
                            } catch (upsertError) {
                                resolve({
                                    checkpoint: true,
                                    error: upsertError.message,
                                });
                            }
                        } else if (
                            response?.success === false &&
                            response?.message
                        ) {
                            resolve({
                                code: 400,
                                status: response.message,
                                error: true,
                            });
                        } else {
                            resolve(response);
                        }
                    },
                    error: function (error) {
                        resolve({
                            checkpoint: true,
                            error: "AJAX request failed",
                        });
                    },
                });
                break;
            default:
                break;
        }
    }).then((data) => {
        let loader = document.querySelector(".lds-dual-ring");
        const export_p = document.querySelector("#cptbot-export-products");

        // Handle training complete flag
        if (data?.complete && (wc_cptbot.modal_type === "onnx" || wc_cptbot.modal_type === "ternlight")) {
            // ✅ FIX: Update UI elements BEFORE reload
            // Stop the loader completely
            if (loader) {
                loader.style.display = "none";
                loader.style.visibility = "hidden";
            }

            // Update progress bar to 100%
            let cptbot_products_progressbar = document.querySelector(
                "#cptbot_products_progressbar",
            );
            if (cptbot_products_progressbar) {
                cptbot_products_progressbar.value = 100;
                cptbot_products_progressbar.innerHTML = "100%";
            }

            // Update trained products counter
            let wc_cptbot_trained_products = document.querySelector(
                ".wc_cptbot_trained_products",
            );
            if (wc_cptbot_trained_products) {
                wc_cptbot_trained_products.innerHTML =
                    data.total_count || product_count;
            }

            // Update button
            if (export_p) {
                export_p.innerHTML = TRANSLATION.done;
                export_p.disabled = false;
            }

            // Clear session checkpoint since training is complete
            sessionStorage.removeItem("cptbot_sync_checkpoint");
            sessionStorage.removeItem("lastIndex");
            sessionStorage.removeItem("lastProductId");

            // Clear all ONNX checkpoints
            const checkpointKeys = Object.keys(localStorage).filter((k) =>
                k.startsWith("onnx_sync_checkpoint_"),
            );
            checkpointKeys.forEach((key) => localStorage.removeItem(key));

            window.clearOnnxCheckpoints();

            // Small delay to ensure UI updates are rendered before reload
            // setTimeout(() => {
            //     // location.reload();
            // }, 500);
            return;
        }

        // Handle ONNX abort errors with full page reload for fresh start
        // if (data?.checkpoint && wc_cptbot.modal_type === "onnx") {
        //     // Show message to user
        //     if (loader) loader.style.display = "none";
        //     if (export_p) export_p.disabled = false;

        //     // Wait 2 seconds before reload to ensure checkpoint is saved
        //     setTimeout(() => {
        //         location.reload();
        //     }, 2000);
        //     return;
        // }

        // Handle undefined data response
        if (!data || typeof data === "undefined") {
            return;
        }

        if (data.code === 200) {
            let wc_cptbot_trained_products = document.querySelector(
                ".wc_cptbot_trained_products",
            );
            let cptbot_products_progressbar = document.querySelector(
                "#cptbot_products_progressbar",
            );
            wc_cptbot_trained_products.innerHTML = data.products_trained || 0;
            cptbot_products_progressbar.innerHTML =
                (data.products_trained || 0) + "%";
            cptbot_products_progressbar.value =
                ((data.products_trained || 0) / product_count) * 100;

            export_p.innerHTML =
                TRANSLATION.pro_syncing +
                ` (${data.products_trained || 0}/${product_count})`;

            offset = limit * i;
            i++;
            if (offset < product_count) {
                wc_cptbot_sync_all_product(limit, offset, i);
            } else {
                loader.style.display = "none";
                export_p.innerHTML = TRANSLATION.done;
                export_p.disabled = false;
                location.reload();
            }
        } else if (data.code === 400 && data.error) {
            // Only show error if it's NOT a "Product is not found" message (which means end of products)
            if (
                data.status === "Product is not found!" ||
                data.status?.includes("Product is not found")
            ) {
                // Clear session checkpoint since training is done
                sessionStorage.removeItem("cptbot_sync_checkpoint");
                sessionStorage.removeItem("lastIndex");
                sessionStorage.removeItem("lastProductId");

                loader.style.display = "none";
                export_p.innerHTML = TRANSLATION.done;
                export_p.disabled = false;
                location.reload();
            } else {
                loader.style.display = "none";
                export_p.disabled = false;
                showAdminNotice(data.status || "Unknown error occurred", "error");
            }
        } else {
            loader.style.display = "none";
            export_p.disabled = false;
            showAdminNotice(data.status || "Unknown error occurred", "error");
        }
    });
}

function wc_chatbot_media(args, e) {
    var custom_uploader;
    var custom_uploader = wp
        .media({
            title: args.header_text,
            button: {
                text: args.button_text,
            },
            library: {
                type: ["image"],
            },
            multiple: false, // Set this to true to allow multiple files to be selected.
        })
        .on("select", function () {
            var attachment = custom_uploader
                .state()
                .get("selection")
                .first()
                .toJSON();
            var file_short_path = attachment.url.split("uploads")[1];
            if (
                attachment.subtype === "png" ||
                attachment.subtype === "jpg" ||
                attachment.subtype === "jpeg" ||
                attachment.subtype === "webp"
            ) {
                cptbot("." + args.img_src_class).attr("src", attachment.url);
                cptbot("#" + args.img_path).val(file_short_path);
            } else {
                if (
                    e.target.parentNode.getAttribute("class") ===
                    "button-secondary btn-upload"
                ) {
                    e.target.parentNode.parentNode.nextElementSibling.style.display =
                        "block";
                    e.target.parentNode.parentNode.nextElementSibling.innerHTML =
                        TRANSLATION.img_found_txt +
                        " " +
                        "<b>" +
                        attachment.subtype +
                        "</b>" +
                        " " +
                        TRANSLATION.need +
                        " " +
                        "<b>" +
                        " ( png, jpeg )" +
                        "</b>";
                } else {
                    e.target.parentNode.nextElementSibling.style.display =
                        "block";
                    e.target.parentNode.nextElementSibling.innerHTML =
                        TRANSLATION.img_found_txt +
                        " " +
                        "<b>" +
                        attachment.subtype +
                        "</b>" +
                        " " +
                        TRANSLATION.need +
                        " " +
                        "<b>" +
                        " ( png, jpeg )" +
                        "</b>";
                }
            }
        })
        .open();
}

document.addEventListener("DOMContentLoaded", () => {
    const modelTypeSelect = document.querySelector("#cpt_bot_model_type");
    const vectorDbSelect = document.querySelector("#wc_cptbot_vector_db");

    // Initialize previous values
    if (modelTypeSelect) {
        modelTypeSelect.dataset.previousValue = modelTypeSelect.value;
    }
    if (vectorDbSelect) {
        vectorDbSelect.dataset.previousValue = vectorDbSelect.value;
    }

    function handleTrainingReset(e) {
        if (e.detail && e.detail.skipCheck) return;

        const target = e.target;
        const newValue = target.value;
        const previousValue =
            target.dataset.previousValue || target.defaultValue;

        // Check if there is trained data (products or posts & pages)
        const productTrainedCount = parseInt(wc_cptbot.trained_count || 0);
        const postsPagesTrainedCount = parseInt(wc_cptbot.posts_pages_trained_count || 0);
        const hasTrainedData = Boolean(wc_cptbot.has_trained_data) || productTrainedCount > 0 || postsPagesTrainedCount > 0;

        if (hasTrainedData) {
            // Create Modal
            const modalId = "cptbot-reset-confirmation-modal";
            // Remove existing if any
            const existingModal = document.getElementById(modalId);
            if (existingModal) existingModal.remove();

            const modalHtml = `
                <div id="${modalId}" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; justify-content: center; align-items: center;">
                    <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                        <h2 style="margin-top: 0; color: #d63638;">⚠️ ${TRANSLATION.warning || "Warning"
                }</h2>
                        <p style="font-size: 16px; line-height: 1.5; margin: 20px 0;">
                            ${TRANSLATION.reset_warning ||
                "Changing the Embeddings Model or Vector DB will delete all existing trained data. You will need to retrain all products, posts, and pages from scratch."
                }
                        </p>
                        <p style="font-weight: bold;">Do you want to proceed?</p>
                        <div style="margin-top: 25px; display: flex; justify-content: center; gap: 15px;">
                            <button id="cptbot-reset-confirm-btn" class="button button-primary button-large" style="background-color: #d63638; border-color: #d63638;">${TRANSLATION.yes_delete ||
                "Yes, Delete & Proceed"
                }</button>
                            <button id="cptbot-reset-cancel-btn" class="button button-large">${TRANSLATION.cancel || "Cancel"
                }</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML("beforeend", modalHtml);

            const modal = document.getElementById(modalId);
            const confirmBtn = document.getElementById(
                "cptbot-reset-confirm-btn",
            );
            const cancelBtn = document.getElementById(
                "cptbot-reset-cancel-btn",
            );

            // Handle Cancel
            cancelBtn.addEventListener("click", () => {
                // Revert value
                target.value = previousValue;
                modal.remove();

                // Dispatch change event to ensure any other listeners (like UI toggles) update correctly
                // We pass skipCheck to avoid infinite loop
                const event = new CustomEvent("change", {
                    detail: { skipCheck: true },
                });
                target.dispatchEvent(event);
            });

            // Handle Confirm
            confirmBtn.addEventListener("click", () => {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML =
                    TRANSLATION.processing || "Processing...";

                cptbot.ajax({
                    type: "POST",
                    url: wc_cptbot.admin_ajax,
                    data: {
                        action: "wc_cptbot_reset_training_data",
                        nonce: wc_cptbot.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            // Update local state
                            wc_cptbot.trained_count = 0;
                            wc_cptbot.posts_pages_trained_count = 0;
                            wc_cptbot.has_trained_data = false;
                            target.dataset.previousValue = newValue;

                            // Clear checkpoints
                            if (window.clearOnnxCheckpoints) {
                                window.clearOnnxCheckpoints();
                            }

                            // Update UI if elements exist (e.g. if we are on training page, though unlikely)
                            const trainedEl = document.querySelector(
                                ".wc_cptbot_trained_products",
                            );
                            if (trainedEl) trainedEl.innerHTML = "0";

                            const progressBar = document.querySelector(
                                "#cptbot_products_progressbar",
                            );
                            if (progressBar) {
                                progressBar.value = 0;
                                progressBar.innerHTML = "0%";
                            }

                            modal.remove();

                            showAdminNotice(
                                response.data?.status || "Training data reset successfully.",
                                "success",
                            );
                        } else {
                            showAdminNotice(
                                response.data?.status || "Error resetting data",
                                "error",
                            );
                            // Revert on error
                            target.value = previousValue;
                            modal.remove();
                            const event = new CustomEvent("change", {
                                detail: { skipCheck: true },
                            });
                            target.dispatchEvent(event);
                        }
                    },
                    error: function () {
                        showAdminNotice("Server error occurred while resetting data.", "error");
                        target.value = previousValue;
                        modal.remove();
                        const event = new CustomEvent("change", {
                            detail: { skipCheck: true },
                        });
                        target.dispatchEvent(event);
                    },
                });
            });
        } else {
            // No trained data, just update previous value
            target.dataset.previousValue = newValue;
        }
    }

    if (modelTypeSelect) {
        modelTypeSelect.addEventListener("change", handleTrainingReset);
    }

    if (vectorDbSelect) {
        vectorDbSelect.addEventListener("change", handleTrainingReset);
    }

    // Dismiss custom admin notices
    document.addEventListener("click", function (e) {
        const dismissBtn = e.target.closest(".wc-cptbot-notice-dismiss, .wc-cptbot-plugin-adds-wrap .notice-dismiss");
        if (dismissBtn) {
            const notice = dismissBtn.closest(".wc-cptbot-notice, .wc-cptbot-plugin-adds-wrap");
            if (notice) {
                notice.remove();
            }
        }
    });
});
