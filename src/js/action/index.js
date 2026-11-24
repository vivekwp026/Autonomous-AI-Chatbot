import { createEmbedding, createTernlightEmbedding } from "./../embedding/index.js";

const TRANSLATION = wc_cptbot.translation;

export function getLastSevenDays(days) {
    const today = new Date();
    const lastSevenDays = [];

    for (let i = days; i >= 0; i--) {
        const day = new Date(today);
        day.setDate(today.getDate() - i);
        lastSevenDays.push(day);
    }

    return lastSevenDays;
}

export const GetLastSevenDaysChat = (data) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_get_few_days_chat",
            nonce: wc_cptbot.nonce,
            days: data.days,
            user_type: data.user_type,
        };

        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const getTodaysusers = (user_type) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_get_today_user",
            nonce: wc_cptbot.nonce,
            user_type: user_type,
        };

        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const get_user_by_user_id = (user) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_get_user_by_id",
            nonce: wc_cptbot.nonce,
            user_id: user.user_id,
        };

        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const gettopsearchedproducts = () => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_get_most_searched_products",
            nonce: wc_cptbot.nonce,
        };

        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const getDashboardKPIs = (days = 7) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_get_dashboard_kpis",
            nonce: wc_cptbot.nonce,
            days: days,
        };

        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => response.json())
            .then((res) => {
                if (res && res.success && res.data) {
                    resolve(res.data);
                } else {
                    resolve([]);
                }
            })
            .catch(() => {
                resolve([]);
            });
    });
};

export const block_current_user = (user_id) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_block_user",
            nonce: wc_cptbot.nonce,
            user_id: user_id,
        };
        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const create_custom_data = (postdata) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_create_custom_data",
            nonce: wc_cptbot.nonce,
        };
        body = { ...body, ...postdata };
        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const speech_to_openai = (postdata) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_speech_to_openai",
            nonce: wc_cptbot.nonce,
        };
        body = { ...body, ...postdata };
        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const save_content_openai_setting = (formdata) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_save_content_openai_setting",
            nonce: wc_cptbot.nonce,
        };
        body = { ...body, ...formdata };
        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((data) => {
                resolve(data);
            });
    });
};

export const create_new_post = (data, posttype) => {
    return new Promise((resolve) => {
        let body = {
            action: "wc_cptbot_create_new_post",
            nonce: wc_cptbot.nonce,
            data: JSON.stringify(data),
            posttype: posttype,
        };
        fetch(wc_cptbot.admin_ajax, {
            method: "post",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "Cache-Control": "no-cache",
            },
            body: new URLSearchParams(body),
        })
            .then((response) => {
                return response.json();
            })
            .then((resp) => {
                resolve(resp);
            });
    });
};

export const logToServer = (data, type = "info") => {
    if (!wc_cptbot.admin_ajax || !wc_cptbot.nonce) return;

    const body = {
        action: "wc_cptbot_log_js_data",
        nonce: wc_cptbot.nonce,
        log_data: typeof data === "object" ? JSON.stringify(data) : data,
        log_type: type,
    };

    fetch(wc_cptbot.admin_ajax, {
        method: "POST",
        credentials: "same-origin",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(body),
    }).catch((err) => console.error("Failed to log to server:", err));
};

export function loadModel(key, modelPath) {
    let chatbotBarEle = document.querySelector(
        "#wp-admin-bar-wc_chatgpt_chatbot_bar .ab-item",
    );

    try {
        if (
            !!chatbotBarEle &&
            "onnx" === wc_cptbot?.modal_type &&
            !!wc_cptbot?.is_model_load
        ) {
            chatbotBarEle.innerHTML = `<span class="wc-cptbot-onnx-loading-text"><span class="wc-cptbot-onnx-loading-icon">♻️ </span> ${TRANSLATION.model_loading}</span>`;
            chatbotBarEle.style.background = "none";

            let modelWorker = new Worker(wc_cptbot.model_worker_url);

            modelWorker.postMessage([key, modelPath]);
            modelWorker.onmessage = (e) => {
                if (e.data && e.data.status === "done") {
                    chatbotBarEle.style.background = "green";
                    chatbotBarEle.innerHTML = `<span style="color:#fff;font-weight:bold;">${TRANSLATION.model_ready}</span>`;

                    const event = new CustomEvent("onOnnxModelLoad", {
                        detail: { session: e.data.buffer },
                    });

                    document.dispatchEvent(event);
                } else {
                    console.log(
                        `Message from worker: ${e?.data?.status || "unknown"}`,
                    );
                }
            };

            modelWorker.onerror = (err) => {
                console.error("Worker error: ", err);
                chatbotBarEle.style.background = "red";
                chatbotBarEle.innerHTML = `<span style="color:#fff;font-weight:bold;">${TRANSLATION.model_error}</span>`;
                setTimeout(() => {
                    loadModel(key, modelPath);
                }, 2000);
            };
        }
    } catch (error) {
        if (chatbotBarEle) {
            chatbotBarEle.style.background = "red";
            chatbotBarEle.innerHTML = `<span style="color:#fff;font-weight:bold;">${TRANSLATION.model_error}</span>`;
        }
        console.error("Model load error:", error);
    }
}

export const upsertData = async (
    data,
    ajax,
    session,
    actionId = 0,
    offset = 0,
) => {
    if (!!Object.keys(data)?.length) {
        try {
            // Extract the 'update' flag from data if it exists
            const isUpdate = data.update === true;

            // Remove 'update' from data so it's not treated as a product
            const productData = { ...data };
            delete productData.update;

            // Retrieve last processed product from localStorage
            const lastProcessedKey = `onnx_sync_checkpoint_${actionId}`;
            const lastProcessedData = localStorage.getItem(lastProcessedKey);
            let startIndex = 0;

            if (lastProcessedData) {
                const checkpoint = JSON.parse(lastProcessedData);
                startIndex = checkpoint.lastIndex || 0;
            }

            let sess = session; // Use the session passed as parameter
            if (
                session &&
                (typeof session === "string" ||
                    session instanceof ArrayBuffer) &&
                !!ort
            ) {
                ort.env.wasm.wasmPaths =
                    "https://cdn.jsdelivr.net/npm/onnxruntime-web/dist/";
                sess = await ort.InferenceSession.create(session, {
                    executionProviders: ["wasm"],
                });
            }

            let productDataEntries = Object.entries(productData);
            let responseData = {};
            const getMemoryInfo = () => {
                if (performance.memory) {
                    return {
                        usedJSHeapSize: Math.round(
                            performance.memory.usedJSHeapSize / 1048576,
                        ),
                        totalJSHeapSize: Math.round(
                            performance.memory.totalJSHeapSize / 1048576,
                        ),
                        jsHeapSizeLimit: Math.round(
                            performance.memory.jsHeapSizeLimit / 1048576,
                        ),
                    };
                }
                return null;
            };

            for (let i = startIndex; i < productDataEntries.length; i++) {
                let productId = productDataEntries[i][0];
                let productDataString = JSON.stringify(productDataEntries[i][1]);

                const checkpointData = {
                    lastIndex: i,
                    lastOffset: offset,
                    lastProductId: productId,
                    status: "processing",
                    timestamp: new Date().toISOString(),
                };
                const checkpointJSON = JSON.stringify(checkpointData);
                localStorage.setItem(lastProcessedKey, checkpointJSON);
                sessionStorage.setItem(lastProcessedKey, checkpointJSON);

                const memBefore = getMemoryInfo();
                if (memBefore) {
                    console.log(
                        `%c💾 Memory BEFORE: ${memBefore.usedJSHeapSize}MB used / ${memBefore.totalJSHeapSize}MB total (limit: ${memBefore.jsHeapSizeLimit}MB)`,
                        "color: #0066ff; font-weight: bold",
                    );
                }

                try {
                    const modalType = (typeof wc_cptbot !== 'undefined' ? wc_cptbot.modal_type : null) || 
                                      (typeof wc_cptbotObject !== 'undefined' ? wc_cptbotObject.modal_type : 'onnx');

                    if (modalType === "onnx" && (!sess || (typeof sess === "object" && sess._dispose))) {
                        throw new Error(
                            "ONNX Session is no longer valid or has been disposed. Reloading model...",
                        );
                    }
                    try {
                        const parsedData = JSON.parse(productDataString);
                        // Check for variation data
                        if (parsedData.Type === "variable") {
                            const variationKeys = Object.keys(
                                parsedData,
                            ).filter(
                                (key) =>
                                    key.toLowerCase().includes("variant") ||
                                    key.toLowerCase().includes("edition") ||
                                    key.toLowerCase().includes("format"),
                            );

                            if (variationKeys.length > 0) {
                                variationKeys.forEach((key) => { });
                            }
                        }
                    } catch (parseError) {
                        console.log(
                            "Raw Product Data String (first 500 chars):",
                            productDataString.substring(0, 500),
                        );
                    }

                    let embeddingData;
                    if (modalType === "ternlight") {
                        embeddingData = await createTernlightEmbedding(productDataString);
                    } else {
                        embeddingData = await createEmbedding(
                            productDataString,
                            sess,
                            wc_cptbot.onnx_modal_dir,
                        );
                    }
                    if (!!embeddingData) {
                        let embeddingArray = Array.from(embeddingData);
                        const jsonString = JSON.stringify(embeddingArray);

                        const requestData = {
                            action: "wc_cptbot_upsert_data_in_pinecone",
                            nonce: wc_cptbot.nonce,
                            product_id: productId,
                            action_id: !!actionId ? actionId : 0,
                            embedding: jsonString,
                            data: JSON.stringify({ update: isUpdate }),
                            force: 1,
                        };

                        console.log(`🔍 Sending upsert request for Product ID ${productId}:`, {
                            isUpdate: isUpdate,
                            dataBeingSent: JSON.stringify({ update: isUpdate })
                        });

                        let result = await ajax({
                            type: "POST",
                            url: wc_cptbot.admin_ajax,
                            data: requestData,
                        });

                        if (
                            result?.success ||
                            result?.message?.includes("already exists") ||
                            result?.message?.includes("already trained")
                        ) {
                            responseData = result?.success
                                ? result
                                : {
                                    ...result,
                                    success: true,
                                    products_trained:
                                        (responseData?.products_trained ||
                                            0) + 1,
                                };

                            const successCheckpoint = JSON.stringify({
                                lastIndex: i + 1,
                                lastOffset: offset,
                                lastProductId: productId,
                                status: "completed",
                                timestamp: new Date().toISOString(),
                            });
                            localStorage.setItem(
                                lastProcessedKey,
                                successCheckpoint,
                            );
                            sessionStorage.setItem(
                                lastProcessedKey,
                                successCheckpoint,
                            );
                        } else {
                            const errorCheckpoint = JSON.stringify({
                                lastIndex: i,
                                lastProductId: productId,
                                error:
                                    result?.message || "Unknown upsert failure",
                                timestamp: new Date().toISOString(),
                            });
                            localStorage.setItem(
                                lastProcessedKey,
                                errorCheckpoint,
                            );
                            sessionStorage.setItem(
                                lastProcessedKey,
                                errorCheckpoint,
                            );

                            return {
                                success: false,
                                error: result?.message || "Upsert failed",
                                checkpoint: true,
                                lastIndex: i,
                                lastOffset: offset,
                                lastProductId: productId,
                            };
                        }
                    }
                } catch (embeddingError) {
                    console.log("Error:", embeddingError.message);

                    const errorCheckpoint = JSON.stringify({
                        lastIndex: i,
                        lastOffset: offset,
                        lastProductId: productId,
                        error: embeddingError.message,
                        timestamp: new Date().toISOString(),
                    });
                    localStorage.setItem(lastProcessedKey, errorCheckpoint);
                    sessionStorage.setItem(lastProcessedKey, errorCheckpoint);

                    // Return error with checkpoint info for page reload
                    return {
                        success: false,
                        error: embeddingError.message,
                        checkpoint: true,
                        lastIndex: i,
                        lastOffset: offset,
                        lastProductId: productId,
                    };
                }

                if (i < productDataEntries.length - 1) {
                    let delayMs = 500;

                    if ((i + 1) % 5 === 0) {
                        delayMs = 2500;
                    } else if ((i + 1) % 2 === 0) {
                        delayMs = 800;
                    }

                    await new Promise((resolve) =>
                        setTimeout(resolve, delayMs),
                    );
                }
            }

            localStorage.removeItem(lastProcessedKey);

            return responseData;
        } catch (error) {
            return {
                success: false,
                error: error.message,
                checkpoint: true,
                lastIndex: startIndex,
                lastOffset: offset,
                lastProductId: "unknown",
            };
        }
    }
};

export const upsertPostPageData = async (
    postId,
    text,
    ajax,
    session,
    force = true,
) => {
    if (!postId || !text) {
        return { success: false, error: "Missing post ID or content" };
    }

    try {
        let sess = session;
        if (
            session &&
            (typeof session === "string" || session instanceof ArrayBuffer) &&
            typeof ort !== "undefined"
        ) {
            ort.env.wasm.wasmPaths =
                "https://cdn.jsdelivr.net/npm/onnxruntime-web/dist/";
            sess = await ort.InferenceSession.create(session, {
                executionProviders: ["wasm"],
            });
        }

        const modalType = (typeof wc_cptbot !== "undefined" && wc_cptbot.modal_type) ? wc_cptbot.modal_type : "onnx";
        let embeddingData;
        if (modalType === "ternlight") {
            embeddingData = await createTernlightEmbedding(text);
        } else {
            const onnxDir = (typeof wc_cptbot !== "undefined") ? wc_cptbot.onnx_modal_dir : "";
            embeddingData = await createEmbedding(
                text,
                sess,
                onnxDir,
            );
        }

        if (!embeddingData) {
            return { success: false, error: "Failed to generate embedding" };
        }

        let embeddingArray = Array.from(embeddingData);
        const jsonString = JSON.stringify(embeddingArray);

        const requestData = {
            action: "wc_cptbot_upsert_post_page_vector",
            nonce: (typeof wc_cptbot !== "undefined") ? wc_cptbot.nonce : "",
            post_id: postId,
            embedding: jsonString,
            force: force ? 1 : 0,
        };

        const adminAjax = (typeof wc_cptbot !== "undefined") ? wc_cptbot.admin_ajax : ajaxUrl;

        const result = await ajax({
            type: "POST",
            url: adminAjax,
            data: requestData,
        });

        return result;
    } catch (err) {
        console.error("❌ ONNX Post/Page sync error:", err);
        return { success: false, error: err.message || "Failed to sync" };
    }
};

