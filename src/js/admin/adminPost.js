const cptbotPost = jQuery.noConflict();
const PROCESSING_TRANSLATION = wc_cptbot_post.processing;
const COMPLETED_TRANSLATION = wc_cptbot_post.complete;

document.addEventListener('DOMContentLoaded', () => {
    let chatgpt_desc = document.querySelectorAll('.chatgpt_desc');
    chatgpt_desc.forEach(desc_btn => {
        desc_btn.addEventListener('click', (e) => {
            e.preventDefault();
            let product_id = e.target.getAttribute('data-id');
            let length = e.target.getAttribute('data-length');
            let type = e.target.getAttribute('data-type');

            cptbotPost.ajax({
                type: "POST",
                url: wc_cptbot_post.admin_ajax,
                data: {
                    'action': 'wc_cptbot_generate_product_description',
                    'nonce': wc_cptbot_post.nonce,
                    'product_id': product_id,
                    'length': length,
                    'type': type,
                },
                beforeSend: function () {
                    e.target.innerHTML = PROCESSING_TRANSLATION;
                    e.target.disabled = true;
                },
                success: function (response) {
                    if (type == "excerpt") {
                        let prevexcerpt = tinymce.editors.excerpt.getContent();
                        tinymce.editors.excerpt.setContent(prevexcerpt + response.data);
                    } else {
                        let prevcontent = tinymce.editors.content.getContent();
                        tinymce.editors.content.setContent(prevcontent + response.data);
                    }
                    e.target.innerHTML = COMPLETED_TRANSLATION;
                    e.target.disabled = true;
                }
            });
        });
    });
});
