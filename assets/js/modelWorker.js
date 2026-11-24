importScripts("https://cdn.jsdelivr.net/npm/onnxruntime-web@1.23.2/dist/ort.min.js");

onmessage = (params) => {
    let key = !!params?.data?.length ? params.data[0] : '';
    let onnxModal = !!params?.data?.length ? params.data[1] : '';

    const request = indexedDB.open("E5SmallDB", 1);
    request.onupgradeneeded = () =>
        request.result.createObjectStore("models");
    request.onerror = () => console.error("IndexedDB error");
    request.onsuccess = async () => {
        const db = request.result;
        const tx = db.transaction("models", "readonly");
        const store = tx.objectStore("models");

        const getReq = store.get(key);
        getReq.onsuccess = async () => {
            let modelBuffer = '';
            if (getReq.result) {
                postMessage({ status: 'configuring' });
                modelBuffer = getReq.result;
            } else {
                postMessage({ status: 'downloading' });
                const resp = await fetch(onnxModal);
                const buf = await resp.arrayBuffer();
                const tx2 = db.transaction("models", "readwrite");
                tx2.objectStore("models").put(buf, key);
                modelBuffer = buf;
            }
            postMessage({ status: 'done', buffer: modelBuffer });
        };
    };
}
