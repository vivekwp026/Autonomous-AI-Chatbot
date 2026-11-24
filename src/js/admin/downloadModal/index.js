document.addEventListener('DOMContentLoaded', () => {
    const MAX_RETRY = 3;
    let retry = 1;
    let percent = 0;

    const retryBtn = document.getElementById('wc-cptbot-download-model');


    const showNotice = (msg, type = 'success', dismissible = false) => {
        const noticeArea = document.getElementById('wc-cptbot-notice-area');
        if (!!noticeArea) {

            noticeArea.innerHTML = `
        <div class="wc-cptbot-notice wc-cptbot-${type} ${dismissible ? 'is-dismissible' : ''}">
            <p>${msg}</p>
            ${dismissible ? '<button type="button" class="wc-cptbot-notice-dismiss notice-dismiss"></button>' : ''}
        </div>
    `;
        }
    };

    const downloadModal = async () => {
        try {
            const params = new URLSearchParams();
            params.append('action', 'wc_cptbot_download_model');
            params.append('nonce', wc_cptbot_download_modal.nonce);
            if (0 >= percent) {
                showNotice(wc_cptbot_download_modal.translation.waiting, 'info', false);
            }
            const response = await fetch(wc_cptbot_download_modal.ajax_url, {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                method: 'POST',
                credentials: 'same-origin',
                body: params.toString()
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result?.message);
            }

            if (! !!result.data?.is_completed) {
                const { offset, total } = result.data || {};
                if (total > 0) {
                    percent = Math.floor((offset / total) * 100);
                }
                showNotice(wc_cptbot_download_modal.translation.downloading + percent + '%', 'info', false);
                downloadModal();
            } else {

                showNotice(wc_cptbot_download_modal.translation.already_done, 'success', true);

            }
        } catch (error) {
            if (retry <= MAX_RETRY) {
                downloadModal();
                retry++;

            } else {
                showNotice(wc_cptbot_download_modal.translation.download_failed + (error?.message ? `${error.message}` : ''), 'error', true);
                if (!!retryBtn) {
                    retryBtn.style.display = 'block';
                }

            }
        }
    }

    if (!!retryBtn) {
        retryBtn.style.display = 'none';
    }


    if (wc_cptbot_download_modal?.start_download) {
        downloadModal();
    }



    if (!!retryBtn) {
        retryBtn.addEventListener('click', () => {
            retry = 1;
            retryBtn.style.display = 'none';
            let notice = 0 < percent ? wc_cptbot_download_modal.translation.downloading + percent + '%' : wc_cptbot_download_modal.translation.waiting;
            showNotice(notice, 'info', false);
            downloadModal();
        });
    }
});

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('wc-cptbot-notice-dismiss')) {
        e.target.closest('.wc-cptbot-notice').remove();
    }
});
