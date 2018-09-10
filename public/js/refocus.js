$(document).on('keyup change','.belief textarea',function(e){
  var val = $(this).val().trim()
  if(!val){
  	$(this).parent().removeClass('populated')
  } else {
  	$(this).parent().addClass('populated')
  }
})

$(document).on('click','.movie-canvas',function(e){
  if($(e.target).hasClass('movie-canvas')){
    $('.belief').removeClass('edit')
  }
})

$(document).on('click','.belief .save',function(e){
	$('.belief').removeClass('edit')
})

$(document).on('click','.belief textarea',function(e){
  $('.belief').removeClass('edit')
  $(this).parent().addClass('edit')
})