// Initialize slider when document is ready
$(document).ready(function() {
    $('.slider-container').slick({
        dots: false,
        infinite: true,
        speed: 800,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 4000,
        arrows: true,
        prevArrow: '<button class="hero-nav hero-prev" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>',
        nextArrow: '<button class="hero-nav hero-next" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>',
        fade: true,
        cssEase: 'ease-in-out',
        pauseOnHover: true,
        pauseOnFocus: true,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    arrows: true,
                    dots: false
                }
            },
            {
                breakpoint: 480,
                settings: {
                    arrows: false,
                    dots: false
                }
            }
        ]
    });

    // Initialize image carousel
    $('.carousel-slider').slick({
        dots: false,
        infinite: true,
        speed: 1000,
        slidesToShow: 4,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 5000,
        arrows: true,
        prevArrow: '<button class="carousel-nav carousel-prev" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>',
        nextArrow: '<button class="carousel-nav carousel-next" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>',
        pauseOnHover: true,
        pauseOnFocus: true,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
        ]
    });
});
