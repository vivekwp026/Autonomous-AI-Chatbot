import { Fragment, useEffect, useState, useRef } from "react";
import ReactHtmlParser from "html-react-parser";
import { Searchico, Downloadico, Userblock } from "./../../front/action/icons";
import { get_user_by_user_id, block_current_user } from "./../../action";
import { processMessage } from "./../../front/utils/messageFormatter";

const TRANSLATION = wc_cptbot.translation;
const color = wc_cptbot.primary_color;
const avatar = wc_cptbot.avatar_url;
const AiLogo = wc_cptbot.ai_logo;
const not_found = wc_cptbot.not_found;
const chatbot_name = wc_cptbot.chatbot_name;
const admin_background = wc_cptbot.admin_background;

function History() {

    const [today, settoday] = useState("");
    const [chat, setchat] = useState([]);
    const [chatfetch, setchatfetch] = useState(false);
    const [userblocked, setuserblocked] = useState(false);

    const dateTo = useRef();
    const dateFrom = useRef();
    const userselect = useRef();

    useEffect(() => {
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        jQuery('#cptbot_users_list').select2({
            ajax: {
                method: "POST",
                url: wc_cptbot.admin_ajax, // AJAX URL is predefined in WordPress admin
                dataType: "json",
                delay: 250, // delay in ms while typing when to perform a AJAX search
                data: function (params) {
                    return {
                        query: params.term, // search query
                        action: "wc_cptbot_get_users_list",
                        security: wc_cptbot.nonce,
                    };
                },
                processResults: function (response) {
                    var options = [];
                    if (response != null) {
                        response.forEach((res) => {
                            options.push({
                                id: res.ID,
                                text: res.user_nicename,
                                class: res.exist ? 'custom-option-class' : ''
                            });
                        });
                    }
                    return {
                        results: options,
                    };
                },
                cache: true,
            },
            templateResult: function (data) {
                document.querySelector('.wc-cptbot-select-filter').style.border = "none";
                return jQuery('<span class="' + data.class + '">' + data.text + '</span>')
            },
            width: "100%",
            placeholder: (TRANSLATION && TRANSLATION.username) ? TRANSLATION.username : "Search username...",
            allowClear: true,
            multiple: false,
            minimumInputLength: 3, // the minimum of symbols to input before perform a search
            language: {
                inputTooShort: function (args) {
                    let remain = args.minimum - args.input.length;
                    return TRANSLATION.select2_enter_char.replace(
                        "%qty%",
                        remain,
                    );
                },
                noResults: function () {
                    return TRANSLATION.not_found;
                },
            },
        });
        if (!!urlParams.get('user_id')) {
            new Promise(resolve => {
                get_user_by_user_id({
                    user_id: urlParams.get('user_id')
                }).then(userdata => {
                    dateTo.current.setAttribute("value", gettodatdate());
                    dateFrom.current.setAttribute("value", gettodatdate());
                    jQuery("#cptbot_users_list").select2({
                        data: [{
                            id: userdata.ID,
                            text: userdata.data.user_nicename
                        }],
                    }).select2('data', userdata.ID);
                    resolve(true);
                }).then(data => {
                    document.getElementById("cptbot_search_chat").click();
                })
            })
        }
        let maxDate = gettodatdate();
        settoday(maxDate);
    }, []);

    function gettodatdate() {
        let dtToday = new Date();
        let month = dtToday.getMonth() + 1;
        let day = dtToday.getDate();
        let year = dtToday.getFullYear();
        if (month < 10)
            month = '0' + month.toString();
        if (day < 10)
            day = '0' + day.toString();

        return year + '-' + month + '-' + day;
    }

    function getfrom(e) {
        dateTo.current.setAttribute("value", '');
        dateTo.current.setAttribute("min", e.target.value);
    }

    function downloadChatTxt() {
        let chatText = "CHAT CONVERSATION HISTORY\n";
        chatText += "==================================================\n\n";

        chat.forEach((data) => {
            const from = data.message_type === 'ai' ? chatbot_name : wc_cptbot.user.data.user_nicename;
            chatText += `From: ${from}\n`;
            chatText += `Date: ${data.created_at}\n`;
            chatText += `Message: ${data.message}\n`;
            chatText += "--------------------------------------------------\n\n";
        });

        const blob = new Blob([chatText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement("a");
        a.href = url;
        a.download = `chat-history-${userselect.current.value}-${gettodatdate()}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function filterformsubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const searchdata = [...formData.entries()];
        let body = [];
        if (searchdata.length === 3) {
            searchdata.forEach(element => {
                body[element[0]] = element[1];
            });
            fetch(wc_cptbot.admin_ajax, {
                method: "post",
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Cache-Control': 'no-cache',
                },
                body: new URLSearchParams({ ...body, action: "wc_cptbot_get_filtered_chat", nonce: wc_cptbot.nonce })
            }).then(response => {
                return response.json();
            }).then(data => {
                setchat(data.chat);
                setchatfetch(true);
                if (data.block === "block") {
                    setuserblocked(true);
                }
            });
        }
    }

    function blockuser(e) {
        e.preventDefault();
        if (!!userselect.current.value) {
            let txt = userblocked ? "Unblock" : "Block";
            const modalId = "cptbot-block-user-modal";
            const existing = document.getElementById(modalId);
            if (existing) existing.remove();

            const modalHtml = `
                <div id="${modalId}" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; justify-content: center; align-items: center;">
                    <div style="background: #fff; padding: 28px 30px; border-radius: 8px; max-width: 450px; width: 90%; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                        <h2 style="margin-top: 0; color: #d63638; font-size: 20px;">⚠️ ${TRANSLATION.warning || "Confirmation"}</h2>
                        <p style="font-size: 14px; line-height: 1.5; margin: 16px 0; color: #3c434a;">${(TRANSLATION.areyousure || "Are you sure you want to") + ' ' + txt + " " + (TRANSLATION.this_user || "this user") + '?'}</p>
                        <div style="margin-top: 24px; display: flex; justify-content: center; gap: 12px;">
                            <button id="cptbot-block-confirm-btn" class="button button-primary button-large" style="background-color: #d63638; border-color: #d63638;">${txt}</button>
                            <button id="cptbot-block-cancel-btn" class="button button-large">${TRANSLATION.cancel || "Cancel"}</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML("beforeend", modalHtml);
            const modal = document.getElementById(modalId);
            document.getElementById("cptbot-block-cancel-btn").addEventListener("click", () => modal.remove());
            document.getElementById("cptbot-block-confirm-btn").addEventListener("click", () => {
                modal.remove();
                block_current_user(userselect.current.value).then(data => {
                    const blockBtn = document.querySelector('#wc_cptbot_block_user');
                    if (blockBtn) blockBtn.innerHTML = data.msg;
                });
            });
        } else {
            document.querySelector('.wc-cptbot-select-filter').style.padding = "10px";
            document.querySelector('.wc-cptbot-select-filter').style.border = "1px solid red";
        }
    }

    return <Fragment>
        <form name="wc-cptbot-form" id="wc-cptbot-form" onSubmit={(e) => filterformsubmit(e)}>
            <div className="wc-cptbot-history-container">
                <div className="wc-cptbot-history-body">
                    <div className="wc-cptbot-history-filter">
                        <div className="wc-cptbot-filter-text">
                            <span>{TRANSLATION.chat_history}</span>
                        </div>
                        <div className="wc-cptbot-filters">
                            <div className="wc-cptbot-select-filter">
                                <span>{TRANSLATION.username}<b style={{ color: "red" }}> *</b></span>
                                <select name="cptbot_users_list" ref={userselect} id="cptbot_users_list" required>
                                    <option value=""></option>
                                </select>
                            </div>
                            <div className="wc-cptbot-date-filter">
                                <span>{TRANSLATION.from}<b style={{ color: "red" }}> *</b> :</span>
                                <input type="date" name="wc-cptbot-from" ref={dateFrom} onChange={(e) => getfrom(e)} max={today} required />
                            </div>
                            <div className="wc-cptbot-date-filter">
                                <span>{TRANSLATION.to}<b style={{ color: "red" }}> *</b> :</span>
                                <input type="date" name="wc-cptbot-to" ref={dateTo} max={today} required />
                            </div>
                            <div className="wc-cptbot-button">
                                <button type="submit" id="cptbot_search_chat" className="button-primary">
                                    <Searchico />
                                    <span>{TRANSLATION.search}</span>
                                </button>
                            </div>
                            {
                                chat.length > 0 ? <div className="wc-cptbot-button" onClick={downloadChatTxt}>
                                    <button type="button" className="button-primary">
                                        <Downloadico />
                                        <span>{TRANSLATION.download}</span>
                                    </button>
                                </div> : null
                            }
                            {
                                chat.length > 0 ? <div title={userblocked ? TRANSLATION.usr_block : TRANSLATION.block_placeholder} onClick={(e) => blockuser(e)} className="wc-cptbot-button">
                                    <button type="button" id="wc_cptbot_block_user" className="button-primary">
                                        <Userblock />
                                        <span>{userblocked ? TRANSLATION.unblock_txt : TRANSLATION.block_txt}</span>
                                    </button>
                                </div> : null
                            }

                        </div>
                    </div>
                    <div className="wc-cptbot-history-chats">
                        <div className="wc-cptbot-chat-title">
                            <span></span>
                            <h4>Chat Data</h4>
                            <span></span>
                        </div>
                        <div className="cptbot-chatbox-content">
                            {
                                (chat.length > 0) ? chat.map((data, key) => {
                                    const formattedMessage = processMessage(data.message || '');
                                    return (data.message_type !== "ai") ? <div key={key} className="cptbot-chatbox cptbot-chatbox-ai-msg">
                                        <div className="cptbot-chatbox-img"><img title={TRANSLATION.created_by + ' ' + wc_cptbot.user.data.user_nicename + ' ' + TRANSLATION.on_date + ': ' + data.created_at} src={avatar} /></div>
                                        <div className="cptbot-chatbox-msg" style={{ background: color }}>
                                            {(data.message !== '') ? <Fragment>{data.created_at} : {ReactHtmlParser(formattedMessage)}</Fragment> : TRANSLATION.false_resp}
                                        </div>
                                        <div className="cptbot-chatbox-blank"></div>
                                    </div> : <div key={key} className="cptbot-chatbox cptbot-chatbox-user-msg">
                                        <div className="cptbot-chatbox-blank"></div>
                                        <div className="cptbot-chatbox-msg" style={{ background: color }}>
                                            {ReactHtmlParser(formattedMessage)} : {data.created_at}
                                        </div>
                                        <div className="cptbot-chatbox-img"><img title={TRANSLATION.created_by + ' ' + chatbot_name + ' ' + TRANSLATION.on_date + ': ' + data.created_at} src={AiLogo} /></div>
                                    </div>
                                }) : (chatfetch ? <div className="wc-cptbot-chat-not-found"><img src={not_found} /><span>{TRANSLATION.chat_not_found}</span></div> : '')
                            }
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </Fragment>;
}

export default History;
