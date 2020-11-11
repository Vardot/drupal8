(function ($) {

  $(document).ready(function(){

    /*
      Drag n Drop functionality
      Inspired by https://neliosoftware.com/blog/native-drag-and-drop-with-html5/
     */
    $('.kanban-entry').draggable({
      helper: 'clone'
    });


    $('.kanban-column').droppable({

      accept: '.kanban-entry',
      hoverClass: 'hovering',

      //on drop
      drop: function( ev, ui ) {

        ui.draggable.detach();
        $( this ).append( ui.draggable );

        // Get EntityId and type from draggable object.
        var entityId = $(ui.draggable[0]).data('id');
        var type = $(ui.draggable[0]).data('type');
        // Get state_id from target column.
        var stateID = $(this).data('state_id');

        if (stateID && entityId && type) {
          // Generate URL for AJAX call.
          var url = '/admin/content-kanban/update-entity-workflow-state/' + type +'/' + entityId + '/' + stateID;
          $.ajax({
            'url': url,
            'success': function(result) {
              if(!result.success) {
                alert('Something went wrong: ' + result.message);
              } else {
                alert('Updated.');
              }
            },
            'error': function(xhr, status, error) {
              alert('An error occured during the update of the desired node. Please consult the watchdog.');
            }
          });

        }

      }
    });

    // Instant search for Kanban
    jQuery(".content-kanban-filter-form #edit-search").keyup(function(){
      var divs =  jQuery(".content-kanban-entry");
      if (jQuery(this).val().length >= 2)
      {
        var noElem	 = true;
        var val = jQuery.trim(this.value).toLowerCase();

        el = divs.filter(function() {
          console.log(jQuery(this).clone().children().remove().end().text());
          return jQuery(this).text().toLowerCase().match(val);
        });
        if (el.length >= 1) {
          noElem	 = false;
        }
        divs.not(el).hide();
        el.show();
      } else {
        divs.show();
      }
    });

  });

})(jQuery);
