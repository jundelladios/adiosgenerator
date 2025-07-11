function AdiosGeneratorInsertMediaDomGrid( element, label, type="lazyloaded" ) {
  jQuery(element).each( function() {
    if( !jQuery(this).find('.attachment-preview').find('.adiosgenerator_media_label').length ) {
      jQuery(this).find(`.attachment-preview`).append(
        `<div class="adiosgenerator_media_label"></div>`
      );
    }

    const insertedDom = jQuery(this).find('.attachment-preview').find('.adiosgenerator_media_label');
    const selector = `adiosgenerator_media_label_${type}`;
    if( !insertedDom.find(`.${selector}`).length ) {
      jQuery(insertedDom).append(
        `<div class="adiosgenerator_media_label_item ${selector}">${label}</div>`
      );
    }
  })
}


jQuery(document).ajaxSuccess(function(event, xhr, settings) {

  if (settings.data && (settings.data.includes('action=query-attachments') || settings.data.includes('action=save-attachment-compat'))) {

    // remove media label on init to ensure refreshnes of data
    jQuery('.adiosgenerator_media_label').remove();

    jQuery.post(adiosgenerator_media.ajax_url, {
      action: 'wp_adiosgenerator_media_statuses',
      security: adiosgenerator_media.nonce
    }, function( response ) {
      if (response.status === 'ok') {
        var AdiosGeneratorMediaLabelData = response.message;

        // for prio loaded
        AdiosGeneratorMediaLabelData.dislazy.map(id => {
          const attachmentElem = jQuery(`.attachments [data-id=${id}]`);
          console.log(attachmentElem)
          AdiosGeneratorInsertMediaDomGrid( attachmentElem, "No Lazyload", "lazyloaded" );
        });

        // for preloaded
        AdiosGeneratorMediaLabelData.preloaded.map(id => {
          const attachmentElem = jQuery(`.attachments [data-id=${id}]`);
          AdiosGeneratorInsertMediaDomGrid( attachmentElem, "Preloaded", "preloaded" );
        });
      }
    })
  
  }
});