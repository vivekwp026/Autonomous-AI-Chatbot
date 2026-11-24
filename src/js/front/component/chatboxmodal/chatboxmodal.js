import { Fragment, useEffect, useRef, useState, useMemo } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { Sendbtn, CloseIcon, Mic } from "./../../action/icons";
import { ChatbotRequest } from "./../../action";
import Loader from "./../../action/loader.js";
import { createEmbedding, createTernlightEmbedding } from '../../../embedding/index.js';
import { processMessage } from '../../utils/messageFormatter.js';

const TRANSLATION = (typeof wc_cptbotObject !== 'undefined' && wc_cptbotObject.translation) ? wc_cptbotObject.translation : {};
const chatbot_name = wc_cptbotObject.chatbot_name;
const avatar = wc_cptbotObject.avatar_url;
const chatgpt_img = wc_cptbotObject.chatgpt_img;
const AiLogo = wc_cptbotObject.ai_logo;
const BackgroundImg = (wc_cptbotObject.cpt_bot_allow_bg_image && !!wc_cptbotObject.chat_bg_img) ? { background: "url(" + wc_cptbotObject.chat_bg_img + ")" } : {};

const animation = wc_cptbotObject.animation;
const voice_assistance = wc_cptbotObject.voice_assistance;
const position = (wc_cptbotObject.cpt_bot_position !== "right") ? "cptbot-chatbox-left" : '';

function AIMessage({ data, AiLogo, SecondaryColor, color }) {
    const processedMsg = useMemo(() => {
        if (data.msg && data.msg !== 0) {
            try {
                return processMessage(data.msg);
            } catch (err) {
                console.error('Error processing message:', err);
                return data.msg;
            }
        }
        return TRANSLATION.false_res;
    }, [data.msg]);

    return (
        <div className="cptbot-chatbox cptbot-chatbox-ai-msg">
            <div className="cptbot-chatbox-img"><img src={AiLogo} alt="AI" /></div>
            <div
                className="cptbot-chatbox-msg"
                style={{ background: SecondaryColor, color: color }}
                dangerouslySetInnerHTML={{ __html: processedMsg || '' }}
            >
            </div>
            <div className="cptbot-chatbox-blank"></div>
        </div>
    );
}

function ChatBoxModal({ setopen, color, open, SecondaryColor, chat, chatMboxClass, setchatMboxClass }) {

    const [hover, sethover] = useState(false);
    const [msg, setmsg] = useState();
    const [loader, setloader] = useState(false);
    const [voicerecognize, setvoicerecognize] = useState(false);
    const [session, setWorkerResult] = useState(null);
    const [loadingText, setLoadingText] = useState('Loading');
    const activeSessions = useRef(new Set());

    const textbox = useRef();
    const chatContentRef = useRef();
    const isOnline = !!wc_cptbotObject.is_online ? "online" : "offline";

    const parseString = (string) => {
        const parser = new DOMParser();
        return parser.parseFromString(string, 'text/html').body.textContent;
    }

    const logIntentDebug = (response) => {
        const intentDebug = response?.intent_debug;

        if (!intentDebug) {
            console.log('[WC CPTBot Intent] source: unknown');
            return;
        }

        console.log(
            `[WC CPTBot Intent] source: ${intentDebug.source}, intent: ${intentDebug.intent}, using_jev: ${intentDebug.using_jev}`,
            intentDebug
        );
    }

    useEffect(() => {
        if (wc_cptbotObject.modal_type === 'onnx') {

            let modelWorker = new Worker(wc_cptbotObject.model_worker_url + '?v=' + new Date().getTime());
            modelWorker.postMessage(['e5-small', wc_cptbotObject.onnx_e5small_modal]);

            modelWorker.onmessage = (e) => {
                const initSession = (buffer) => {
                    ort.env.wasm.wasmPaths = "https://cdn.jsdelivr.net/npm/onnxruntime-web@1.23.2/dist/";
                    ort.env.logLevel = 'error';
                    ort.InferenceSession.create(buffer, {
                        executionProviders: ["wasm"],
                    }).then(res => {
                        setWorkerResult(res);
                    });
                };

                if (e.data && e.data.status) {
                    if (e.data.status === 'downloading') {
                        setLoadingText('Downloading Model');
                    } else if (e.data.status === 'configuring') {
                        setLoadingText('Configuring AI Model');
                    } else if (e.data.status === 'done') {
                        initSession(e.data.buffer);
                    }
                } else {
                    // Fallback for cached worker (sending raw buffer)
                    initSession(e.data);
                }
            };

            modelWorker.onerror = (err) => {
                console.error("Worker error: ", err);
            };

            return () => modelWorker.terminate();
        }
    }, []);

    useEffect(() => {
        if (chat.chat.length === 0) {
            setTimeout(() => {
                let a = {
                    "id": `ai-${Date.now()}`,
                    "from": "AI",
                    "msg": TRANSLATION.welcome_msg
                }
                chat.setchat(prev => [...prev, a])
            }, 1200);
        }
        textbox.current.focus();
    }, [open]);

    useEffect(() => {
        if (!!chat.chat.length) {
            let lastmsg = chat.chat.at(-1);
            let from = lastmsg.from;

            if (from === 'User') {
                let count = chat.chat.filter(data => data.from === "User");
                let queryText = !!msg ? msg : textbox.current.value;

                let body = {
                    query: queryText,
                    action: "wc_cptbot_get_client_query_prompt",
                    count: count.length - 1,
                    history: chat.chat.length > 2 ? 1 : 0,
                    history_content: JSON.stringify(chat.chat),
                };

                if (wc_cptbotObject.modal_type) {
                    ChatbotRequest("post", body).then(response => {
                        logIntentDebug(response);

                        // Check if agent returned direct response (categories already fetched)
                        if (response?.is_direct_response === true && response?.data) {
                            console.log('Agent Response Directly');

                            let a = {
                                "id": `ai-${Date.now()}`,
                                "from": "AI",
                                "msg": response.data,
                            }
                            chat.setchat(prev => [...prev, a]);
                            setmsg('');
                            setloader(false);
                            return;
                        }

                        // Handle is_direct_response even if data is empty (fallback)
                        if (response?.is_direct_response === true && response?.success) {
                            console.log('Agent Response Directly (with fallback)');

                            let a = {
                                "id": `ai-${Date.now()}`,
                                "from": "AI",
                                "msg": response.data || response.message || TRANSLATION.false_res,
                            }
                            chat.setchat(prev => [...prev, a]);
                            setmsg('');
                            setloader(false);
                            return;
                        }

                        // Check if this is a category list query - skip embedding
                        if (response?.is_category_query === true) {
                            console.log('Category list query detected - skipping embedding');

                            let data = {
                                action: 'wc_cptbot_client_query_for_onnx',
                                history_content: JSON.stringify(chat.chat),
                                embedding_data: JSON.stringify([]), // Empty embedding
                                query: queryText,
                                message: queryText, // Also send as message
                                price_filter: null,
                            }

                            ChatbotRequest("post", data).then(chatResponse => {
                                let a = {
                                    "id": `ai-${Date.now()}`,
                                    "from": "AI",
                                    "msg": chatResponse,
                                }
                                chat.setchat(prev => [...prev, a]);
                                setmsg('');
                                setloader(false);
                            }).catch(err => {
                                console.error('Error getting categories:', err);
                                setloader(false);
                            });
                            return;
                        }

                        if (!!response?.success && response?.data) {
                            let rag_required = response?.rag_required !== undefined ? response.rag_required : true;

                            const isTernlight = wc_cptbotObject.modal_type === "ternlight";
                            if (rag_required && (isTernlight || !!session)) {
                                let prompt = JSON.stringify(response.data);

                                let embedPromise;
                                if (isTernlight) {
                                    embedPromise = createTernlightEmbedding(prompt);
                                } else {
                                    embedPromise = createEmbedding(prompt, session, wc_cptbotObject.onnx_modal_dir);
                                }

                                embedPromise.then((res) => {
                                    if (!!res) {
                                        let embeddingData = res;

                                        let data = {
                                            action: 'wc_cptbot_client_query_for_onnx',
                                            history_content: JSON.stringify(chat.chat),
                                            embedding_data: JSON.stringify(embeddingData),
                                            query: queryText,
                                            chromadb_where: JSON.stringify(response?.chromadb_where) || null,
                                            rag_required: true
                                        }

                                        ChatbotRequest("post", data).then(chatResponse => {
                                            let a = {
                                                "id": `ai-${Date.now()}`,
                                                "from": "AI",
                                                "msg": chatResponse,
                                            }
                                            chat.setchat(prev => [...prev, a]);
                                            setmsg('');
                                            setloader(false);
                                        }).catch(err => {
                                            console.error('Error in ChromaDB query:', err);
                                            setloader(false);
                                        });
                                    } else {
                                        console.error('Embedding creation failed');
                                        setloader(false);
                                    }
                                }).catch(err => {
                                    console.error('Error creating embedding:', err);
                                    setloader(false);
                                });
                            } else if (!rag_required) {
                                // Skip embedding for conversational queries
                                console.log('RAG not required - skipping embedding');
                                let data = {
                                    action: 'wc_cptbot_client_query_for_onnx',
                                    history_content: JSON.stringify(chat.chat),
                                    embedding_data: JSON.stringify([]), // Empty embedding
                                    query: queryText,
                                    price_filter: response?.price_filter ? JSON.stringify(response.price_filter) : null,
                                    needs_history: response?.needs_history !== undefined ? response.needs_history : true,
                                    rag_required: false
                                }

                                ChatbotRequest("post", data).then(chatResponse => {
                                    let a = {
                                        "id": `ai-${Date.now()}`,
                                        "from": "AI",
                                        "msg": chatResponse,
                                    }
                                    chat.setchat(prev => [...prev, a]);
                                    setmsg('');
                                    setloader(false);
                                }).catch(err => {
                                    console.error('Error in ChromaDB query (no embedding):', err);
                                    setloader(false);
                                });
                            } else {
                                console.error('Prompt response invalid or Session not ready:', response);
                                setloader(false);
                            }
                        } else {
                            console.error('Prompt response invalid:', response);
                            setloader(false);
                        }
                    }).catch(err => {
                        console.error('Error getting prompt:', err);
                        setloader(false);
                    });
                } else {
                    ChatbotRequest("post", body).then(data => {
                        let a = {
                            "id": `ai-${Date.now()}`,
                            "from": "AI",
                            "msg": data,
                        }
                        chat.setchat(prev => [...prev, a]);
                        setmsg('');
                        setloader(false);
                    }).catch(err => {
                        console.error('Error in query:', err);
                        setloader(false);
                    });
                }
            } else if (from === 'AI' && lastmsg.msg && lastmsg.msg.includes('cptbot-stripe-poll-data')) {
                // Stripe Payment detected
                const parser = new DOMParser();
                const doc = parser.parseFromString(lastmsg.msg, 'text/html');
                const pollData = doc.querySelector('.cptbot-stripe-poll-data');

                if (pollData) {
                    const sessionId = pollData.getAttribute('data-stripe-session-id');
                    const orderId = pollData.getAttribute('data-stripe-order-id');

                    if (sessionId && orderId && !activeSessions.current.has(sessionId)) {
                        activeSessions.current.add(sessionId);

                        const pollInterval = setInterval(() => {
                            ChatbotRequest("post", {
                                action: 'wc_cpbot_verify_stripe_payment',
                                session_id: sessionId,
                                order_id: orderId,
                            }).then(verification => {
                                if (verification.success) {
                                    if (verification.paid === true || verification.pending === false) {
                                        // ✅ Successful or ❌ Failed (Both stop polling)
                                        clearInterval(pollInterval);
                                        activeSessions.current.delete(sessionId);

                                        chat.setchat(prevChat => {
                                            const updatedChat = [...prevChat];
                                            let msgIndex = -1;
                                            // Find the specific message that contains this session ID (loop backwards for most recent)
                                            for (let i = updatedChat.length - 1; i >= 0; i--) {
                                                if (updatedChat[i].msg && typeof updatedChat[i].msg === 'string' && updatedChat[i].msg.includes(`data-stripe-session-id='${sessionId}'`)) {
                                                    msgIndex = i;
                                                    break;
                                                }
                                            }

                                            if (msgIndex !== -1) {
                                                updatedChat[msgIndex].msg = verification.message;
                                            }
                                            return updatedChat;
                                        });
                                    }
                                }
                            }).catch(err => {
                                console.error('Error verifying Stripe payment:', err);
                            });
                        }, 5000);

                        // Stop polling after 10 minutes
                        setTimeout(() => {
                            clearInterval(pollInterval);
                            activeSessions.current.delete(sessionId);
                        }, 600000);
                    }
                }
            }
        }

        // Add handler to open Stripe checkout in a popup (mini-modal)
        const handleStripeClick = (e) => {
            const button = e.target.closest('.cptbot-stripe-pay-button');
            if (button) {
                e.preventDefault();
                const url = button.href;
                const width = 500;
                const height = 700;
                const left = (window.innerWidth / 2) - (width / 2);
                const top = (window.innerHeight / 2) - (height / 2);

                window.open(
                    url,
                    'StripePayment',
                    `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`
                );
            }
        };

        const currentChatContent = chatContentRef.current;
        if (currentChatContent) {
            currentChatContent.addEventListener('click', handleStripeClick);
        }

        setTimeout(() => {
            let allChat = document.querySelectorAll('.cptbot-chatbox');
            let lastindex = allChat.length - 1;
            let lastchat = allChat.length > 1 ? allChat[lastindex] : allChat[0];
            if (!!lastchat) {
                lastchat.scrollTop = lastchat.scrollHeight;
                lastchat.scrollIntoView({ block: 'end', behavior: 'smooth' });
                textbox.current.value = '';
            }
        }, 10);

        return () => {
            if (currentChatContent) {
                currentChatContent.removeEventListener('click', handleStripeClick);
            }
        }
    }, [chat.chat]);

    function togglehover() {
        sethover(!hover);
    }

    function submitForm(e) {
        e.preventDefault();
        let keys = ['', undefined, null];
        if (loader === false && msg && !keys.includes(msg.trim())) {
            setloader(true);
            let a = {
                "id": `user-${Date.now()}`,
                "from": "User",
                "msg": msg
            }
            chat.setchat(prev => [...prev, a]);
        }
    }

    function listenspeech() {
        if ('webkitSpeechRecognition' in window) {
            const recognition = new webkitSpeechRecognition();

            recognition.continuous = true;
            recognition.interimResults = true;

            recognition.onstart = function () {
                setvoicerecognize(true);
            };

            recognition.onend = function (e) {
                setvoicerecognize(false);
                if (textbox.current.value !== '') {
                    setTimeout(() => {
                        let a = {
                            "id": `user-${Date.now()}`,
                            "from": "User",
                            "msg": textbox.current.value
                        }
                        chat.setchat(prev => [...prev, a]);
                    }, 100);
                }
            };

            recognition.onresult = function (event) {
                const result = event.results[event.results.length - 1];
                const transcript = result[0].transcript;
                textbox.current.value = transcript;
            };

            recognition.start();
        } else {
            console.warn(TRANSLATION.speech_not_support);
        }
    }

    function close() {
        if (!!animation) {
            if (wc_cptbotObject.cpt_bot_position === "right") {
                setchatMboxClass("cptbot-chatbox-modal wc-cptbot-top-to-bottom-right " + position);
            } else {
                setchatMboxClass("cptbot-chatbox-modal wc-cptbot-top-to-bottom-left " + position);
            }
            setTimeout(() => {
                setopen(false)
            }, 300)
        } else {
            setchatMboxClass("cptbot-chatbox-modal " + position);
            setopen(false)
        }
    }
    let onlinecolor = isOnline == "online" ? "green" : "red";
    return <Fragment>
        <form onSubmit={submitForm}>
            <div className={chatMboxClass} style={BackgroundImg}>
                <div className="cptbot-chatbox-header" style={{ backgroundColor: color }}>
                    <div className="cptbot-avatar"><img src={chatgpt_img} /></div>
                    <div className="cptbot-title" style={{ color: SecondaryColor }} title={chatbot_name}>
                        <span className="cptbot-name" style={{ color: SecondaryColor }}>{chatbot_name}</span>
                        <small className="cptbot-status">
                            <span className="cptbot-status-color" style={{ backgroundColor: onlinecolor }}></span>
                            <span>{isOnline}</span>
                        </small>
                    </div>
                    <div className="cptbot-cancel" onClick={() => close()}>
                        <div className="cptbot-cancel-ui">
                            <CloseIcon color={SecondaryColor} />
                        </div>
                    </div>
                </div>
                <div className="cptbot-chatbox-body" style={{ position: 'relative' }}>
                    {
                        (wc_cptbotObject.modal_type === 'onnx' && !session) &&
                        <div className="cptbot-configuring-overlay" style={{
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            right: 0,
                            bottom: 0,
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'center',
                            alignItems: 'center',
                            zIndex: 9999
                        }}>
                            <Loader color={color} type="gemini" />
                            <div className="cptbot-loading-text">
                                {loadingText}
                            </div>
                            <div className="cptbot-loading-subtext">
                                Initializing ...
                            </div>
                        </div>
                    }
                    <div className="cptbot-chatbox-content" ref={chatContentRef}>
                        {
                            chat.chat.length > 0 ? chat.chat.map((data, key) => {
                                const stableKey = data.id || `msg-${key}`;
                                return (data.from === "AI") ?
                                    <AIMessage key={stableKey} data={data} AiLogo={AiLogo} SecondaryColor={SecondaryColor} color={color} /> :
                                    <div key={stableKey} className="cptbot-chatbox cptbot-chatbox-user-msg">
                                        <div className="cptbot-chatbox-blank"></div>
                                        <div
                                            className="cptbot-chatbox-msg"
                                            style={{ background: color, color: SecondaryColor }}
                                            dangerouslySetInnerHTML={{ __html: !!data?.msg ? data.msg : '' }}
                                        >
                                        </div>
                                        <div className="cptbot-chatbox-img"><img title={wc_cptbotObject.user.data.user_nicename} src={avatar} alt="User" /></div>
                                    </div>
                            }) : ""
                        }
                    </div>
                    <div className="cptbot-chatbox-footer">
                        <div className="cptbot-chatbox-footer-data">
                            <div className="cptbot-chatbox-input" style={{ backgroundColor: color }}>
                                <input type="text" style={{ border: "1px solid" + color, backgroundColor: color, color: SecondaryColor }} ref={textbox} autoComplete="off" onChange={(e) => setmsg(parseString(e.target.value))} id="cptbot-chatbox-input-msg" placeholder={TRANSLATION.wnat_to_know} />
                                <div className="cptbot-action-btns">
                                    {
                                        voice_assistance !== '' && <span style={{ backgroundColor: color }} onClick={listenspeech}> {voicerecognize ? <Loader color={SecondaryColor} /> : <Mic color={SecondaryColor}></Mic>}
                                        </span>
                                    }

                                    {
                                        !loader ?
                                            <button type="submit" onMouseEnter={togglehover} onMouseLeave={togglehover} className="cptbot-button" style={{
                                                backgroundColor: color
                                            }}><Sendbtn color={SecondaryColor} /></button> : <button type="button" disabled className="cptbot-button"><div className="cptbot-loader" style={{ backgroundColor: color }}></div></button>
                                    }

                                </div>
                                <style>
                                    {`
                                            .cptbot-chatbox-input input::placeholder {
                                                color: ${SecondaryColor};
                                                opacity: 0.5
                                            }
                                            `
                                    }
                                </style>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </Fragment>;
}

export default ChatBoxModal;
