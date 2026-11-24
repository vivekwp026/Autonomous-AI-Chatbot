import { createRoot } from 'react-dom';
import History from "./history";

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('wc_cptbot_history');
    if (container) {
        const root = createRoot(container);
        root.render(<History />);
    }
});
