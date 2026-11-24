import { Fragment } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { UploadFile } from "../../utils/icon";
import { loadModel } from "../../action/index";

const Model = () => {
    const setFieldData = (e) => {
        if (!!e?.target?.files?.length) {
            let key = e.target.files[0].name.split('.')[0];
            loadModel(key, e.target.files[0].name);
        }
    }

    return (
        <Fragment>
            <div className="wc-cptbot-models-upload-wrapper">
                <input type="file" className="wc-cptbot-file-input" onChange={(e) => setFieldData(e)} accept=".onnx" />
                <div className="wc-cptbot-models-upload-box">
                    <UploadFile />
                    <small>{__('Upload Model', 'autonomous-ai-chatbot')}</small>
                </div>
            </div>
        </Fragment>
    );
}

export default Model;
