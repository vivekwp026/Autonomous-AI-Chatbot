import React, { Fragment, useState, useRef } from "react";
import toast from 'react-hot-toast';
import ReactHtmlParser from "html-react-parser";
import { speech_to_openai } from "../../../action";

const TRANSLATION = wc_cptbot_content.content_translations;

function VoiceContent() {

    const [prompt, setprompt] = useState('');
    const [voicerecognize, setvoicerecognize] = useState(false);
    const [aidata, setaidata] = useState("");

    const copyclipboardbtn = useRef();

    function listenspeech() {
        if ('webkitSpeechRecognition' in window) {
          // Create an instance of the SpeechRecognition object
          const recognition = new webkitSpeechRecognition();
          // Set the parameters
          recognition.continuous = true;  // Continue recognition even if the user pauses
          recognition.interimResults = true;  // Get interim results while speaking

          recognition.onstart = function () {
                setvoicerecognize(true);
          };

          recognition.onend = function (e) {
              setvoicerecognize(false);
              speech_to_openai({prompt:prompt}).then(data => {
                 setaidata(data);
              });
          };

          // Event handler for when a speech recognition result is received
          recognition.onresult = function(event) {
            const result = event.results[event.results.length - 1];
            const transcript = result[0].transcript;
            copyclipboardbtn.current.disabled = false;
            setprompt(transcript);
          };

          // Start the speech recognition
          recognition.start();
        } else {
          console.warn(TRANSLATION.admin.speech_not_support);
        }
    }

    function copyClipboard() {
        // Get the text field
        var copyText = document.querySelector(".clipboard");
        copyclipboardbtn.current.innerHTML = TRANSLATION.general.copied_text;
        copyText.select();
        if (window.isSecureContext && navigator.clipboard) {
            copyText.setSelectionRange(0, 99999); // For mobile devices
            navigator.clipboard.writeText(content);
        } else {
            document.execCommand("copy");
        }
        toast.success(TRANSLATION.general.content_copy, {
            position: "bottom-center",
            style: {
              borderRadius: '10px',
              background: '#333',
              color: '#fff',
            },
        });
    }

    return <Fragment>
        <div className="voice-container">
            <div className="sample-msg">
                {ReactHtmlParser(TRANSLATION.general?.voice_example || '')}
            </div>
            <div className="voice-button">
                <button type="button" onClick={listenspeech} className="button button-primary">
                    <span className="dashicons dashicons-microphone"></span>
                    {voicerecognize?<span>{TRANSLATION.general.listen_text}</span>:<span>{TRANSLATION.general.speak_text}</span>}
                </button>
            </div>
            <div className="editorbox">
                <span>{TRANSLATION.general.user_text}</span>
                <textarea disabled defaultValue={prompt} style={{height:"80px", maxHeight:"80px"}}></textarea>
            </div>
            <div className="editorbox">
                <div>
                    <span>{TRANSLATION.general.ai_text}</span>
                    <button type="button" disabled ref={copyclipboardbtn} className="button button-primary" onClick={copyClipboard}>Copy</button>
                </div>
                <textarea defaultValue={aidata} className="clipboard" style={{height:"280px", maxHeight:"280px"}}></textarea>
            </div>
        </div>
    </Fragment>;
}

export default VoiceContent;
