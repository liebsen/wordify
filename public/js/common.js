if(typeof localStorage === 'object') {
  try {
    localStorage.setItem('localStorage', 1);
    localStorage.removeItem('localStorage');
  } catch(e) {
    Storage.prototype._setItem = Storage.prototype.setItem;
    Storage.prototype.setItem = function() {};
    console.log('Tu navegador no soporta alojamiento local. En Safari, la causa más común de este error es usar "Modo Navegación Privada". Algunas preferencias no podrán ser guardadas y la aplicación no funcionará correctamente.');
  }
}
var cache = {}
var helper = {
  mapbox : {
    accessToken: 'pk.eyJ1IjoibWFydGluZnJlZSIsImEiOiJjams4ZDh0dGoyanRwM3FtbHlqMXJnYjA3In0.VBD8U4yG46py1g9NxBFMPw',
    style: 'mapbox://styles/mapbox/bright-v8'
    //style: 'mapbox://styles/mapbox/dark-v9'
    //style: 'mapbox://styles/mapbox/streets-v10'
  },
  filters : {
    toJSON : function(json){
      return JSON.stringify(json)
    },
    formatDate : function(date){
      return moment(date,'X').format('DD MMM hh:mm');
    },
    base64encode: function(a){
      return Base64.encode(a).split("=").join("-").split("").reverse().join("");
    },
    isHumanTime : function(date){
      return moment(date,'X').fromNow()
    },
    isHumanTime2 : function(date){
      var d = new Date().getTime()
      , hourago = d - (60 * 60 * 1 * 1000) * 4
      , yesterday = d - (60 * 60 * 1 * 1000) * 8
      , dayago = d - (60 * 60 * 24 * 1000) * 24 
      , date = new Date(date).getTime()
      if(date > hourago)
        return moment.utc(date).format("HH:mm")
      if(date > yesterday)
        return moment.utc(date).format("D MMM HH:mm")
      if(date > dayago)
        return moment.utc(date).format("D MMM YY HH:mm")
      return moment.utc(date).startOf('minute').fromNow()
    }   
  },
  import : function(url,type){
    let resource = document.createElement(type||'script');    
    resource.setAttribute('src',url);
    document.head.appendChild(resource);
  },
  is_loading: function(){
    $('.navbar').after('<div class="spinner-outer fadeInFast"><div class="spinner" data-layer="4"><div class="spinner-container"><div class="spinner-rotator"><div class="spinner-left"><div class="spinner-circle"></div></div><div class="spinner-right"><div class="spinner-circle"></div></div></div></div><div class="spinner-message"></div></div></div>')
  },
  is_loaded: function(){
    $('#app .spinner-outer').fadeOut(250,function(){
      $(this).remove()
    })
  },
  playAudio : function(audio) {
    if(audio===undefined) audio = "message";
      var audio = new Audio(helper.getAttributes($('html')).assets + '/audio/' + audio + '.mp3');
      audio.play();
  },
  fixExifOrientation : function(int, element) {
    switch(parseInt(int)) {
      case 2:
        element.parent().addClass('loaded');
        element.addClass('flip');
        break;
      case 3:
        element.parent().addClass('loaded');
        element.addClass('rotate-180');
        break;
      case 4:
        element.addClass('flip-and-rotate-180');
        break;
      case 5:
        element.addClass('flip-and-rotate-270');
        break;
      case 6:
        element.parent().addClass('loaded');
        element.addClass('rotate-90');
        break;
      case 7:
        element.addClass('flip-and-rotate-90');
        break;
      case 8:
        element.addClass('rotate-270');
        break;
    }
  },
  showTick : function() {
    $(".tick-asset").css({
      'display': 'block'
    });
    $(".trigger").addClass("drawn");
    setTimeout(function() {
      $(".trigger").removeClass("drawn");
    }, 2000);
  },  
  breadcrumb : function(data){
    $('.navbar-item.has-dropdown .navbar-link:first-child').html($.templates('#breadcrumb').render(data))
  },
  getFormData : function($form){
    var unindexed_array = $form.serializeArray();
    var indexed_array = {};

    $.map(unindexed_array, function(n, i){
      indexed_array[n['name']] = n['value'];
    });

    return indexed_array;
  },
  getAttributes : function ( node ) {
    var attrs = {};
    $.each( node[0].attributes, function ( index, attribute ) {
        attrs[attribute.name] = attribute.value;
    } );
    return attrs;
  },
  getToken : function() {
    return this.champ('token').token || "";
  },
  verifyStatus : function(){
    $.server({
      url: '/token',
      success: function(res){
        if(res.data){
          localStorage.setItem("token", JSON.stringify(res.data))
        }
      },
      error : function(){
        //helper.sessionExpired()
      }
    })    
  },
  verifyResponse : function(res){
    if(res.data.status && res.data.status != 'success'){
      swal({
        type: 'error',
        title: 'Algo no anduvo de la manera que se esperaba',
        html : 'Una operación falló. Gracias por darte cuenta lo corregiremos en breve. Intenta nuevamente en unos minutos.'
      })
    }
  },
  verifyFailResponse : function(res){
    if(res.status === 401){
      this.sessionExpired()
    }
  },
  sessionExpired : function(){
    localStorage.removeItem("token")
    console.log("expired")
    helper.setFlash({title:"Tu sesión expiró",text:"Este dispositivo tiene los datos de tu perfil pero pasó mucho tiempo desde tu última actividad así que por motivos de seguridad te solicitamos que ingreses nuevamente."})
    router.push("/ingresar")
  },
  refreshToken : function(event) {
    $.server({
      url: '/v1/token',
      success: function(res) {
        if(res) {
          localStorage.setItem("token", JSON.stringify(res.data));
          setTimeout(function() {
            $('body').trigger(event||'token_updated');
          }, 200);
        }
      },
      error: function(xhr) {
        switch(xhr.status) {
          case 401:
            var res = $.parseJSON(xhr.responseText),
              _endpoints = $.parseJSON(res.message);
            if(_endpoints) {
              endpoints = _endpoints;
            }
        }
      }
    });
  },  
  cerrarMercedesBenz : function(){
    localStorage.removeItem("token");
    helper.setFlash({title:"¡Hasta luego!",text:'Tu sesión se cerró correctamente. Saliste de tu MercedesBenz.'});
    setTimeout(function(){
      if(location.pathname === '/'){
        location.href = '/',true;
      } else {
        router.push('/');  
      }      
    },200);
  },
  setFlash : function( flash ){
    localStorage.setItem("flash", JSON.stringify(flash));
  },
  getFlash : function(){
    var flash = $.parseJSON(localStorage.getItem("flash"));
    if(flash){
      $('.section > .notification strong').html(flash.title);
      $('.section > .notification span').html(flash.text);
      $('.section > .notification').removeClass('is-hidden').hide().fadeIn(300);
      localStorage.setItem("flash","");
      localStorage.removeItem("flash");
    }
  },
  champ : function(a) {
    var b = localStorage.getItem(a?a:'champ');
    return b ? $.parseJSON(b) : {};
  },  
  validateEmail : function ( email ) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  },
  emailOrPhone : function( field ){
    $('input[name="'+field+'"]').on('change keyup',function(){
      var val = $(this).val();
      $(this).next().find('svg').addClass('is-hidden');
      if(val.indexOf('@') > -1){
        $(this).next().find('.fa-envelope').removeClass('is-hidden');
      } else {
        $(this).next().find('.fa-phone').removeClass('is-hidden');
      } 
    });
  },
  initAutocomplete : function ( field, form ){
    var input = document.getElementById(field);
    var options = {
      componentRestrictions: {country: "ar"}
    };

    var autocomplete = new google.maps.places.Autocomplete(input,options);

    autocomplete.addListener('place_changed', function() {
      var place = autocomplete.getPlace();
      if (!place.geometry) {
        // User entered the name of a Place that was not suggested and
        // pressed the Enter key, or the Place Details request failed.
        console.log("No details available for input: '" + place.name + "'");
        return;
      }
      if (place.geometry.viewport) {
        var coords = place.geometry.location.toJSON();
        var address = place.address_components;
        var inject = {
          lat: coords.lat,
          lng: coords.lng,
          locality: address[1].long_name,
          administrative_area_level_1: address[3].long_name,
          administrative_area_level_2: address[2].long_name,
          country: address[4].long_name,
          vicinity: place.vicinity,
          map_icon: place.icon,
          map_url: place.url,
          formatted_address: place.formatted_address,
          utc: place.utc_offset
        };

        for(var i in inject){
          $('input[name="'+i+'"]').val(inject[i]).trigger('change');
        };
      }
    });
  },
  setValue : function(form,field,value){
    var champ = helper.champ();
    champ[form][field] = value;
    localStorage.setItem("champ", JSON.stringify(champ));
  },
  clear : function(form){
    var champ = helper.champ()
    delete champ[form]
    localStorage.setItem("champ", JSON.stringify(champ))
  },
  send : function( form, atts, cb ){
    if(atts===undefined) atts = {}
    var champ = helper.champ();
    var atts = JSON.parse(JSON.stringify(atts))||{}
    var prefix = $('.'+form).attr('ajax-prefix')?"/"+$('.'+form).attr('ajax-prefix')+"/":"/app/"
    var parent = $("."+form+":visible");
    var button = parent.find('.submitable');

    button.attr('disabled',true).addClass('disabled is-loading')

    return $.post( helper.getAttributes($('html')).endpoint + prefix + form, JSON.stringify(champ[form]), function(res){

      button.removeClass('is-loading')

      if(res.status === 'success'){
        if(res.id){
          champ[form].id = res.id;
        }

        if(atts.dump){
          console.log("dumping " + form);
          delete champ[form];
        }

        if(atts.redirect){
          router.push(atts.redirect);
        }

        localStorage.setItem("champ", JSON.stringify(champ));
      } else {
        if(typeof cb != 'function'){
          swal({
            type:'error',
            title:'Algo no resultó como se esperaba',
            text:res.message||'Hubo un error al consultar la api',
            html:true
          })
          $('.' + form + ' .button.rounded-button-grey').removeClass('disabled is-loading')
        }
      }
      if(typeof cb === 'function'){
        return cb.call(this,res)
      }        
    })
  },
  fill : function( field, form ){
    var champ = this.champ();
    var val = field.val();
    if(champ[form] === undefined) champ[form] = {};
    if(field.is(':checkbox')){
      val = val == 'on' ? 1:0;
    }
    if(val){
      champ[form][field.attr('name')] = val;
    }
    localStorage.setItem("champ", JSON.stringify(champ));
  },
  update : function( form ){
    $('.' + form).find('input, select, textarea').trigger("change")
  },
  capture : function( form ){

    helper.clear(form)

    var champ = this.champ();
    var parent = $("."+form+":visible");
    var button = parent.find('.submitable');
    var elements = parent.find('input, select, textarea');
    var that = this

    button.on('click',function(e){
      e.preventDefault();
      if(!$(this).hasClass('disabled')){
        router.push($(this).attr('to'))
      }
    })

    elements.on('change keyup click',function(){
      var complete = true
      helper.fill($(this),form)
      elements.each(function(){
        if(!$(this).attr('optional')){
          if(!$(this).val() || $(this).val() === ''){
            complete = false;
          }
          if($(this).attr('type')==='email' && !helper.validateEmail($(this).val())) {
            complete = false;
          }
        }
      })

      if(complete) {
        button.attr('disabled',false).removeClass('disabled');
      }
    });

    helper.update(form)
    elements.first().trigger('change');
    elements.first().focus()
  }
}

$(document).keypress(function(e) {
  if(e.which == 13) {
    var button = $('.section').find('.submitable');
    if(!button.hasClass('disabled')){
      button.click()
    } else {
      console.log("Rellena los datos que faltan para continuar")
    }
  }
})

$(document).on('click','.modal-button',function(e){
  $('html').addClass('is-clipped');
  $('.modal').removeClass('is-active');
  $('#'+$(this).data('target')).addClass('is-active');
  var capture = $(this).data('capture')
  if(capture) {
    setTimeout(function(){
      helper.capture(capture)  
    },100)
  }
})

$(document).on('click','.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button',function(e){
  $('html').removeClass('is-clipped');
  $('.modal').each(function () {
    $(this).removeClass('is-active');
  });
})

$(document).on('click','.rmver',function(e){
  e.preventDefault()
  var $t = $(this).parents($(this).attr('target'))
  if($t.length){
    $t.fadeOut('fast',function(){
      $(this).remove()
    })
  }
  return false
})

$(document).on('click','.tpler',function(e){
  e.preventDefault()
  var $t = $($(this).attr('target'))
  if($(this).attr('template')){
    $t.append($.templates($(this).attr('template')).render({target:$(this).attr('target'),removable:true}))
  }
  return false
})

$(document).on('click','.switcher',function(e){
  e.preventDefault()
  var $t = $($(this).attr('target'))
  var $t2 = $($(this).attr('target2'))
  if($t.html()==='' && $(this).attr('template')){
    $t.html($.templates($(this).attr('template')).render({target:$(this).attr('target')}))
  }
  $t.toggle()
  $t2.toggle()
  return false
})

$(document).on('focus change keyup','.sugest',function(e){
  e.preventDefault()
  var $v = $(this).val()
  var $t = $(this).attr('template')
  var $ep = $(this).attr('endpoint')
  var that = this
  if($v.length < 3){
    $(this).next().fadeOut('fast',function(){
      $(this).remove()
    })
  } else {
    $.server({
      url: $ep,
      data: {value: $v},
      success: function(res){
        if($(that).next().hasClass('sugest')){
          $(that).next().remove()
        }
        if($t){
          $(that).after($.templates($t).render({data:res.data,count:res.data.length},helper.filters))
        }
      },
      error : function(){
        //helper.sessionExpired()
      }
    })
  }
  return false
})

$(document).on('unfocus blur','.sugest',function(e){
  e.preventDefault()
  if($(this).next().hasClass('sugest')){
    $(this).next().fadeOut('fast',function(){
      $(this).remove()
    })  
  }
  return false  
})

/*$(document).on('change keyup','.slick-search input[type="text"]',function(e){


  clearTimeout($.data(this, 'scrollTimer'))
  $.data(this, 'scrollTimer', setTimeout(function() {


    var that = this
    var value = $(this).val().trim()
    console.log(value)

    $('.slick').slick('slickUnfilter')
    $('.slick').slick('slickFilter', function(a,b,c){

      console.log($(b).find('.hero__heading h1').text())


      if($('.slick').find('.hero__heading h1').text().indexOf(value) > -1 || 
        $('.slick').find('.hero__heading h4').text().indexOf(value) > -1){
        
        //console.log(a)
        //console.log(b)
        //console.log(c)
        console.log("true")
        return true
      }
       console.log("false")
      return false
    })
  }, 250))
})*/


$(document).on('click','.sugest-setv',function(e){
  e.preventDefault()
  var $v = $(this).text()
  var $p = $(this).parents('.sugest')
  $p.prev().val($v)
  $p.fadeOut('fast',function(){
    $(this).remove()
  })    
  return false   
})

$(document).on('click','.notification .delete',function(){
  $(this).parents('.notification').fadeOut();
})

$(document).on('click',"a:not([href*=':'])",function(event){

  const target = this
  // handle only links that do not reference external resources
  if (target && target.href) {

    // some sanity checks taken from vue-router:
    // https://github.com/vuejs/vue-router/blob/dev/src/components/link.js#L106
    const { altKey, ctrlKey, metaKey, shiftKey, button, defaultPrevented } = this
    // don't handle with control keys
    if (metaKey || altKey || ctrlKey || shiftKey) return
    // don't handle when preventDefault called
    if (defaultPrevented) return
    // don't handle right clicks
    if (button !== undefined && button !== 0) return
    // don't handle if `target="_blank"`

    if (target && target.getAttribute) {
      const linkTarget = target.getAttribute('target')
      if (/\b_blank\b/i.test(linkTarget)) return
    }
    // don't handle same page links/anchors
    const url = new URL(target.href)
    const to = url.pathname

    if (window.location.pathname !== to) {
      app.$router.push(to)
    }

    event.preventDefault()
  }  
})

document.addEventListener('DOMContentLoaded', function () {

  // Get all "navbar-burger" elements
  var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

  // Check if there are any navbar burgers
  if ($navbarBurgers.length > 0) {

    // Add a click event on each of them
    $navbarBurgers.forEach(function ($el) {
      $el.addEventListener('click', function () {

        // Get the target from the "data-target" attribute
        var target = $el.dataset.target;
        var $target = document.getElementById(target);

        // Toggle the class on both the "navbar-burger" and the "navbar-menu"
        $el.classList.toggle('is-active');
        $target.classList.toggle('is-active');

      });
    });
  }
  //$('.footer__partners').before($.templates('#backtotop').render())  
  $('.backtotop').click(function(){
    var body = $("html, body");
    body.stop().animate({scrollTop:0}, 500, 'swing', function() { 
       
    });
  });
});

$.extend({
  server: function(options) {
    options.method = "post";
    options.url = helper.getAttributes($('html')).endpoint + options.url;
    options.cache = false;
    options.async = true;
    options.then = options.then;
    var jwt = helper.getToken();
    if(jwt.length) {
      options.beforeSend = function(xhr) {
        xhr.setRequestHeader('Authorization', 'Bearer ' + jwt);
      };
    }
    var jqXHR = $.ajax(options).then(options.then);
    jqXHR.done(function() {});
  }
});

/*
var lastscrollpos = 0;
$(window).scroll(function(){
  clearTimeout($.data(this, 'scrollTimer'));
  $.data(this, 'scrollTimer', setTimeout(function() {
      var scrollpos = $(this).scrollTop();
      if(scrollpos > 500){
        if(!$('.backtotop').hasClass('slideIn')){
          $('.backtotop').removeClass('slideOut').addClass('slideIn')
        }
      } 
      if(lastscrollpos > scrollpos) {
        if($('.backtotop').hasClass('slideIn')){
          $('.backtotop').removeClass('slideIn').addClass('slideOut')
        }
      }
      lastscrollpos = scrollpos;
  }, 250));
});
*/

$.ajaxSetup({
  dataType : "json",
  contentType: "application/json; charset=utf-8"
})

$("body").keydown(function(e) {
  if(e.keyCode == 37) { // left
    $('.slick').slick('slickPrev');
  }
  else if(e.keyCode == 39) { // right
    $('.slick').slick('slickNext');
  }
});

$.views.settings.delimiters("[[", "]]")
moment.locale('es')