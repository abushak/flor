(function ($) {

  "use strict";

/*
  Drupal.behaviors.paragraphsAccordion = {
    attach: function (context, settings) {


      $('#block-wellbeing-content .field--name-field-hideable-text').hide(500);

      $('#block-wellbeing-content .field--name-field-heading-text').unbind('click').click(function(){
        //$(this).siblings('.field--name-field-hideable-text').toggle(500);
        console.log($(this).siblings('.field-content'));
        console.log($(this).siblings('.field-content'));
        if($(this).hasClass("accordion-open")){
        $(this).siblings('.field-content').toggle(500);
        $(this).toggleClass("accordion-open");

        }else{
          $(".accordion-open ").click();
          $(this).siblings('.field--name-field-hideable-text').toggle(500);
          $(this).toggleClass("accordion-open");
        }
      });
 
    }
  };
  //add a class to the page based on menu branch.
 /* Drupal.behaviors.setBlockHeights = {
    attach: function (context, settings) {

 

    }
  };*/
  
  Drupal.behaviors.collapsiblevies = {
    attach: function (context, settings) {
		
	jQuery(document).ready(function($){

		$('.dropdown-link').click(function(e) {
		  e.preventDefault();
		  var $div = $(this).next('.dropdown-container');
		  $('.dropdown-container').not($div).hide();
			if ($div.is(":visible")) {
				$div.hide()
			}  else {
			   $div.show();
			}
		});

		$(document).click(function(e){
			var p = $(e.target).closest('.dropdown').length
			if (!p) {
				  $('.dropdown-container').hide();
			}
		});
		 
	});
	 
	
  }}


}(jQuery));
