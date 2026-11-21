(function ($) {
  if ($(".notification-slider").length) {
    $(".notification-slider").slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      dots: false,
      vertical: true,
      variableWidth: false,
      autoplay: true,
      autoplaySpeed: 1000,
      arrows: false,
    });
  }
})(jQuery);
