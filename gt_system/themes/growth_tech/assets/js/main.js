window.addEventListener("load", function () {


	jQuery('.menu-item').mouseover(function () {
		if (jQuery(this).hasClass('services-sub-menu')) {
			jQuery(this).addClass('active');
			jQuery('.header-desktop').addClass('sticky');
			jQuery('.c-mega-menu.services-sub-menu').addClass('open');
		} else {
			if (jQuery(".services-sub-menu:hover").length == 0) {
				jQuery('.header-desktop').removeClass('sticky');
				jQuery('.c-mega-menu.services-sub-menu').removeClass('open');
			}
		}

	});

	jQuery("body").mouseover(function () {
		if (jQuery(".services-sub-menu:hover").length == 0 && jQuery(".header-desktop > .container:hover").length == 0 && jQuery('.c-mega-menu.services-sub-menu:hover').length == 0) {
			jQuery('.c-mega-menu.services-sub-menu').removeClass('open');
			jQuery('.header-desktop').removeClass('sticky');
		}
	});

		jQuery(".usp_box ul li").click(function () {
			jQuery(this).find('.info_box').slideToggle();
		});

		jQuery(".close").click(function () {
			jQuery(this).find('.info_box').hide();
		});


	jQuery('.close_menu').click(function (e) {
		e.preventDefault();
		jQuery('.header-desktop').removeClass('sticky');
		jQuery('.c-mega-menu').removeClass('open');
	});

	/* Mobile */
	jQuery('.navbar-toggler').click(function (e) {
		e.preventDefault();
		jQuery('.navbar').toggleClass('open');
		jQuery('.page-header').toggleClass('z-index-0');
	});

	jQuery('.menu-item-has-children a').click(function (e) {
		//e.preventDefault();
		jQuery('.sub-menu').slideToggle(200);
	});


	/*** -- Scroll animation -- ***/
	function scrollInit() {
		//Display animted on scroll elements if they are within the viewport on load
		let window_bottom = jQuery(window).scrollTop() + jQuery(window).height();
		jQuery('.scroll-animated').each(function () {
			if (jQuery(this).offset().top <= window_bottom) {
				jQuery(this).addClass('animate');
			}
		});
	}
	scrollInit();

	jQuery(window).scroll(function (e) {
		//Animate in elements with the "scroll-animated" class
		let window_bottom = jQuery(window).scrollTop() + jQuery(window).height();

		jQuery('.scroll-animated').each(function () {
			if (jQuery(this).offset().top < window_bottom) {
				jQuery(this).addClass('animate');
			} else if (jQuery(this).offset().top > window_bottom) {
				jQuery(this).removeClass('animate');
			}
		});
	});
	/*** -- END Scroll animation -- ***/

	/*** -- Mega menu -- ***/
	let desktop_header = document.querySelector(".header-desktop");
	let mega_menu = document.querySelector(".mega-menu");
	if (mega_menu) {
		let menu_button = desktop_header.querySelector(".navbar-toggler-icon");
		if (menu_button) {
			menu_button.addEventListener("click", function (e) {
				e.preventDefault();
				menu_button.classList.toggle("is-active");
				mega_menu.classList.toggle("active");
			});
		}
	}
	/*** -- END Mega menu -- ***/


	/*** -- Read more -- ***/
	jQuery(".hidden-content").hide();
	jQuery(".show_hide").on("click", function () {
		jQuery(".show_hide").toggleClass('active');
		jQuery(this).parents('.body').find('.hidden-content').slideToggle(200);
	});


	/*** -- END Read more -- ***/
});