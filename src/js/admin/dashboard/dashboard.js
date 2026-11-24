import { Fragment, useState, useEffect } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import { SelectControl, Spinner } from "@wordpress/components";
import Chart from 'react-apexcharts';
import { getLastSevenDays, GetLastSevenDaysChat, getTodaysusers, gettopsearchedproducts, getDashboardKPIs } from "./../../action";
import MetricCard from "./MetricCard";

const TRANSLATION = (typeof wc_cptbot !== 'undefined' && wc_cptbot.translation) ? wc_cptbot.translation : {};

function Dashboard() {
    const [type, settype] = useState("area");
    const [msgtype, setmsgtype] = useState("user");
    const [todaysuser, settodaysuser] = useState([]);
    const [duration, setduration] = useState('week');
    const Charttype = ["area", "bar", "line", "scatter"];
    const Usertype = ["user", "ai"];
    const durationtype = ["week", "month"];

    const [chatdata, setchartdata] = useState({
        options: {
            chart: {
                id: 'apexchart-example'
            },
            xaxis: {
                categories: []
            }
        },
        series: [{
            name: 'user traffic',
            data: [0, 0, 0, 0, 0, 0, 0]
        }]
    });

    const [proStatus, setProStatus] = useState({ is_pro: true, banner: '', upgrade_url: 'https://store.webkul.com/woocommerce-plugins/WooCommerce.html?p=1', btn_text: '' });
    const [kpiList, setKpiList] = useState([]);
    const [kpiLoading, setKpiLoading] = useState(true);

    const isProActive = Boolean(
        typeof wc_cptbot !== 'undefined' && (wc_cptbot.is_pro_active || wc_cptbot.is_pro)
    );

    const defaultKpis = [
        { id: "total_conversations", title: __("Total Conversations", "autonomous-ai-chatbot"), value: "0", subtitle: __("No activity in either period", "autonomous-ai-chatbot"), icon: "conversations" },
        { id: "total_messages", title: __("Total Messages", "autonomous-ai-chatbot"), value: "0", subtitle: __("No activity in either period", "autonomous-ai-chatbot"), icon: "messages" },
        { id: "active_users_today", title: __("Active Users Today", "autonomous-ai-chatbot"), value: "0", subtitle: __("No users active today", "autonomous-ai-chatbot"), icon: "users" },
        { id: "resolution_rate", title: __("AI Resolution Rate", "autonomous-ai-chatbot"), value: "100%", subtitle: __("Awaiting queries in period", "autonomous-ai-chatbot"), icon: "resolution" },
        { id: "knowledge_synced", title: __("Knowledge Synced", "autonomous-ai-chatbot"), value: isProActive ? "0" : "—", subtitle: isProActive ? __("0 total items indexed", "autonomous-ai-chatbot") : __("Available with PRO Addon", "autonomous-ai-chatbot"), icon: "knowledge", is_pro: !isProActive },
        { id: "bot_cart_additions", title: __("Bot Cart Additions", "autonomous-ai-chatbot"), value: "—", subtitle: isProActive ? __("No cart activity yet", "autonomous-ai-chatbot") : __("Available with PRO Addon", "autonomous-ai-chatbot"), icon: "cart", is_pro: !isProActive },
        { id: "bot_revenue", title: __("Revenue from Bot", "autonomous-ai-chatbot"), value: "$0.00", subtitle: isProActive ? __("No activity in this period", "autonomous-ai-chatbot") : __("Available with PRO Addon", "autonomous-ai-chatbot"), icon: "revenue", is_pro: !isProActive },
        { id: "top_product", title: __("Top Queried Product", "autonomous-ai-chatbot"), value: "—", subtitle: isProActive ? __("No product inquiries yet", "autonomous-ai-chatbot") : __("Available with PRO Addon", "autonomous-ai-chatbot"), icon: "top_product", is_pro: !isProActive },
        { id: "bot_coupons", title: __("Active Coupons", "autonomous-ai-chatbot"), value: "0", subtitle: isProActive ? __("Ready for bot redemption", "autonomous-ai-chatbot") : __("Available with PRO Addon", "autonomous-ai-chatbot"), icon: "coupons", is_pro: !isProActive },
        { id: "avg_latency", title: __("Avg AI Latency", "autonomous-ai-chatbot"), value: "1.2s", subtitle: __("Average response time", "autonomous-ai-chatbot"), icon: "latency" },
    ];

    useEffect(() => {
        setKpiLoading(true);
        const days = duration === "week" ? 7 : 30;
        getDashboardKPIs(days).then((data) => {
            if (data && Array.isArray(data) && data.length > 0) {
                setKpiList(data);
            }
            setKpiLoading(false);
        }).catch(() => {
            setKpiLoading(false);
        });
    }, [duration]);
    const [polarchart, setpolarchart] = useState({
        series: [0],

        options: {
            yaxis: {
                labels: {
                    style: {
                        fontWeight: '600'
                    },
                    formatter: (value) => {
                        return parseInt(value);
                    },
                }
            },
            chart: {
                type: 'polarArea',
            },
            labels: [''],
            stroke: {
                colors: ['#fff']
            },
            fill: {
                opacity: 0.9
            },
            responsive: [{
                breakpoint: 200,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
        },

    })
    useEffect(() => {
        let totaldays = duration === "week" ? 6 : 30;
        let days = getLastSevenDays(totaldays).map(day => day.toDateString());
        setchartdata({
            options: {
                ...chatdata.options,
                chart: {
                    id: 'apexchart-example'
                },
                xaxis: {
                    ...chatdata.options.xaxis,
                    categories: days
                }
            },
            series: [{
                name: TRANSLATION.chart_count,
                data: [0, 0, 0, 0, 0, 0, 0]
            }]
        });
        let data = {
            days: totaldays,
            user_type: msgtype
        }
        GetLastSevenDaysChat(data).then(data => {
            setchartdata({
                options: {
                    ...chatdata.options,
                    chart: {
                        id: 'apexchart-example'
                    },
                    xaxis: {
                        ...chatdata.options.xaxis,
                        categories: days
                    }
                },
                series: [{
                    name: TRANSLATION.chart_count,
                    data: data
                }]
            });
        });
        getTodaysusers(data.user_type).then(data => {
            settodaysuser(data);
        });

    }, [msgtype, duration]);

    const decodeEntities = (str) => {
        if (!str || typeof str !== 'string') return '';
        const txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    };

    useEffect(() => {
        gettopsearchedproducts().then(data => {
            if (data && data.is_pro === false) {
                setProStatus({
                    is_pro: false,
                    is_installed: data.is_installed || false,
                    banner: decodeEntities(data.banner) || 'Upgrade to PRO to view Top Searched Products.',
                    upgrade_url: decodeEntities(data.upgrade_url) || 'https://store.webkul.com/woocommerce-plugins/WooCommerce.html?p=1',
                    btn_text: data.btn_text || (data.is_installed ? 'Activate PRO Plugin' : 'Upgrade to PRO')
                });
                return;
            }
            if (data && data.series) {
                setpolarchart({
                    ...polarchart,
                    series: data.series,
                    options: {
                        ...polarchart.options,
                        chart: {
                            type: 'polarArea',
                        },
                        labels: data.label,
                        stroke: {
                            colors: ['#fff']
                        },
                        fill: {
                            opacity: 0.7
                        },
                        responsive: [{
                            breakpoint: 200,
                            options: {
                                chart: {
                                    width: 200
                                },
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }],
                    },
                });
            }
        });
    }, []);

    // console.log(todaysuser);
    var totalchatcount = 0;
    todaysuser.forEach(data => {
        totalchatcount += parseInt(data.entry_count);
    });

    const displayKpis = kpiList.length > 0 ? kpiList : defaultKpis;
    const proCardIds = ["bot_cart_additions", "bot_revenue", "top_product", "bot_coupons", "knowledge_synced"];
    const checkCardIsPro = (kpi) => !isProActive && (Boolean(kpi.is_pro) || proCardIds.includes(kpi.id));

    return <Fragment>
        <div className="wc-cptbot-dashboard-container">
            <div className="wc-cptbot-dashboard-body">
                <div className="wcaichatbot-kpi-container">
                    <div className="wcaichatbot-kpi-grid">
                        {displayKpis.map((kpi, idx) => (
                            <MetricCard
                                key={kpi.id || idx}
                                title={kpi.title}
                                value={kpi.value}
                                subtitle={kpi.subtitle}
                                icon={kpi.icon}
                                trend={kpi.trend}
                                trendDirection={kpi.trendDirection}
                                loading={kpiLoading}
                                isPro={checkCardIsPro(kpi)}
                            />
                        ))}
                    </div>
                </div>
                <div className="wc-cptbot-subcontainer">
                    <div className="wc-cptbot-charts-filter">
                        <div className="wc-cptbot-charts-name">
                            {__("User Traffic", "autonomous-ai-chatbot")}
                        </div>
                        <div className="wc-cptbot-charts-select">
                            <SelectControl
                                className="wc-cptbot-wp-select"
                                value={duration}
                                options={durationtype.map(opt => ({
                                    label: opt === "week" ? __("Week", "autonomous-ai-chatbot") : __("Month", "autonomous-ai-chatbot"),
                                    value: opt
                                }))}
                                onChange={(val) => setduration(val)}
                            />
                            <SelectControl
                                className="wc-cptbot-wp-select"
                                value={msgtype}
                                options={Usertype.map(opt => ({
                                    label: opt === "user" ? __("User Messages", "autonomous-ai-chatbot") : __("AI Messages", "autonomous-ai-chatbot"),
                                    value: opt
                                }))}
                                onChange={(val) => setmsgtype(val)}
                            />
                            <SelectControl
                                className="wc-cptbot-wp-select"
                                value={type}
                                options={Charttype.map(opt => ({
                                    label: opt.charAt(0).toUpperCase() + opt.slice(1),
                                    value: opt
                                }))}
                                onChange={(val) => settype(val)}
                            />
                        </div>
                    </div>
                    <div className="wc-cptbot-charts">
                        <Chart options={!!chatdata.options ? chatdata.options : {}} series={!!chatdata.series ? chatdata.series : []} key={Math.random()} type={type} width="100%" height={320} />
                    </div>
                </div>
                <div className="wc-cptbot-subcontainer2">
                    <div className="wc-cptbot-charts-mini-container">
                        <div className="wc-cptbot-charts-head">
                            <span>{__("Today's User Chat", "autonomous-ai-chatbot")}</span>
                        </div>
                        <div className="wc-cptbot-charts-body">
                            {todaysuser.length > 0 ?
                                <ul>
                                    <li key="0">
                                        <span><b>{__("Email", "autonomous-ai-chatbot")}</b></span>
                                        <span><b>{__("Message Count", "autonomous-ai-chatbot")}</b></span>
                                    </li>
                                    {
                                        todaysuser.length > 0 ?
                                            todaysuser.map((user, key) => {
                                                return <li key={key + 1}>
                                                    <span><a href={wc_cptbot.admin_url + "admin.php?page=chatgpt-bot&tab=history&user_id=" + user.user.ID}>{user.user.data.user_email}</a></span>
                                                    <span>{user.entry_count}</span>
                                                </li>
                                            })
                                            : null
                                    }
                                    <li key="">
                                        <span><b>{__("Total", "autonomous-ai-chatbot")}</b></span>
                                        <span className="chat_total"><b>{totalchatcount}</b></span>
                                    </li>
                                </ul> : <div className="wc-cptbot-not-found">
                                    <img src={wc_cptbot.no_result} width="30px" height="30px" />
                                    <span>{__("Chat not found", "autonomous-ai-chatbot")}</span>
                                </div>
                            }
                        </div>
                    </div>
                    <div className="wc-cptbot-charts-mini-container">
                        <div className="wc-cptbot-charts-head">
                            <span>
                                {__("Most Asked Products", "autonomous-ai-chatbot")}
                                {!proStatus.is_pro && (
                                    <img
                                        src={wc_cptbot.crown_icon}
                                        className="wcaichatbot-crown-icon"
                                        alt={__("PRO Feature", "autonomous-ai-chatbot")}
                                        title={__("PRO Feature", "autonomous-ai-chatbot")}
                                        width="16"
                                        height="13"
                                    />
                                )}
                            </span>
                        </div>
                        <div className="wc-cptbot-charts-body">
                            {!proStatus.is_pro ? (
                                <div className="wcaichatbot-pro-lock is-compact">
                                    <div className="wcaichatbot-pro-lock__preview" aria-hidden="true" inert="">
                                        <svg viewBox="0 0 280 200" width="100%" height="200" aria-hidden="true" style={{ margin: '0 auto', display: 'block' }}>
                                            <circle cx="140" cy="100" r="80" fill="#e0f2fe" opacity="0.6" />
                                            <circle cx="140" cy="100" r="55" fill="#bae6fd" opacity="0.7" />
                                            <circle cx="140" cy="100" r="30" fill="#7dd3fc" opacity="0.8" />
                                            <path d="M140 20 A80 80 0 0 1 216 147 L140 100 Z" fill="#3b82f6" opacity="0.45" />
                                            <path d="M140 100 L216 147 A80 80 0 0 1 83 158 Z" fill="#60a5fa" opacity="0.4" />
                                            <path d="M140 100 L83 158 A80 80 0 0 1 140 20 Z" fill="#93c5fd" opacity="0.45" />
                                        </svg>
                                    </div>
                                    <div className="wcaichatbot-pro-lock__overlay">
                                        <div className="wcaichatbot-pro-lock__card">
                                            <span className="wcaichatbot-pro-lock__icon" aria-hidden="true">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" strokeWidth="1.8"/>
                                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" strokeWidth="1.8"/>
                                                </svg>
                                            </span>
                                            <div>
                                                <strong className="wcaichatbot-pro-lock__title">
                                                    {TRANSLATION.most_ask_pro} <span className="wcaichatbot-pro-badge-pill">PRO</span>
                                                </strong>
                                                <p className="wcaichatbot-pro-lock__desc">{proStatus.banner}</p>
                                            </div>
                                            <a
                                                href={proStatus.upgrade_url}
                                                className="wcaichatbot-pro-lock__btn"
                                                target={proStatus.is_installed ? '_self' : '_blank'}
                                                rel="noopener noreferrer"
                                            >
                                                {proStatus.btn_text || 'Activate Pro'}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            ) : (polarchart.series && polarchart.series.length > 0 && polarchart.series.some(val => Number(val) > 0)) ? (
                                <div className="wc-cptbot-polarchart">
                                    <Chart options={polarchart.options} series={polarchart.series} type="polarArea" height="313px" />
                                </div>
                            ) : (
                                <div className="wc-cptbot-not-found" style={{ minHeight: '260px', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: '8px', color: '#64748b', textAlign: 'center', padding: '20px' }}>
                                    <img src={wc_cptbot.no_result} width="36px" height="36px" style={{ opacity: 0.6 }} />
                                    <span style={{ fontSize: '13.5px', fontWeight: 600, color: '#334155' }}>{__("No product inquiries recorded yet", "autonomous-ai-chatbot")}</span>
                                    <span style={{ fontSize: '12px', color: '#94a3b8' }}>{__("Top products asked about in customer chats will appear here.", "autonomous-ai-chatbot")}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Fragment>;
}

export default Dashboard;
