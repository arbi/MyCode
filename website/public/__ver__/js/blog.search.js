var outbound = {
	target: null,
	active: false,

	init: function(element) {
		this.target = $(element);

		// Implement Logic
		this.implementLogic();
	},

	implementLogic: function() {
		var self = this;

		// Highlight input icons
		$('.col input').focus(function() {
			$(this).closest('.col').find('i').addClass('text-primary');
		}).blur(function() {
			$(this).closest('.col').find('i').removeClass('text-primary');
		});
        
		// Detect Destination event
		$('.input-destination a').click(function() {
			$(this).closest('.input-destination').find('input').val(
				$(this).find('span').text()
			);
			$(this).closest('.input-destination').find('input').attr('data-url', $(this).attr('data-url'));
//			$(this).closest('form').find('input[type=submit]').removeAttr('disabled');

			self.active = true;
		});

		// Trigger Search
		this.target.find('input[type=submit]').click(function(e) {
			e.preventDefault();
            if(self.target.find('input[data-toggle=dropdown]').val() == '') {
                self.target.find('input[data-toggle=dropdown]').dropdown('toggle');
                return false;
            }
            
			if (self.active) {
                window.location.href = '/search?city=' + $(this).closest('form').find('.input-destination input').attr('data-url');
			}
		});
	}
};

outbound.init('.search-blog');
