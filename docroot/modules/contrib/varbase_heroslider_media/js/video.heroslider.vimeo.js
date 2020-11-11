/**
 * @file
 * Behaviors of Varbase hero slider media for vimeo embeded videos scripts.
 */

(function($, Drupal) {
  Drupal.behaviors.varbaseHeroSliderMedia_vimeo = {
    attach: function(context) {
      const mediaSliders = $(
        ".slick--view--varbase-heroslider-media .slick__slider",
        context
      );
      // On before slide change.
      mediaSliders.on("beforeChange", function(
        event,
        slick,
        currentSlide,
        nextSlide
      ) {
        const currentVideo = $(
          ".slide--" + currentSlide + ".slick-active"
        ).find('.varbase-video-player iframe[src*="vimeo.com"]', context);
        if (currentVideo.length > 0) {
          currentVideo.get(0).contentWindow.postMessage("pause", "*");
        }
      });

      // On after slide change.
      mediaSliders.on("afterChange", function(event, slick, currentSlide) {
        const currentVideo = $(
          ".slide--" + currentSlide + ".slick-active"
        ).find('.varbase-video-player iframe[src*="vimeo.com"]', context);
        if (currentVideo.length > 0) {
          currentVideo.get(0).contentWindow.postMessage("play", "*");
        } else {
          mediaSliders.slick("slickPlay");
        }
      });

      // On first slide load.
      const firstIframeVideo = $(".varbase-heroslider-media")
        .find(".slide")
        .first()
        .find('.varbase-video-player iframe[src*="vimeo.com"]', context);
      if (firstIframeVideo.length > 0) {
        firstIframeVideo.on("load", function() {
          if (!firstIframeVideo.hasClass("first-slide-played")) {
            mediaSliders.slick("slickPause");
            $(this)
              .get(0)
              .contentWindow.postMessage("play", "*");
            firstIframeVideo.addClass("first-slide-played");
          }
        });
      }

      function vimeoActionProcessor(e) {
        if (e.data === "endedVimeo" || e.message === "endedVimeo") {
          mediaSliders.slick("slickNext");
        } else if (e.data === "playingVimeo" || e.message === "playingVimeo") {
          mediaSliders.slick("slickPause");
        }
      }

      // Setup the event listener for messaging.
      if (window.addEventListener) {
        window.addEventListener("message", vimeoActionProcessor, false);
      } else {
        window.attachEvent("onmessage", vimeoActionProcessor);
      }
    }
  };
})(window.jQuery, window.Drupal);
