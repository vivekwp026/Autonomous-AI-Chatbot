import { createRoot } from 'react-dom';
import Chatbox from "./component/chatbox.js";

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById('front_chatbox');

    if (container) {
        const root = createRoot(container);
        root.render(<Chatbox />);
    }
});
