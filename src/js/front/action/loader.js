import React from "react";

function Loader({ color, type = 'ripple' }) {
    if (type === 'ai') {
        return (
            <div className="cptbot-ai-loader" style={{ color: color }}>
                <span></span>
                <span></span>
                <span></span>
            </div>
        );
    }

    if (type === 'orb') {
        return (
            <div className="cptbot-glowing-orb" style={{ color: color }}></div>
        );
    }

    if (type === 'gemini') {
        return (
            <div className="cptbot-gemini-loader">
                <div className="gemini-star main"></div>
                <div className="gemini-star secondary"></div>
                <div className="gemini-star tertiary"></div>
            </div>
        );
    }

    return <div className="lds-ripple">
        <div style={{ border: "4px solid" + color }}></div>
        <div style={{ border: "4px solid" + color }}></div>
    </div>;
}

export default Loader;
