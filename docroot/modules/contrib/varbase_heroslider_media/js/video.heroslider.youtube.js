/**
 * @file
 * Behaviors of Varbase hero slider media for Youtube video scripts.
 */

(function($, Drupal) {
  Drupal.behaviors.varbaseHeroSliderMedia_youtube = {
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
        ).find('.varbase-video-player iframe[src*="youtube.com"]', context);
        if (currentVideo.length > 0) {
          currentVideo.get(0).contentWindow.postMessage("pause", "*");
        }
      });

      // On after slide change.
      mediaSliders.on("afterChange", function(event, slick, currentSlide) {
        const currentVideo = $(
          ".slide--" + currentSlide + ".slick-active"
        ).find('.varbase-video-player iframe[src*="youtube.com"]', context);
        if (currentVideo.length > 0) {
          currentVideo.get(0).contentWindow.postMessage("play", "*");
        } else {
          $(".slick__slider").slick("slickPlay");
        }
      });

      // On first slide load.
      const firstIframeVideo = $(".varbase-heroslider-media")
        .find(".slide")
        .first()
        .find('.varbase-video-player iframe[src*="youtube.com"]', context);
      if (firstIframeVideo.length > 0) {
        firstIframeVideo.on("load", function() {
          $(".slick__slider").slick("slickPause");
          $(this)
            .get(0)
            .contentWindow.postMessage("play", "*");
        });
      }

      function youtubeActionProcessor(e) {
        if (e.data === "endedYoutube" || e.message === "endedYoutube") {
          $(".slick__slider").slick("slickNext");
        } else if (
          e.data === "playingYoutube" ||
          e.message === "playingYoutube"
        ) {
          $(".slick__slider").slick("slickPause");
        }
      }

      // Setup the event listener for messaging.
      if (window.addEventListener) {
        window.addEventListener("message", youtubeActionProcessor, false);
      } else {
        window.attachEvent("onmessage", youtubeActionProcessor);
      }
    }
  };
})(window.jQuery, window.Drupal);
