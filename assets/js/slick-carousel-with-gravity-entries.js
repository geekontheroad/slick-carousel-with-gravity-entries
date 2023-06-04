jQuery(document).ready(function(){
	jQuery('.gotr-rating-carousel').slick({
	infinite: true,   
    adaptiveHeight: true,   
    centerMode: false,
	slidesToShow: 3,
	slidesToScroll: 3,			
	dots: true,
	responsive: [
		{
		  breakpoint: 1024,
		  settings: {
			slidesToShow: 3,
			slidesToScroll: 3,			
			dots: true
		  }
		},
		{
		  breakpoint: 900,
		  settings: {
			slidesToShow: 2,
			slidesToScroll: 2,
			dots: false,
		  }
		},
		{
		  breakpoint: 600,
		  settings: {
			slidesToShow: 1,
			slidesToScroll: 1,
			dots: false,
		  }
		}
	]
  });
});