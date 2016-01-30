function populateProvinceOptions($countryID) {
	$.getJSON(getProvinceOptionsURL + '/' + $countryID, function(data) {
	    var html = '';

	    for (var i in data) {
		    var item = data[i];
	        html += '<option value="' + item.id + '">' + item.name + '</option>';
	    }

	    $('select#province_id').html(html);

	    $( "select#province_id" ).trigger('change');
	});
}

function populateCityOptions($provinceID) {
	$.getJSON(getCityOptionsURL + '/' + $provinceID, function(data) {
	    var html = '';

	    for (var i in data) {
		    var item = data[i];
	        html += '<option value="' + item.id + '">' + item.name + '</option>';
	    }

	    $('select#city_id').html(html);
	});
}

if($('#country_id').length > 0){
   $('#country_id').change(function (){
       if(this.value > 0) {
            var currecny = '';
            for(var v in GLOBAL_COUNTRY_CURRENCY){
               var item = GLOBAL_COUNTRY_CURRENCY[v];
               if(item.id == this.value){
                   currecny = item.code;
                   break;
               }
            }

            if(currecny == '') {
                var data = {"status":"error","msg":"This Country has no Currency"};
                $(this).rules('add',{min: 10000000}); 
            } else {
                $(this).rules('add',{min: 1}); 
                var data = {"status":"warning","msg": "The currency of your chosen country is " + currecny + ". Once you hit save it will no longer be possible to change currency."};
            }    
            notification(data);
       }
   });
}

$(function() {
    $('#apartment_location').submit(function (){
        return $('#apartment_location').valid();
    });

	$( "select#country_id" ).change(function() {
		var $countryID = $(this).val();
		populateProvinceOptions($countryID);
	});

	$( "select#province_id" ).change(function() {
		var $provinceID = $(this).val();
		populateCityOptions($provinceID);
	});

	$( "select#building" ).change(function() {
		var $buildingID = $(this).val();
		$.getJSON(getBuildingSectionURL + '/' + $buildingID, function(data) {
			var html = '';
			var select = $('select#building_section');
			if (data.length > 1) {
				select.closest('.form-group').show();
				html += '<option value="0">--Choose Section--</option>';
			} else {
				select.closest('.form-group').hide();
			}

			for (var i in data) {
				var item = data[i];
				html += '<option value="' + item.id + '">' + item.name + '</option>';
			}

			select.html(html);
		});
	});
});
