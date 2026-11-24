import { Fragment, useState } from "react";
import { applyFilters } from '@wordpress/hooks';

export const CPTBOT_TAB_CONTENT = 'wc_cptbot_tab_contents';
const tabs = wc_cptbot_image.tabs;

function OpenAiImage() {
    const [default_tab, setdefault_tab] = useState('image');

    function gettabcontent() {
		return applyFilters(CPTBOT_TAB_CONTENT, {
			'image': "content",
			'variation': "voice",
		});
	}

    return <Fragment>
        <div className="wrap">
            <nav className="nav-tab-wrapper">
                {
                    (typeof tabs === "object")&&
                    Object.entries(tabs).map((tab, key) => {
                        return <span className={tab[0]===default_tab?"nav-tab nav-tab-active":"nav-tab"} key={key} onClick={()=>setdefault_tab(tab[0])}><span className={tab[1].icon}></span>{tab[1].title}</span>
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

export default OpenAiImage;
