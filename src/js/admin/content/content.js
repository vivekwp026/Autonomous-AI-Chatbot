import React, { Fragment, useEffect, useState } from "react";
import { applyFilters } from '@wordpress/hooks';
import CustomContent from "./tabcontent/customcontent";
import VoiceContent from "./tabcontent/voicecontent";

export const CPTBOT_TAB_CONTENT = 'wc_cptbot_tab_contents';
const tabs = wc_cptbot_content.tabs;

function Content() {
    const [default_tab, setdefault_tab] = useState('content');

    useEffect(() => {
        let deftab = localStorage.getItem("wc_cptbot_content_def_tab");
        if (performance.navigation.type == 1) {
            setdefault_tab(deftab);
        } else {
            localStorage.setItem("wc_cptbot_content_def_tab", 'content');
            setdefault_tab('content');
        }
    }, []);

    function gettabcontent() {
		return applyFilters(CPTBOT_TAB_CONTENT, {
			'content': <CustomContent/>,
			'voice': <VoiceContent/>,
		});
    }

    function setdefaulttab(tab) {
        setdefault_tab(tab);
        localStorage.setItem("wc_cptbot_content_def_tab", tab);
    }

    return <Fragment>
        <div className="wrap">
            <nav className="nav-tab-wrapper">
                {
                    (typeof tabs === "object")&&
                    Object.entries(tabs).map((tab, key) => {
                        return <span className={tab[0]===default_tab?"nav-tab nav-tab-active":"nav-tab"} key={key} onClick={()=>setdefaulttab(tab[0])}><span className={tab[1].icon}></span>{tab[1].title}</span>
                    })
                }
            </nav>
            <div id="wrapper">
                {
					gettabcontent()[default_tab]
				}
            </div>
        </div>
    </Fragment>;
}

export default Content;
