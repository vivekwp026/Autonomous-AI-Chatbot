export const ChatbotRequest = (method, body) => {
    return new Promise(resolve => {
        fetch(wc_cptbotObject.ajaxurl, {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache',
            },
            body: new URLSearchParams({ ...body, nonce:wc_cptbotObject.nonce })
        }).then(response => {
            resolve(response.json());
        });
    });
}
