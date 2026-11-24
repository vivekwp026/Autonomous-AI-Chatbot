import { Fragment, useState, useEffect } from "@wordpress/element";
import ChatBoxModal from "./chatboxmodal/chatboxmodal.js";
import { ArrowDown, Messageicon, ArrowDownleft } from "./../action/icons";

const color = wc_cptbotObject.primary_color;
const SecondaryColor = wc_cptbotObject.secondary_color;
const animation = wc_cptbotObject.animation;
const position = (wc_cptbotObject.cpt_bot_position !== "right") ? "cptbot-circle-left" : '';
const modal_position = (wc_cptbotObject.cpt_bot_position !== "right") ? "cptbot-chatbox-left" : '';
const modle_status = wc_cptbotObject?.cpt_bot_status === "enable";

function Chatbox() {

    if (!modle_status) {
        return null;
    }

    const [open, setopen] = useState(false);
    const [chat, setchat] = useState([]);
    const [display, setdisplay] = useState("flex");
    const [chatMboxClass, setchatMboxClass] = useState("d-none");


    useEffect(() => {
        let chatboxupdatedClass = '';
        if (wc_cptbotObject.cpt_bot_position === "right") {
            chatboxupdatedClass = !!animation ? "cptbot-chatbox-modal wc-cptbot-bottom-to-top-right " + modal_position : "cptbot-chatbox-modal " + modal_position;
        } else {
            chatboxupdatedClass = !!animation ? "cptbot-chatbox-modal wc-cptbot-bottom-to-top-left " + modal_position : "cptbot-chatbox-modal " + modal_position;
        }
        setchatMboxClass(chatboxupdatedClass);
    }, []);

    useEffect(() => {
        const isMobile = window.screen.width < 480 ? true : false;
        if (isMobile && open) {
            setdisplay("none");
        } else {
            setdisplay("flex");
        }
    }, [open]);

    function close() {

        let openclass = "cptbot-chatbox-modal " + modal_position;
        if (open && !!animation) {
            if (wc_cptbotObject.cpt_bot_position === "right") {
                openclass += " wc-cptbot-top-to-bottom-right";
            } else {
                openclass += " wc-cptbot-top-to-bottom-left";
            }
        }
        if (!open && !!animation) {
            if (wc_cptbotObject.cpt_bot_position === "right") {
                openclass += " wc-cptbot-bottom-to-top-right";
            } else {
                openclass += " wc-cptbot-bottom-to-top-left";
            }
        }
        if (!!animation && open) {
            setTimeout(() => {
                setopen(!open);
            }, 300)
        } else {
            setopen(!open);
        }
        setchatMboxClass(openclass);
    }

    let chatboxClass = open ? "cptbot-chatbox-circle cptbot-chatbox-open " + position : (!!animation ? "cptbot-chatbox-circle wc-cptbot-jump " + position : "cptbot-chatbox-circle " + position);
    return <Fragment>
        {
            open ? <ChatBoxModal setopen={setopen} chat={{ chat, setchat }} open={open} color={color} SecondaryColor={SecondaryColor} chatMboxClass={chatMboxClass} setchatMboxClass={setchatMboxClass} /> : null
        }
        <div className={chatboxClass} style={{ backgroundColor: open ? SecondaryColor : color, border: open ? "2px Solid " + color : null, boxShadow: open ? null : color + " 0px 20px 30px -10px", display: display }} onClick={() => close()}>
            {open ? ((wc_cptbotObject.cpt_bot_position == "right") ? <ArrowDown color={color} /> : <ArrowDownleft color={color} />) : <Messageicon color={SecondaryColor} />}
        </div>
    </Fragment>;
}

export default Chatbox;
