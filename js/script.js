(function ($){
  'use strict';
  $(document).ready(function($){

    var $uploadButton = $('.ptam-upload-btn');

    if ( ! $uploadButton.length ) {
      return;
    }

    var $deleteButton = $('.ptam-delete-btn');

    $deleteButton.each( function(){
        var $this = $(this);
        if ( ! $this.siblings('.ptam-image-id').val() ) {
          $this.hide();
        }
    });

    $uploadButton.on( 'click', function(e) {
      e.preventDefault();
      var $this = $(this);
      var image = wp.media({
        title: 'Post Type Archive Meta Upload Image',
        multiple: false
      }).open()
      .on('select', function(e){
        var uploadedImage = image.state().get('selection').first();
        var imageObj = uploadedImage.toJSON();

        var imageHtml = '<img src="' + imageObj.url  + '" />';
        $this.siblings('.ptam-image-area').html(imageHtml);
        $this.siblings('.ptam-image-id').val(imageObj.id);
        $deleteButton.show();
      });
    });

    $deleteButton.on( 'click', function(e) {
      e.preventDefault();
      var $this = $(this);
      $this.siblings('.ptam-image-area').html('');
      $this.siblings('.ptam-image-id').val('');
      $this.hide();
    });
  });
}(jQuery));
