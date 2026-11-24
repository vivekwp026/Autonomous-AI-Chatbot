import { __ } from "@wordpress/i18n";

const getIcon = (type) => {
    switch (type) {
        case "conversations":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            );
        case "messages":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            );
        case "users":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            );
        case "resolution":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
            );
        case "knowledge":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
            );
        case "cart":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            );
        case "revenue":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2"></rect>
                    <circle cx="12" cy="12" r="2"></circle>
                    <path d="M6 12h.01M18 12h.01"></path>
                </svg>
            );
        case "top_product":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
            );
        case "coupons":
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"></path>
                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                    <line x1="15" y1="15" x2="15.01" y2="15"></line>
                </svg>
            );
        case "latency":
        default:
            return (
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            );
    }
};

const decodeHtmlEntities = (str) => {
    if (!str || typeof str !== "string") return str;
    const txt = document.createElement("textarea");
    txt.innerHTML = str;
    return txt.value;
};

const MetricCard = ({
    title,
    value,
    subtitle,
    icon,
    trend,
    trendDirection = "neutral",
    loading = false,
    isPro = false,
}) => {
    const isProActive = typeof wc_cptbot !== "undefined" && (Boolean(wc_cptbot.is_pro_active) || Boolean(wc_cptbot.is_pro));
    const showCrown = !isProActive && Boolean(isPro);

    const decodedTitle = decodeHtmlEntities(title);
    const decodedValue = decodeHtmlEntities(value);
    const decodedSubtitle = decodeHtmlEntities(subtitle);

    return (
        <div className={`wcaichatbot-metric-card ${showCrown ? "is-pro-locked" : ""}`}>
            <div className="wcaichatbot-metric-header">
                <span className="wcaichatbot-metric-icon">{getIcon(icon)}</span>
                <span className="wcaichatbot-metric-title" title={decodedTitle}>
                    {decodedTitle}
                </span>
                {showCrown && (
                    <span className="wcaichatbot-metric-crown" title={__("PRO Feature", "autonomous-ai-chatbot")}>
                        {typeof wc_cptbot !== "undefined" && wc_cptbot.crown_icon ? (
                            <img
                                src={wc_cptbot.crown_icon}
                                className="wcaichatbot-crown-icon"
                                alt={__("PRO Feature", "autonomous-ai-chatbot")}
                                width="16"
                                height="13"
                            />
                        ) : (
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 16"
                                width="16"
                                height="13"
                            >
                                <path
                                    d="M3 15h14l2-10-5 2.5-4-6.5-4 6.5-5-2.5 2 10z"
                                    fill="#ffb800"
                                    stroke="#e0a100"
                                    strokeWidth="0.75"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        )}
                    </span>
                )}
            </div>

            <div className="wcaichatbot-metric-body">
                {loading ? (
                    <div className="wcaichatbot-skeleton-value"></div>
                ) : (
                    <span className="wcaichatbot-metric-number" title={decodedValue}>
                        {decodedValue}
                    </span>
                )}
            </div>

            <div className="wcaichatbot-metric-footer">
                {loading ? (
                    <div className="wcaichatbot-skeleton-sub"></div>
                ) : (
                    <div className="wcaichatbot-metric-sub-wrapper">
                        {trend && (
                            <span className={`wcaichatbot-trend-badge trend-${trendDirection}`}>
                                {trendDirection === "up" && "↗ "}
                                {trendDirection === "down" && "↘ "}
                                {trend}
                            </span>
                        )}
                        <span className="wcaichatbot-metric-subtext" title={decodedSubtitle}>
                            {decodedSubtitle}
                        </span>
                    </div>
                )}
            </div>
        </div>
    );
};

export default MetricCard;

