import React, { Fragment } from "react";
const count = wc_cptbot_content.count;

const TRANSLATION = wc_cptbot_content.content_translations;

function EditorBox({ allparams }) {
    let {params, setparams, action, setdata, data, select, setselect, settopic} = allparams;
    let style = {}
    !!params.body.height&&(style.height = params.body.height);
    !!params.body.maxHeight && (style.maxHeight = params.body.maxHeight);
    !!params.body.minWidth && (style.minWidth = params.body.minWidth+ "%");

    function changedata(e) {
        setdata({...data, [params.head.slug]: e.target.value});
    }

    function changeselect(e) {
        setselect({...select, [params.head.slug]: e.target.value});
    }

    function buttonclick(e) {
        params.head.btn_label = TRANSLATION.general.processing;
        setparams(params);
        action(params.head.slug);
    }

    function changeradio(e) {
        settopic(e.target.value);
    }

    return <Fragment>
        <div className="editor-box">
            <div className="editer-label">
                {
                    !!(params.head2) ? <Fragment>
                        <div className="custom-label">
                            <div className="label-box">
                                <input type="radio" name="custom_label" onClick={(e)=>changeradio(e)} value="label" defaultChecked />
                                <span className="label-name">{params.head.label}</span>
                            </div>
                            <div className="label-box">
                                <input type="radio" name="custom_label" onClick={(e)=>changeradio(e)} value="customlabel" />
                                <span className="label-name">{params.head2.label}</span>
                            </div>
                        </div>
                    </Fragment> :<span className="label-name">{params.head.label}</span>
                }
                <div className="label-count">
                    {params.head.select_label &&<Fragment> <span>No. Of {params.head.label}:</span>
                        <select disabled={params.head.btn_disable? true:false} onChange={(e)=>changeselect(e)}>
                        {
                            count.map((c, key) => <option key={key} value={c}>{c}</option>)
                        }
                        </select>
                    </Fragment>
                    }
                    <button type="button" id={params.head.slug+"_button"} disabled={params.head.btn_disable? true:false} className="button button-secondary" onClick={(e)=>buttonclick(e)}>{params.head.btn_label}</button>
                </div>
            </div>
            <div className="editor-text">
                <textarea defaultValue="" id={"wc_cptbot_textarea_"+params.head.slug} placeholder={!!params.body.placeholder?params.body.placeholder:''} style={style} onChange={(e)=>changedata(e)}></textarea>
            </div>
        </div>
    </Fragment>;
}

export default EditorBox;
