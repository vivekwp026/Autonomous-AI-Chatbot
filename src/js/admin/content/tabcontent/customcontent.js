import React, { Fragment, useEffect, useState, useRef } from "react";
import toast from 'react-hot-toast';
import ReactHtmlParser from "html-react-parser";
import EditorBox from "./editorbox";
import AdminLoader from "./../../loader";
import { create_custom_data, save_content_openai_setting, create_new_post } from "../../../action";

const openai_settings = wc_cptbot_content.openai_settings;
const posttypes = wc_cptbot_content.posttypes;
const TRANSLATION = wc_cptbot_content.content_translations;
const isProActive = Boolean(wc_cptbot_content.is_pro_active);
const proUpgradeUrl = wc_cptbot_content.pro_upgrade_url || 'https://store.webkul.com/woocommerce-plugins/WooCommerce.html?p=1';
const proBtnText = wc_cptbot_content.pro_btn_text || 'Upgrade to PRO';
const proBannerDesc = wc_cptbot_content.pro_banner_desc || 'Upgrade to Autonomous AI Chatbot Pro to automatically draft WooCommerce products directly with AI.';
const crownIcon = wc_cptbot_content.crown_icon || (typeof wc_cptbot !== 'undefined' ? wc_cptbot.crown_icon : '');

const SimpleCrownSvg = ({ width = 16, height = 13, style = {} }) => (
    <svg
        width={width}
        height={height}
        viewBox="0 0 20 16"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        style={style}
    >
        <path
            d="M3 15h14l2-10-5 2.5-4-6.5-4 6.5-5-2.5 2 10z"
            fill="#ffb800"
            stroke="#e0a100"
            strokeWidth="0.75"
            strokeLinejoin="round"
        />
    </svg>
);

function CustomContent() {

    let [editorboxparams, seteditorboxparams] = useState({
        head:{
            slug: "topic",
            label: TRANSLATION.editorbox.label,
            btn_label: TRANSLATION.editorbox.btn_label,
            btn_disable: false,
            select_label: true,
        },
        head2:{
            slug: "custom_topic",
            label: TRANSLATION.editorbox.head2_label,
        },
        body: {
            maxheight: 100,
            minWidth: 100,
            placeholder: TRANSLATION.editorbox.bplaceholder
        }
    });

    let [sectionboxparams, setsectionboxparams] = useState({
        head: {
            slug: "section",
            label: TRANSLATION.sectionbox.label,
            btn_label: TRANSLATION.sectionbox.btn_label,
            select_label: true,
            btn_disable: true,
        },
        body: {
            height: 100,
            maxheight: 200,
            minWidth: 100,
        }
    })

    let [contentboxparams, setcontentboxparams] = useState({
        head: {
            slug: "content",
            label: TRANSLATION.contentbox.label,
            btn_label: TRANSLATION.contentbox.btn_label,
            btn_disable: true,
            select_label: true,
        },
        body: {
            height: 300,
            maxheight: 400,
            minWidth: 100,
        }
    })

    let [excerptboxparams, setexcerptboxparams] = useState({
        head: {
            slug: "excerpt",
            label: TRANSLATION.excerptbox.label,
            btn_label: TRANSLATION.excerptbox.btn_label,
            btn_disable: true,
            select_label: false,
        },
        body: {
            height: 100,
            maxheight: 200,
            minWidth: 100,
        }
    })

    let [metaboxparams, setmetaboxparams] = useState({
        head: {
            slug: "meta",
            label: TRANSLATION.metabox.label,
            btn_label: TRANSLATION.metabox.btn_label,
            btn_disable: true,
            select_label: false,
        },
        body: {
            height: 100,
            maxheight: 200,
            minWidth: 100,
        }
    });

    const [slug, setslug] = useState('');
    const [openmodal, setopenmodal] = useState(false);
    const [showProModal, setShowProModal] = useState(false);
    const [data, setdata] = useState({});
    const [select, setselect] = useState('');
    const [suggestions, setsuggestions] = useState({});
    const [defsuggestion, setdefsuggestion] = useState("");
    const [section, setsection] = useState("");
    const [formentry, setformentry] = useState({});
    const [label, setlabel] = useState('label');
    const [posttype, setposttype] = useState('post');
    var settingdata = {};

    useState(() => {
        if (!!openai_settings && typeof openai_settings === "object") {
            openai_settings.forEach(element => {
                settingdata = {...settingdata, [element.field.name]:element.field.value}
            });
        }
        setformentry(settingdata);
    }, []);

    useEffect(() => {
        switch (slug) {
            case "topic":
                if (!!data.topic && data.topic !== '') {
                    if (label === "label") {
                        setopenmodal(true);
                        if (1 === select) {
                            setselect({
                                slug:1
                            })
                        }
                        let postdata = {
                            data: JSON.stringify(data),
                            select:select !== '' ? JSON.stringify(select): '',
                            slug: slug,
                            formentry:JSON.stringify(formentry)
                        }
                        create_custom_data(postdata).then(data => {
                            let regex = /^(1|2|3|4|5|6|7|8|9|10)\.\s*(.*)/gm;
                            if (typeof data.match(regex) === "object" && data.match(regex) !== null) {
                                let matches = data.match(regex).map(match => match.replace(regex, '$2'));
                                setsuggestions(matches);
                            } else {
                                let matches = [data.replace(regex, '$2')];
                                setsuggestions(matches);
                            }
                            editorboxparams.head.btn_label = TRANSLATION.general.recreate_topic;
                            seteditorboxparams(editorboxparams);
                        });
                    } else {
                        setdefsuggestion({ ...defsuggestion, [slug]: replaceAllDoubleQuotes(data.topic) });
                        sectionboxparams.head.btn_disable = false;
                        setsectionboxparams(sectionboxparams);
                        setslug('');
                        toast.success(TRANSLATION.general.topic_set, {
                            position: "bottom-center",
                            style: {
                              borderRadius: '10px',
                              background: '#333',
                              color: '#fff',
                            },
                        });
                    }
                } else {
                    setslug('');
                    toast.error(TRANSLATION.general.fill_topic, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
              break;
            case "section":
                if (defsuggestion.topic !== '') {
                    let postdata = {
                        data: defsuggestion.topic,
                        select:select !== '' ? JSON.stringify(select): '',
                        slug:slug,
                        formentry:JSON.stringify(formentry)
                    }
                    create_custom_data(postdata).then(data => {
                        const sections_textarea = document.querySelector('#wc_cptbot_textarea_section');
                        sectionboxparams.head.btn_label = TRANSLATION.general.recreate_section;
                        contentboxparams.head.btn_disable = false;
                        sections_textarea.value = data;
                        setcontentboxparams(contentboxparams);
                        setdefsuggestion({ ...defsuggestion, [slug]: replaceAllDoubleQuotes(data) });
                        setsection(data);
                        setsectionboxparams(sectionboxparams);
                        setslug('');
                        toast.success(TRANSLATION.general.section_set, {
                            position: "bottom-center",
                            style: {
                              borderRadius: '10px',
                              background: '#333',
                              color: '#fff',
                            },
                        });
                    });

                } else {
                    setslug('');
                    toast.error(TRANSLATION.general.select_topic, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
              break;
            case "content":
                if (defsuggestion.topic !== '' && defsuggestion.content !== '') {
                    let postdata = {
                        data: JSON.stringify({content:defsuggestion}),
                        select:select !== '' ? JSON.stringify(select): '',
                        slug: slug,
                        section: section,
                        formentry:JSON.stringify(formentry)
                    }
                    create_custom_data(postdata).then(data => {
                        const content_textarea = document.querySelector('#wc_cptbot_textarea_content');
                        contentboxparams.head.btn_label = TRANSLATION.general.recreate_content;
                        excerptboxparams.head.btn_disable = false;
                        content_textarea.value = data;
                        setcontentboxparams(contentboxparams);
                        setexcerptboxparams(excerptboxparams);
                        setdefsuggestion({ ...defsuggestion, [slug]: replaceAllDoubleQuotes(data) });
                        setslug('');
                        toast.success(TRANSLATION.general.content_set, {
                            position: "bottom-center",
                            style: {
                              borderRadius: '10px',
                              background: '#333',
                              color: '#fff',
                            },
                        });
                    });

                } else {
                    setslug('');
                    toast.error(TRANSLATION.general.select_topic_content, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
              break;
            case "excerpt":
                if (!!data.topic && data.topic !== '') {
                    let postdata = {
                        data: defsuggestion.topic,
                        select:select !== '' ? JSON.stringify(select): '',
                        slug: slug,
                        formentry:JSON.stringify(formentry)
                    }
                    create_custom_data(postdata).then(data => {
                        const excerpt_textarea = document.querySelector('#wc_cptbot_textarea_excerpt');
                        excerptboxparams.head.btn_label = TRANSLATION.general.recreate_excerpt;
                        metaboxparams.head.btn_disable = false;
                        excerpt_textarea.value = data;
                        setexcerptboxparams(excerptboxparams);
                        setmetaboxparams(metaboxparams);
                        setdefsuggestion({ ...defsuggestion, [slug]: replaceAllDoubleQuotes(data) });
                        setslug('');
                        toast.success(TRANSLATION.general.excerpt_set, {
                            position: "bottom-center",
                            style: {
                              borderRadius: '10px',
                              background: '#333',
                              color: '#fff',
                            },
                        });
                    });

                } else {
                    setslug('');
                    toast.error(TRANSLATION.general.empty_topic, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
              break;
            case "meta":
                if (!!data.topic && data.topic !== '') {
                    let postdata = {
                        data: defsuggestion.topic,
                        select:select !== '' ? JSON.stringify(select): '',
                        slug:slug,
                        formentry:JSON.stringify(formentry)
                    }
                    create_custom_data(postdata).then(data => {
                        const meta_textarea = document.querySelector('#wc_cptbot_textarea_meta');
                        metaboxparams.head.btn_label = TRANSLATION.general.recreate_meta;
                        meta_textarea.value = data;
                        setmetaboxparams(metaboxparams);
                        setdefsuggestion({ ...defsuggestion, [slug]: replaceAllDoubleQuotes(data) });
                        setslug('');
                        toast.success(TRANSLATION.general.meta_set, {
                            position: "bottom-center",
                            style: {
                              borderRadius: '10px',
                              background: '#333',
                              color: '#fff',
                            },
                        });
                    });

                } else {
                    setslug('');
                    toast.error(TRANSLATION.general.empty_topic, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
              break;
        }
    }, [slug]);

    if (label === "customlabel") {
        editorboxparams.head.select_label = false;
        editorboxparams.head.btn_label= TRANSLATION.general.save_topic;
        editorboxparams.head.btn_disable= false;
    } else {
        editorboxparams.head.btn_disable= false;
        editorboxparams.head.select_label = true;
        editorboxparams.head.btn_label= TRANSLATION.general.suggest_topic;
    }

    useEffect(() => {
        seteditorboxparams(editorboxparams);
        setslug('');
    }, [label])

    function removemodal() {
        setopenmodal(false);
        setslug('');
    }

    function selectdefsuggestion(e) {
        let def_suggestion = e.target.getAttribute('data-suggestion');
        setdefsuggestion({...defsuggestion, [slug]: replaceAllDoubleQuotes(def_suggestion)});
        sectionboxparams.head.btn_disable = false;
        setsectionboxparams(sectionboxparams);
        setopenmodal(false);
        toast.success(TRANSLATION.general.suggest_used, {
            position: "bottom-center",
            style: {
              borderRadius: '10px',
              background: '#333',
              color: '#fff',
            },
        });
    }

    function OpenAifields(setting) {
        let field;
        if (setting.field.type == "input") {
            field = <input type={setting.field.type} name={setting.field.name} defaultValue={setting.field.value} />
        } else if (setting.field.type == "textarea") {
            field = <textarea name={setting.field.name} defaultValue={setting.field.value} style={{height:setting.field.height, maxHeight:setting.field.height}}></textarea>
        } else {
            field = <select name={setting.field.name} defaultValue={setting.field.value}>
                {
                    (setting.field.option).map((model, key) => {
                        return <option key={key} value={model}>{model}</option>
                    })
                }
            </select>
        }
        return field;
    }

    function formSubmit(e) {
        e.preventDefault();
        var formData = new FormData(e.target);
        let formdata = Object.fromEntries(formData);
        save_content_openai_setting(formdata).then(resp => {
            if (resp.status) {
                setformentry(resp.data);
                toast.success(TRANSLATION.general.openai_setting, {
                    position: "bottom-center",
                    style: {
                      borderRadius: '10px',
                      background: '#333',
                      color: '#fff',
                    },
                });
            } else {
                toast.error(TRANSLATION.general.looks_same, {
                    position: "bottom-center",
                    style: {
                      borderRadius: '10px',
                      background: '#333',
                      color: '#fff',
                    },
                });
            }
        })
    }

    function replaceAllDoubleQuotes(str) {
      return str.replace(/"/g, "'");
    }

    function createpost() {
        if (posttype === "product" && !isProActive) {
            setShowProModal(true);
            return;
        }
        if (!!defsuggestion.meta && defsuggestion.meta !== '') {
            create_new_post(defsuggestion, posttype).then(resp => {
                if (resp.status === "success") {
                    toast.success(posttype + " " + TRANSLATION.general.created, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                } else if (resp.is_pro === false) {
                    setShowProModal(true);
                    toast.error(resp.message || (posttype + " " + TRANSLATION.general.exist), {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                } else {
                    toast.error(posttype + " " + TRANSLATION.general.exist, {
                        position: "bottom-center",
                        style: {
                          borderRadius: '10px',
                          background: '#333',
                          color: '#fff',
                        },
                    });
                }
            });
        } else {
            toast.error(TRANSLATION.general.all_required_field, {
                position: "bottom-center",
                style: {
                  borderRadius: '10px',
                  background: '#333',
                  color: '#fff',
                },
            });
        }
    }

    return <Fragment>
        <div className="content-container">
            <div className="conrainer-editor">
                <EditorBox allparams={{params:editorboxparams, setparams:seteditorboxparams, action:setslug, setdata:setdata, data:data, select:select, setselect:setselect, settopic:setlabel}}  />
                {
                    !!defsuggestion.topic && <div className="def_topic">
                        <span>Selected Suggestion : {defsuggestion.topic}</span>
                        {label==="label"&&<span onClick={()=>setopenmodal(true)} className="dashicons dashicons-edit"></span>}
                    </div>
                }
                <EditorBox allparams={{params:sectionboxparams, setparams:setsectionboxparams, action:setslug, setdata:setdata, data:data, select:select, setselect:setselect, settopic:setlabel}}  />
                <EditorBox allparams={{params:contentboxparams, setparams:setcontentboxparams, action:setslug, setdata:setdata, data:data, select:select, setselect:setselect, settopic:setlabel}}  />
                <EditorBox allparams={{params:excerptboxparams, setparams:setexcerptboxparams, action:setslug, setdata:setdata, data:data, select:select, setselect:setselect, settopic:setlabel}}  />
                <EditorBox allparams={{ params: metaboxparams, setparams:setmetaboxparams, action: setslug, setdata: setdata, data: data, select: select, setselect: setselect, settopic: setlabel }} />

                <div className="content-creator">
                    <div className="content-creator-container">
                        <span>{TRANSLATION.general.create_txt}</span>
                        <select
                            value={posttype}
                            onChange={(e) => {
                                const val = e.target.value;
                                if (val === "product" && !isProActive) {
                                    setShowProModal(true);
                                    return;
                                }
                                setposttype(val);
                            }}
                        >
                            {Object.entries(posttypes).map(([key, name]) => (
                                <option value={key} key={key}>
                                    {name} {key === "product" && !isProActive ? "(PRO)" : ""}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="button-container">
                        <button type="button" className="button button-primary" onClick={createpost}>{TRANSLATION.general.create_txt}</button>
                    </div>
                </div>
            </div>
            <div className="container-setting">
                <div className="setting-box">
                    <div className="setting-header">
                        <span>{TRANSLATION.general.settings_txt}</span>
                    </div>
                    <form name="openai-settings" method="POST" className="openai-settings" onSubmit={(e)=>formSubmit(e)}>
                        <div className="setting-body">
                            {
                                openai_settings.map((setting, key) => {
                                    let classname ="ai-setting-box "+ setting.class;
                                    return <Fragment key={key}>
                                        <div className={classname}>
                                            <span className="heading">{setting.label}</span>
                                            <div className="setting-field">
                                                {OpenAifields(setting)}
                                            </div>
                                            <div className="setting-footer">
                                                {ReactHtmlParser(!!setting?.footer ? setting.footer : '')}
                                            </div>
                                        </div>
                                    </Fragment>
                                })
                            }
                            <div className="save-setting">
                                <button type="submit" className="button button-primary" id="cptbot_save_settings">{TRANSLATION.general.save_settings_txt}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {
            openmodal&&<div className="content-modal">
                <div className="modal-box">
                    <div className="modal-head">
                        <span>{TRANSLATION.general.gpt_suggestion}</span>
                        <span className="close" title="Close Modal" onClick={removemodal}>X</span>
                    </div>
                    <div className="modal-body">
                        {
                            suggestions.length > 0 ?
                                <ul>
                                {suggestions.map((suggestion, key) => {
                                    return <li key={key}>
                                        <span>{(key+1)}</span>
                                        <span>
                                            {suggestion.trim().replaceAll("\"", '')}
                                        </span>
                                        <span>
                                            <button type="button" onClick={(e)=>{selectdefsuggestion(e)}} data-suggestion={suggestion.trim().replaceAll("\"", '')} className="button button-primary">{TRANSLATION.general.use}</button>
                                        </span>
                                    </li>
                                })}
                                </ul>
                            : <AdminLoader />
                        }
                    </div>
                    <div className="modal-footer"></div>
                </div>
            </div>
        }
        {
            showProModal && (
                <div className="content-modal wcaichatbot-pro-modal">
                    <div className="modal-box" style={{ width: "560px", height: "auto", minHeight: "320px", display: "flex", flexDirection: "column" }}>
                        <div className="modal-head" style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "16px 20px" }}>
                            <span style={{ fontWeight: 600, fontSize: "15px", display: "flex", alignItems: "center", gap: "8px", color: "#0f172a" }}>
                                <SimpleCrownSvg width={16} height={13} />
                                {TRANSLATION.general?.pro_product_title || "WooCommerce Product Creation"}
                            </span>
                            <button
                                type="button"
                                className="wcaichatbot-modal-close"
                                title="Close Modal"
                                onClick={() => setShowProModal(false)}
                                style={{
                                    background: "transparent",
                                    border: "none",
                                    fontSize: "18px",
                                    lineHeight: "1",
                                    color: "#64748b",
                                    cursor: "pointer",
                                    padding: "4px 8px",
                                    margin: 0,
                                    outline: "none",
                                    boxShadow: "none",
                                    display: "flex",
                                    alignItems: "center",
                                    justifyContent: "center",
                                    borderRadius: 0
                                }}
                            >
                                ✕
                            </button>
                        </div>
                        <div className="modal-body" style={{ padding: "28px 24px", textAlign: "center", display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center" }}>
                            <div className="wcaichatbot-pro-badge" style={{ marginBottom: "12px", display: "inline-flex", alignItems: "center" }}>
                                PRO Feature
                            </div>
                            <h3 style={{ margin: "0 0 10px 0", fontSize: "18px", fontWeight: 700, color: "#0f172a" }}>
                                {TRANSLATION.general?.create_wc_products || "Create Complete WooCommerce Products with AI"}
                            </h3>
                            <p style={{ margin: "0 0 24px 0", fontSize: "13.5px", color: "#64748b", lineHeight: 1.6, maxWidth: "460px" }}>
                                {proBannerDesc}
                            </p>
                            <a href={proUpgradeUrl} className="wcaichatbot-pro-btn" style={{ padding: "10px 24px", fontSize: "14px", fontWeight: 600, textDecoration: "none", display: "inline-block" }}>
                                {proBtnText} &rarr;
                            </a>
                        </div>
                        <div className="modal-footer" style={{ padding: "12px 20px", background: "#f8fafc", borderTop: "1px solid #f1f5f9", display: "flex", justifyContent: "flex-end", alignItems: "center" }}>
                            <button type="button" className="button" onClick={() => setShowProModal(false)}>
                                {TRANSLATION.general?.close || "Close"}
                            </button>
                        </div>
                    </div>
                </div>
            )
        }
    </Fragment>;
}

export default CustomContent;
