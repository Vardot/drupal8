/**
 * @file
 * Behaviors of Varbase hero slider media for local video scripts.
 */

(function($, Drupal) {
  Drupal.behaviors.varbaseHeroSliderMedia_local_video = {
    attach: function(context) {
      $(window).on("load", function() {
        // On before slide change.
        $(".slick--view--varbase-heroslider-media .slick__slider", context).on(
          "beforeChange",
          function(event, slick, currentSlide, nextSlide) {
            const currentSlideObject = $(
              ".slide--" + currentSlide + ".slick-active"
            );
            const nextSlideObject = $(".slide--" + nextSlide);
            const currentVideo = currentSlideObject.find(
              ".varbase-video-player video",
              context
            );
            const nextVideo = nextSlideObject.find(
              ".varbase-video-player video",
              context
            );

            if (currentVideo.length > 0) {
              const currentPlayer = currentVideo.get(0);
              currentPlayer.pause();
            }

            if (nextVideo.length > 0) {
              const nextPlayer = nextVideo.get(0);
              nextPlayer.muted = true;
              nextPlayer.onpause = onPause;
              nextPlayer.onended = onFinish;
              nextPlayer.onplay = onPlayProgress;
              nextPlayer.play();
            } else {
              $(".slick__slider").slick("slickPlay");
            }
          }
        );

        // When first slide has a video (Pause the slider and play the video).
        $(".slick--view--varbase-heroslider-media", context).each(function() {
          const firstVideo = $(this)
            .find(".slide.slick-active")
            .find(".varbase-video-player video", context);

          if (firstVideo.length > 0) {
            $(".slick__slider").slick("slickPause");

            const firstVideoPlayer = firstVideo.get(0);
            firstVideoPlayer.muted = true;
            firstVideoPlayer.play();

            firstVideo.on("ended", function() {
              $(".slick__slider").slick("slickPlay");
            });
          }
        });

        // Local Video variable.
        if (
          $(
            ".slick--view--varbase-heroslider-media .varbase-video-player video",
            context
          ).length > 0
        ) {
          const player = $(
            ".slick--view--varbase-heroslider-media .varbase-video-player video",
            context
          ).get(0);

          // When the player is ready, add listeners for pause, finish,
          // and playProgress.
          player.onpause = onPause;
          player.onended = onFinish;
          player.onplay = onPlayProgress;
        }

        // Play when paused.
        function onPause() {
          $(".slick__slider").slick("slickNext");
        }

        // Play when finished.
        function onFinish() {
          $(".slick__slider").slick("slickPlay");
        }

        // Pause on play prgress.
        function onPlayProgress() {
          $(".slick__slider").slick("slickPause");
        }
      });
    }
  };
})(window.jQuery, window.Drupal);
