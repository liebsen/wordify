var filters = {
  token : function(){
    return $.parseJSON(localStorage.getItem("token")) || {}
  },
  cache : function(){
    return $.parseJSON(localStorage.getItem("cache")) || {}
  },
  toJSON : function(json){
    return JSON.stringify(json)
  },
  formatDate : function(date){
    return moment(date,'X').format('DD MMM hh:mm');
  },
  isHumanTime : function(date){
    return moment(date,'X').fromNow()
  },
  isRecent : function(date){
    var a = moment()
    , b = moment(date,'X')
    , diff = a.diff(b,'seconds')
    return (diff < 3600)
  },
  isVeryRecent : function(date){
    var a = moment()
    , b = moment(date,'X')
    , diff = a.diff(b,'seconds')
    return (diff < 8)
  },
  getBeliefTextSize : function(val){
    var size = 10;
    if(val.length <= 5){
      size = 32
    } else if(val.length > 5 && val.length <= 10){
      size = 25
    } else if(val.length > 10 && val.length <= 15){
      size = 20
    } else if(val.length > 15 && val.length <= 25){
      size = 18
    } else if(val.length > 25 && val.length <= 25){
      size = 15
    } else if(val.length > 25 && val.length <= 60){
      size = 13
    } else if(val.length > 60 && val.length <= 100){
      size = 12
    } else if(val.length > 100 && val.length <= 140){
      size = 10
    } else if(val.length > 140){
      size = 8
    }
    return size   
  },
  endSession:function(redirect){
    if(redirect==undefined) redirect = '/session-ended'
    localStorage.removeItem("token")
    setTimeout(function(){
      if(location.pathname === redirect){
        location.href = redirect,true
      } else {
        app.$router.push(redirect)
      }      
    },200)
  },
  refreshToken : function() {
    $.server({
      url: '/api/v2/auth/token',
      success: function(res) {
        if(res) {
          localStorage.setItem("token", JSON.stringify(res.data))
        }
      },
      error: function(xhr) {
        filters.endSession('/session-expired')
      }
    })
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
  }
}

$(document).on('click','.modal-button',function(e){
  $('html').addClass('is-clipped');
  $('.modal').removeClass('is-active');
  $('#'+$(this).data('target')).addClass('is-active');
})

$(document).on('click','.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button',function(e){
  $('html').removeClass('is-clipped');
  $('.modal').each(function () {
    $(this).removeClass('is-active');
  });
})

$(document).on('click','.modal-background, .modal-close, .modal-card-head .delete, .modal-card-foot .button',function(e){
  $('html').removeClass('is-clipped');
  $('.modal').each(function () {
    $(this).removeClass('is-active');
  });
})

$(document).on('click','.notification .delete',function(){
  $(this).parents('.notification').fadeOut();
})

$(document).on('click',"a:not([href*=':'])",function(event){

  const target = this
  // handle only links that do not reference external resources
  if (target && target.href && !$(target).attr('bypass')) {
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
    options.cache = false;
    options.async = true;
    options.then = options.then;
    var token = $.parseJSON(localStorage.getItem("token")) || {}
    if(token.token) {
      options.beforeSend = function(xhr) {
        xhr.setRequestHeader('Authorization', 'Bearer ' + token.token);
      };
    }
    var jqXHR = $.ajax(options).then(options.then);
    jqXHR.done(function() {});
  }
});

$.ajaxSetup({
  dataType : "json",
  contentType: "application/json; charset=utf-8"
})

$.views.settings.delimiters("[[", "]]")
moment.locale('en')