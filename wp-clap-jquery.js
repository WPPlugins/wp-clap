(function() {

function clap(wpurl, post_id, loading) {
	var url = wpurl + '?action=clp_ajax&clapped=done&post_id=' + post_id;
	runChange(url, post_id, loading);
}

function runChange(url, post_id, loading) {
	jQuery.ajax({
		type:         'GET'
		,url:         url
		,cache:       false
		,dataType:    'html'
		,contentType: 'application/json; charset=utf-8'

		,beforeSend: function(data){
			document.body.style.cursor = 'wait';
			jQuery('#wp_clap_do_' + post_id).html('<span class="clp_ajax_loader">' + ((loading == undefined) ? 'Loading...' : loading + '...') + '</span>');
		}

		,success: function(data){
			jQuery('#wp_clap_' + post_id).animate({opacity: 'hide'}, function() {
				jQuery(this).html(data).fadeIn();
			});
			document.body.style.cursor = 'auto';
		}

		,error: function(data){
			alert('Oops, failed to load data.');
		}
	});
}

window['ClpJS'] = {};
window['ClpJS']['clap'] = clap;

})();
