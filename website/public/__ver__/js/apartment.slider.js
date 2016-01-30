$(function($) {
	$('#gallery-1').royalSlider({
		fullscreen: {
			enabled: true,
			nativeFS: true
		},
		controlNavigation: 'thumbnails',
		autoScaleSlider: true,
		autoScaleSliderWidth: 780,
        autoScaleSliderHeight: 520,
		loop: true,
		imageScaleMode: 'fill',
		navigateByClick: true,
		numImagesToPreload:4,
		arrowsNav:true,
		arrowsNavAutoHide: true,
		arrowsNavHideOnTouch: true,
		keyboardNavEnabled: true,
		fadeinLoadedSlide: true,
		globalCaption: true,
		globalCaptionInside: false,
		thumbs: {
			appendSpan: true,
			firstMargin: true,
			paddingBottom: 4
		}
	});
});
