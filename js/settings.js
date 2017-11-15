jQuery( document ).ready( function( $ ) {
	if ( window.fobit_settings ) {
		var investorTypeIndex = 0;

		var addInvestorType = function( investorType, focus ) {
			var investorTypeContainer = $( '<div/>' )
				.addClass( 'fobit-investor-type' )
				.append( $( '<input/>' )
					.addClass( 'fobit-investor-type-code' )
					.attr( 'name', 'fobit_investor_types[' + investorTypeIndex + '][code]' )
					.attr( 'type', 'text' )
					.attr( 'placeholder', fobit_settings.i18n.investor_type_code )
					.attr( 'required', 'required' )
					.val( investorType ? investorType.code : null )
				)
				.append( $( '<input/>' )
					.addClass( 'fobit-investor-type-description' )
					.attr( 'name', 'fobit_investor_types[' + investorTypeIndex + '][description]' )
					.attr( 'type', 'text' )
					.attr( 'placeholder', fobit_settings.i18n.investor_type_description )
					.attr( 'required', 'required' )
					.val( investorType ? investorType.description : null )
				)
				.append( $( '<input/>' )
					.addClass( 'fobit-investor-type-entrance-commision' )
					.attr( 'name', 'fobit_investor_types[' + investorTypeIndex + '][entrance_commision]' )
					.attr( 'type', 'number' )
					.attr( 'step', '0.01' )
					.attr( 'placeholder', fobit_settings.i18n.investor_type_entrance_commision )
					.attr( 'required', 'required' )
					.val( investorType ? investorType.entrance_commision : null )
				)
				.append( $( '<input/>' )
					.addClass( 'fobit-investor-type-profit-commision' )
					.attr( 'name', 'fobit_investor_types[' + investorTypeIndex + '][profit_commision]' )
					.attr( 'type', 'number' )
					.attr( 'step', '0.01' )
					.attr( 'placeholder', fobit_settings.i18n.investor_type_profit_commision )
					.attr( 'required', 'required' )
					.val( investorType ? investorType.profit_commision : null )
				)
				.append( $( '<a/>' )
					.addClass( 'button-secondary' )
					.text( fobit_settings.i18n.investor_type_remove )
					.click( function( e ) {
						e.preventDefault();

						$( this ).closest( '.fobit-investor-type' ).remove();
					} )
				);

			$( '#fobit_investor_types' ).append( investorTypeContainer );

			if ( focus ) {
				investorTypeContainer.find( 'input' )[0].focus();
			}

			investorTypeIndex++;
		};

		$( '#fobit_investor_types_add' ).click( function( e ) {
			e.preventDefault();

			addInvestorType( null, true );
		} );

		for ( var i in fobit_settings.investor_types ) {
			addInvestorType( fobit_settings.investor_types[ i ], false );
		}
	}
} );
