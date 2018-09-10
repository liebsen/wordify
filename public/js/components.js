var reframe_id = '';
var reframe_guid = '';
var beliefs = [];

var root_url = '/';
var base_url = '/';
var img_url = '/img/';
var current_url = location.pathname;
var environment = 'production';
var version = '1.0';
var ga_id = 'UA-38785031-1';

function importJSON(data) {
	window.belief_templates = data.templates;
	window.instructions = data.instructions;
}

var components = {
	hallofrefocuses : function(){
		//helper.is_loading()
		$.post('/api/refocuses',{},function(res){
			//helper.is_loaded()
			console.log(res)
		})
	},
	refocus : function(){
		var now = new Date()

		helper.import('/css/wizard.css?_='+now.getTime(),'link')
		helper.import('/js/instructions.json?_='+now.getTime())
		helper.import('/js/78b2d3.js?_='+now.getTime())
		helper.import('/js/ebb8df.js?_='+now.getTime())	
		
		/*
		$.post('/api/refocus',{},function(res){
			//helper.is_loaded()
			console.log(res)
		})*/
	},
	signup : function(a){
		$(a).html($.templates('#signup').render())
	},
	signin : function(a){
		$(a).html($.templates('#signin').render())
	}
}
