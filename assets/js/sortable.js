/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(function ($) {
	$( "#the-list" ).sortable({
		axis: 'y',
		placeholder: "pe-ptc-highlight",
		update: function (event, ui) {
			var post_data = $( this ).sortable( 'serialize' /*, { key: "post" }*/ ),
				class_name,
				taxonomy_name,
				$post_type,
				$taxonomy
			;

			class_name = $( 'body' ).attr( "class" ).match( /post-type-[\w-]*\b/ );
			taxonomy_name = $( 'body' ).attr( "class" ).match( /taxonomy-[\w-]*\b/ );

			$post_type = class_name[0].replace( 'post-type-', '' );

			if( taxonomy_name )
			{
				$taxonomy = taxonomy_name[0].replace( 'taxonomy-', '' );
			}
			else
			{
				$taxonomy = '';
			}

			var data = {
				action: 'pe_ptc_sort_posts',
				post_type: $post_type,
				taxonomy: $taxonomy,
				post_data: post_data
			};

			// POST to server using $.post or $.ajax
			$.ajax({
				data: data,
				type: 'POST',
				url: ajaxurl
			});
		}
	});
	$( "#the-list" ).disableSelection();
});
