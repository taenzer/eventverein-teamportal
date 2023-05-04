
/*

@brief Partner Select Handler
@author TNZ | Moritz Tanz
@version 1.0
@modified 11.01.2023


Dieses Skript ermöglicht das auswählen des Partners auf verschiedenen Transaktions-
Seiten. Dazu müssen vorher ebenfalls folgende Scripte geladen werden:
- <script src="//code.jquery.com/jquery-1.12.4.js"></script>
- <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

Das HTML Element zum Auswählen des Partners muss so aussehen (entscheidend sind die IDs):

<label for="partner">Partner</label>
<div class="partner-input ui-front">
  <input type="text" name="partner" data-id="" id="partner" value="" required>
  <input type="hidden" id="partner_exist" name="partner_exist" value="">
</div>


*/


$(function() {
    function log( message ) {
      $( "<div>" ).text( message ).prependTo( "#log" );
      $( "#log" ).scrollTop( 0 );
    }

    $.widget( "custom.catcomplete", $.ui.autocomplete, {
    _create: function() {
      this._super();
      this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
    },
    _renderMenu: function( ul, items ) {
      var that = this,
        currentCategory = "";
      $.each( items, function( index, item ) {
        var li;
        if ( item.category && item.category != currentCategory ) {
          ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
          currentCategory = item.category;
        }
        li = that._renderItemData( ul, item );
        if ( item.category ) {
          li.attr( "aria-label", item.category + " : " + item.label );
        }

        if(item.class){
          li.addClass(item.class);
        }
      });
    },
    _renderItem: function(ul, item){
      var build = $( "<li>" )
        .attr( "data-value", item.id )
        .append( item.label );

      if(item.subt){
        build.append("<span class='subt'>" + item.subt + "</span>");
      }

      return build.appendTo( ul );
    }
  });

    $( "#partner" ).catcomplete({
      source: function( request, response ) {
        console.log("getting data...");
        $.ajax({
          url: "https://team.eventverein.de/ajax",
          dataType: "json",
          data: {
            action: "getPartners",
            q: request.term,
            nonce: "nonce" // TODO: NOCE
          },
          success: function( data ) {
            response( data );
          },
        });
      },
      minLength: 1,
      select: function( event, ui ) {
        $("#partner_exist").attr("value", ui.item.id ? ui.item.id : "");
      },
      open: function() {
        $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
      },
      close: function() {
        $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
      }
    });
    document.dispatchEvent(new Event("autocompleteLoaded"))
  });
