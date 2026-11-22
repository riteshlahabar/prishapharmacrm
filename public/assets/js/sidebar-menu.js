(function ($) {
  $(".toggle-nav").click(function () {
    $("#sidebar-links .nav-menu").css("left", "0px");
  });
  $(".mobile-back").click(function () {
    $("#sidebar-links .nav-menu").css("left", "-410px");
  });
  // Prisha patch (see docs/ui-theme-patches.md): the stock lines interpolated the
  // stored layout straight into the class attribute, so a first visit -- with
  // nothing in localStorage -- rendered class="page-wrapper null" and then
  // appended the default. Same result, without the junk class in the DOM.
  var savedLayout = localStorage.getItem("page-wrapper-cuba");
  $(".page-wrapper").attr("class", "page-wrapper " + (savedLayout || "compact-wrapper"));

  // left sidebar and vertical menu
  if ($("#pageWrapper").hasClass("compact-wrapper")) {
    jQuery(".sidebar-title").append('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
    jQuery(".sidebar-title").click(function () {
      jQuery(".sidebar-title").removeClass("active").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      jQuery(".sidebar-submenu, .menu-content").slideUp("normal");
      jQuery(".menu-content").slideUp("normal");
      if (jQuery(this).next().is(":hidden") == true) {
        jQuery(this).addClass("active");
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
        jQuery(this).next().slideDown("normal");
      } else {
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      }
    });
    jQuery(".sidebar-submenu, .menu-content").hide();
    jQuery(".submenu-title").append('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
    jQuery(".submenu-title").click(function () {
      jQuery(".submenu-title").removeClass("active").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      jQuery(".sidebar-submenu li .sidebar-submenu").slideUp("normal");
      if (jQuery(this).next().is(":hidden") == true) {
        jQuery(this).addClass("active");
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
        jQuery(this).next().slideDown("normal");
      } else {
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      }
    });
    jQuery(".sidebar-submenu li .sidebar-submenu").hide();
  } else if ($("#pageWrapper").hasClass("horizontal-wrapper")) {
    var smallSize = false,
      bigSize = false;
    const horizontalMenu = () => {
      var contentwidth = jQuery(window).width();
      if (contentwidth <= 992 && !smallSize) {
        (smallSize = true), (bigSize = false);
        $("#pageWrapper").removeClass("horizontal-wrapper").addClass("compact-wrapper");
        $(".page-body-wrapper").removeClass("horizontal-menu").addClass("sidebar-icon");
        jQuery(".submenu-title").append('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
        jQuery(".submenu-title").click(function () {
          jQuery(".submenu-title").removeClass("active");
          jQuery(".submenu-title").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
          jQuery(".sidebar-submenu li .sidebar-submenu").slideUp("normal");
          if (jQuery(this).next().is(":hidden") == true) {
            jQuery(this).addClass("active");
            jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
            jQuery(this).next().slideDown("normal");
          } else {
            jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
          }
        });
        jQuery(".sidebar-submenu li .sidebar-submenu").hide();

        jQuery(".sidebar-title").append('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
        jQuery(".sidebar-title").click(function () {
          jQuery(".sidebar-title").removeClass("active");
          jQuery(".sidebar-title").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
          jQuery(".sidebar-submenu, .menu-content").slideUp("normal");
          if (jQuery(this).next().is(":hidden") == true) {
            jQuery(this).addClass("active");
            jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
            jQuery(this).next().slideDown("normal");
          } else {
            jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
          }
        });
        jQuery(".sidebar-submenu, .menu-content").hide();
      }
      if (contentwidth > 992 && !bigSize) {
        (smallSize = false), (bigSize = true);
        $("#pageWrapper").removeClass("compact-wrapper").addClass("horizontal-wrapper");
        jQuery(".sidebar-title .according-menu").remove();
      }
    };
    horizontalMenu();
    addEventListener("resize", (event) => {
      horizontalMenu();
    });
  } else if ($("#pageWrapper").hasClass("compact-sidebar")) {
    var contentwidth = jQuery(window).width();
    if (contentwidth > 992) {
      $('<div class="bg-overlay1"></div>').appendTo($("body"));
    }

    jQuery(".sidebar-title").click(function () {
      jQuery(".sidebar-title").removeClass("active");
      $(".bg-overlay1").removeClass("active");
      jQuery(".sidebar-submenu").removeClass("close-submenu").slideUp("normal");
      jQuery(".sidebar-submenu, .menu-content").slideUp("normal");
      jQuery(".menu-content").slideUp("normal");

      if (jQuery(this).next().is(":hidden") == true) {
        jQuery(this).addClass("active");
        jQuery(this).next().slideDown("normal");
        $(".bg-overlay1").addClass("active");

        $(".bg-overlay1").on("click", function () {
          jQuery(".sidebar-submenu, .menu-content").slideUp("normal");
          $(this).removeClass("active");
        });
      }
      if (contentwidth < "992") {
        $(".bg-overlay").addClass("active");
      }
    });
    jQuery(".sidebar-submenu, .menu-content").hide();
    jQuery(".submenu-title").append('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
    jQuery(".submenu-title").click(function () {
      jQuery(".submenu-title").removeClass("active").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      jQuery(".sidebar-submenu li .sidebar-submenu").slideUp("normal");
      if (jQuery(this).next().is(":hidden") == true) {
        jQuery(this).addClass("active");
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
        jQuery(this).next().slideDown("normal");
      } else {
        jQuery(this).find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-right"></i></div>');
      }
    });
    jQuery(".sidebar-submenu li .sidebar-submenu").hide();
  }

  // toggle sidebar
  $nav = $(".sidebar-wrapper");
  $header = $(".page-header");
  $toggle_nav_top = $(".toggle-sidebar");
  $toggle_nav_top.click(function () {
    $nav.toggleClass("close_icon");
    $header.toggleClass("close_icon");
    $(window).trigger("overlay");
  });

  $(window).on("overlay", function () {
    $bgOverlay = $(".bg-overlay");
    $isHidden = $nav.hasClass("close_icon");
    if ($(window).width() <= 1184 && !$isHidden && $bgOverlay.length === 0) {
      $('<div class="bg-overlay active"></div>').appendTo($("body"));
    }

    if ($isHidden && $bgOverlay.length > 0) {
      $bgOverlay.remove();
    }
  });

  $(".sidebar-wrapper .back-btn").on("click", function (e) {
    $(".page-header").toggleClass("close_icon");
    $(".sidebar-wrapper").toggleClass("close_icon");
    $(window).trigger("overlay");
  });

  $("body").on("click", ".bg-overlay", function () {
    $header.addClass("close_icon");
    $nav.addClass("close_icon");
    $(this).remove();
  });

  $body_part_side = $(".body-part");
  $body_part_side.click(function () {
    $toggle_nav_top.attr("checked", false);
    $nav.addClass("close_icon");
    $header.addClass("close_icon");
  });

  //    responsive sidebar
  var $window = $(window);
  var widthwindow = $window.width();
  (function ($) {
    "use strict";
    if (widthwindow <= 1184) {
      $toggle_nav_top.attr("checked", false);
      $nav.addClass("close_icon");
      $header.addClass("close_icon");
    }
  })(jQuery);
  $(window).resize(function () {
    var widthwindaw = $window.width();
    if (widthwindaw <= 1184) {
      $toggle_nav_top.attr("checked", false);
      $nav.addClass("close_icon");
      $header.addClass("close_icon");
    } else {
      $toggle_nav_top.attr("checked", true);
      $nav.removeClass("close_icon");
      $header.removeClass("close_icon");
      jQuery(".sidebar-submenu, .menu-content").hide();
    }
  });

  // horizontal arrows
  var view = $("#sidebar-menu");
  var move = "500px";
  var leftsideLimit = -500;

  var getMenuWrapperSize = function () {
    return $(".sidebar-wrapper").innerWidth();
  };
  var menuWrapperSize = getMenuWrapperSize();

  if (menuWrapperSize >= "1660") {
    var sliderLimit = -4680;
  } else if (menuWrapperSize >= "1440") {
    var sliderLimit = -5500;
  } else {
    var sliderLimit = -5600;
  }

  $("#left-arrow").addClass("disabled");
  $("#right-arrow").click(function () {
    var currentPosition = parseInt(view.css("marginLeft"));
    if (currentPosition >= sliderLimit) {
      $("#left-arrow").removeClass("disabled");
      view.stop(false, true).animate(
        {
          marginLeft: "-=" + move,
        },
        {
          duration: 400,
          // complete: function () {
          //   if (parseInt(view.css("marginLeft")) == -4500) {
          //     $("#right-arrow").addClass("disabled");
          //   }
          // },
        }
      );
      if (currentPosition == sliderLimit) {
        $(this).addClass("disabled");
        console.log("sliderLimit", sliderLimit);
      }
    }
  });

  $("#left-arrow").click(function () {
    var currentPosition = parseInt(view.css("marginLeft"));
    if (currentPosition < 0) {
      view.stop(false, true).animate(
        {
          marginLeft: "+=" + move,
        },
        {
          duration: 400,
        }
      );
      $("#right-arrow").removeClass("disabled");
      $("#left-arrow").removeClass("disabled");
      if (currentPosition >= leftsideLimit) {
        $(this).addClass("disabled");
      }
    }
  });

  // page active
  // Prisha patch (see docs/ui-theme-patches.md): the stock theme cleared every
  // .active class before looking for a match, and only an href equal to the
  // current path counts as one. On a detail or form page -- /visits/5,
  // /persons/3/edit -- nothing matches, so the section link rendered active by
  // Blade lost its highlight and the scroll-into-view below was left with no
  // target. Now the takeover only happens when a link really does match.
  if ($("#pageWrapper").hasClass("compact-wrapper")) {
    var current = window.location.pathname;
    var exactLink = $(".sidebar-wrapper nav ul li a")
      .filter(function () {
        var link = $(this).attr("href");
        return link ? new URL(link, window.location.origin).pathname === current : false;
      })
      .first();

    if (exactLink.length) {
      $(".sidebar-wrapper nav").find("a").removeClass("active");
      $(".sidebar-wrapper nav").find("li").removeClass("active");

      exactLink.parents().children("a").addClass("active");
      exactLink.parents().parents().children("ul").css("display", "block");
      exactLink.addClass("active");
      exactLink.parent().parent().parent().children("a").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
      exactLink.parent().parent().parent().parent().parent().children("a").find("div").replaceWith('<div class="according-menu"><i class="fa-solid fa-angle-down"></i></div>');
    }
  }

  $(".left-header .mega-menu .nav-link").on("click", function (event) {
    event.stopPropagation();
    $(this).parent().children(".mega-menu-container").toggleClass("show");
  });

  $(".left-header .level-menu .nav-link").on("click", function (event) {
    event.stopPropagation();
    $(this).parent().children(".header-level-menu").toggleClass("show");
  });

  $(document).click(function () {
    $(".mega-menu-container").removeClass("show");
    $(".header-level-menu").removeClass("show");
  });

  $(window).scroll(function () {
    var scroll = $(window).scrollTop();
    if (scroll >= 50) {
      $(".mega-menu-container").removeClass("show");
      $(".header-level-menu").removeClass("show");
    }
  });

  $(".left-header .level-menu .nav-link").click(function () {
    if ($(".mega-menu-container").hasClass("show")) {
      $(".mega-menu-container").removeClass("show");
    }
  });

  $(".left-header .mega-menu .nav-link").click(function () {
    if ($(".header-level-menu").hasClass("show")) {
      $(".header-level-menu").removeClass("show");
    }
  });

  $(document).ready(function () {
    $(".outside").click(function () {
      $(this).find(".menu-to-be-close").slideToggle("fast");
    });
  });
  $(document).on("click", function (event) {
    var $trigger = $(".outside");
    if ($trigger !== event.target && !$trigger.has(event.target).length) {
      $(".menu-to-be-close").slideUp("fast");
    }
  });

  $(".left-header .link-section > div").on("click", function (e) {
    if ($(window).width() <= 1199) {
      $(".left-header .link-section > div").removeClass("active");
      $(this).toggleClass("active");
      $(this).parent().children("ul").toggleClass("d-block").slideToggle();
    }
  });

  if ($(window).width() <= 1199) {
    $(".left-header .link-section").children("ul").css("display", "none");
    $(this).parent().children("ul").toggleClass("d-block").slideToggle();
  }

  // active link
  // Prisha patch (see docs/ui-theme-patches.md): the stock guard tested a jQuery
  // object, which is truthy even when it matched nothing, then read .offset()
  // off an empty set. With no active link in the scroller -- any page whose
  // URL is not a sidebar href -- that threw "Cannot read properties of
  // undefined (reading 'top')" on every load. Test the link we actually need.
  var activeSidebarLink = $(".simplebar-wrapper .simplebar-content-wrapper a.active");
  if (activeSidebarLink.length && $("#pageWrapper").hasClass("compact-wrapper")) {
    $(".simplebar-wrapper .simplebar-content-wrapper").animate(
      {
        scrollTop: activeSidebarLink.offset().top - 400,
      },
      1000
    );
  }
})(jQuery);
