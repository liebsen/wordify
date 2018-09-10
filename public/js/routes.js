const ContactUs = {
  template: '#contact',
  methods : {
    submit : function({type, target}){
      if(!this.acceptTerms){
        this.messageType = 'is-danger'
        this.message = "You must accept our terms and conditions"
      } else {
        this.loading = true
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/contact', data, {emulateJSON:true}).then(function(res){
          this.data = res.data.data
          this.loading = false
          this.messageType = 'is-success'
          this.message = "Your message has been stored. Thank you for taking the time to drop us a line. We'll be replying soon."

          //helper.is_loaded()
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,
      hash : location.hash.replace('#','')
    }
  }
}

const SignUp = {
  template: '#signup',
  methods: {
    submit : function({type, target}){
      if(!this.acceptTerms){
        this.messageType = 'is-danger'
        this.message = "You must accept our terms and conditions"
      } else {
        this.loading = true
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/auth/signup', data, {emulateJSON:true}).then(function(res){
          this.data = res.data.data
          this.loading = false
          this.messageType = 'is-success'
          this.message = "An e-mail was sent. Please follow the link to activate your account"
          setTimeout(function(){
            that.loading = false
            that.message = ""
            that.messageType = ""
            app.$router.push('/sign-in')
          },15000)
          //helper.is_loaded()
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }    
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,
      data:{}
    }
  }
}

const SignIn = {
  template: '#signin',
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var that = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/auth/signin', data, {emulateJSON:true}).then(function(res){
          this.data = res.data.data
          if(res.data.status === 'success'){
            that.message = "You have successfully signed in. Redirecting..."
            that.messageType = "is-success"
            setTimeout(function(){
              localStorage.setItem("token", JSON.stringify(res.data.data))
              that.loading = false
              that.message = ""
              that.messageType = ""
              app.$router.push('/account')  
            },2000)
          } else {
            that.loading = false
            that.messageType = "is-danger"
            that.message = res.data.message 
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      data:{}
    }
  }
}

const RecoverPassword = {
  template: '#recoverpassword',
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var that = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/auth/recover-password', data, {emulateJSON:true}).then(function(res){
          if(res.data.status === 'success'){
            that.loading = false
            that.message = "A link was generated and sent. Please check your e-mail."
            that.messageType = "is-success"
          } else {
            that.loading = false
            that.messageType = "is-danger"
            that.message = res.data.message 
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      data:{}
    }
  }
}

const UpdatePassword = {
  template: '#updatepassword',
  mounted:function(){
    this.token = this.$route.query.token||""
  },
  methods: {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        this.message = ""
        this.messageType = ""
        var that = this
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/auth/update-password', data, {emulateJSON:true}).then(function(res){
          this.data = res.data.data
          if(res.data.status === 'success'){
            that.loading = false
            that.message = "You have successfully changed your password. Redirecting..."
            that.messageType = "is-success"
            setTimeout(function(){
              that.loading = false
              that.message = ""
              that.messageType = ""
              app.$router.push('/sign-in')
            },2000)
          } else {
            that.loading = false
            that.messageType = "is-danger"
            that.message = "Invalid access"
          }
        }, function(error){
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      filters:filters,
      loading:false,
      message:"",
      messageType:"",
      token:null,
      data:{}
    }
  }
}

const Movie = {
  template: '#movie',
  name:'movie',
  mounted: function(){
    var filter = this.$route.path.split('/').filter(function(n){ return n != "" }); 
    this.movie = filter[1]||""
    this.chapter = parseInt(filter[2])||0
    this.scene = parseInt(filter[3])||0
    this.map = this.getCache().map
    var proceed = !this.map || !Object.keys(this.map).length ? this.mapper() : this.stepper()
  },
  methods: {
    mapper:function(){
      this.$http.post('/api/v2/movies/'+this.movie, {}, {emulateJSON:true}).then(function(res){
        this.data = res.data.data
        var cache = this.getCache()
        var map = []
        if(!cache.refocus_id){
          this.$http.post('/api/v2/refocuses', {}, {emulateJSON:true}).then(function(res){
            cache.refocus_id = res.data.refocus_id
            localStorage.setItem("cache", JSON.stringify(cache));
          }, function(error){
            console.log(error.statusText)
          })           
        }
        // find chapters and draw items, root entry for scenes
        for(var i in this.data.map){
          var item = this.data.map[i]
          if(item.key == 'chapter'){
            map.push({
              id:item.id,
              value:item.value,
              scenes: [],
              actors:[],
              ins:[]
            })
          }
        }
        // find scenes, secondary entries for scenes
        for(var i in this.data.map){
          var item = this.data.map[i]
          for(var i in map){
            if(map[i].id===item.parent_id){ // belongin to each chapter
              if(item.key == 'scene'){
               map[i].scenes.push({
                  id:item.id,
                  value:item.value,
                  actors:[],
                  ins:[]       
                })
              }
              if(item.key == 'actor'){
                map[i].actors.push({
                  id:item.id,
                  parent_id:item.parent_id,
                  value:item.value
                })
              }
              if(item.key == 'ins'){
                map[i].ins.push({
                  id:item.id,
                  parent_id:item.parent_id,
                  value:item.value
                })                
              }
            } 
          }
        }

        for(var i in this.data.map){
          var item = this.data.map[i]        
          for(var i in map){
            var item2 = map[i]
            for(var j in item2.scenes){
              var item3 = item2.scenes[j]
              if(item3.id===item.parent_id){
                if(item.key==='actor'){
                  map[i].scenes[j].actors.push({
                    id:item.id,
                    parent_id:item.parent_id,
                    value:item.value
                  })
                }
                if(item.key==='ins'){
                  map[i].scenes[j].ins.push({
                    id:item.id,
                    parent_id:item.parent_id,
                    value:item.value
                  })
                }                
              }
            }        
          }
        }

        cache.map = map
        localStorage.setItem("cache", JSON.stringify(cache));
        this.stepper()
        //helper.is_loaded()
      }, function(error){
        console.log(error.statusText)
      }) 
    },
    stepper: function(){
      this.map = this.getCache().map
      var context = this.getContext()
      var that = this

      //console.log("chapter:"+this.chapter)
      //console.log("scene:"+this.scene)

      /* check array depth */

      this.nextstep = this.map[this.chapter] && this.map[this.chapter].scenes && this.map[this.chapter].scenes[this.scene] 
        ? '/movie/' + this.movie + '/' + this.chapter + '/' + (this.scene + 1) 
        : this.nextstep = '/movie/' + this.movie + '/' + (this.chapter + 1)

      /* populate scene */

      this.actors = []

      if(this.map[this.chapter] && this.map[this.chapter].actors){
        for(var i in this.map[this.chapter].actors){
          var actor = this.map[this.chapter].actors[i]
          actor.clss = actor.value

          if(!this.scene){
            actor.clss+= ' fresh'
          }

          if(actor.id){
            actor.clss+= ' saved'
          }

          this.actors.push(actor)
        }

        if(this.scene){
          for(var i in this.map[this.chapter].scenes){
            if(i < this.scene){
              for(var j in this.map[this.chapter].scenes[i].actors){
                var actor = this.map[this.chapter].scenes[i].actors[j]
                actor.clss = actor.value
                var c = parseInt(i)+1
                if(this.scene == c  ){
                  actor.clss+= ' fresh'
                }
                if(actor.id){
                  actor.clss+= ' saved'
                }
                this.actors.push(actor)
              }
            }
          }
        }
      }

      if(context){
        /* instructions */
        this.targets = []
        if(context.ins && context.ins.length){
          for(var i in context.ins){
            this.targets.push(context.ins[i].value)
          }
        }

        setTimeout(function(){

          setInterval(function(){
            that.stepIns()
          },that.insInterval)

          that.setIns()

          $(that.$refs.textarea).each(function(){
            var $input = $(this);
            var e = document.createEvent('HTMLEvents');
            e.initEvent('keyup', true, true);
            $input[0].dispatchEvent(e);
          })
        },800)
      }
    },
    setIns:function(){
      $('.ins span:nth-child(1)').fadeIn(300)
      $('.steps-ind').html($.templates('#step_ind').render(this.targets)).promise().done(function (){
        setTimeout(function(){
          $('.movie-canvas .steps-ind > span:nth-child(1) > span').addClass('active')
        },100)
      })
      this.insi++
    },
    stepIns:function(){
      var that = this
      $('.ins span:nth-child(' + that.insi +')').fadeOut(300,function(){
        if($('.ins span').length <= that.insi) {
          $('.steps-ind').html($.templates('#step_ind').render(that.targets))
          that.insi = 1
        } else {
          that.insi++
        }
        $('.ins span:nth-child(' + that.insi +')').fadeIn(300,function(){
          $('.movie-canvas .steps-ind > span:nth-child(' + that.insi +') > span').addClass('active')
        })
      })    
    },
    getCache:function(){
      return $.parseJSON(localStorage.getItem("cache")) || {}
    },
    getContext:function(){
      if(!this.map) console.log("Something wrong with getContext.")
      return this.map[this.chapter] && this.map[this.chapter].scenes[this.scene-1] 
        ? this.map[this.chapter].scenes[this.scene-1]
        : this.map[this.chapter]      
    },
    getItem:function(id){
      var context = this.getContext()
      for(var i in context.actors){
        if(context.actors[i].id===id){
          return context.actors[i]
        }
      }
      return false
    },
    capture:function({type, target}){
      var cache = this.getCache()
      var target_id = parseInt($(target).parent().attr('item-id'))
        var found = false

      for(var i in cache.map[this.chapter].actors){
        var item = cache.map[this.chapter].actors[i]
        if(item.id===target_id){
          cache.map[this.chapter].actors[i].text = target.value
          found = true
        }
      }

      if(!found && cache.map[this.chapter].scenes[this.scene]){
        for(var i in cache.map[this.chapter].scenes[this.scene].actors){
          if(item.id===target_id){
            cache.map[this.chapter].scenes[this.scene].actors[i].text = target.value
            found = true
          }
        }
      }

      localStorage.setItem("cache", JSON.stringify(cache))
    },
    updateBeliefText : function({type, target}){
      this.setBeliefTextSize(target)
    },
    setBeliefTextSize : function(belief){
      var size = filters.getBeliefTextSize($(belief).val().trim())
      $(belief).css({"font-size":size+'px'})
    },
    remove : function({type, target}){
      var actor = $(target).parents('.actor')
      $(actor).find('textarea').val().trim()

    },
    save:function({type, target}){
      var actor = $(target).parents('.actor')
      var cache = this.getCache()
      var text = $(actor).find('textarea').val().trim()
      var data = {
        //parent_id: $('.actors-canvas')
        refocus_id:cache.refocus_id,
        is_core: $(actor).hasClass('core')||$(actor).hasClass('core-opposite'),
        is_opposite:$(actor).hasClass('opposite'),
        is_selected:false,
        parent_id:0,
        text:text
      }

      this.$http.post('/api/v2/beliefs', data, {emulateJSON:true}).then(function(res){

        var cache = this.getCache()
        var res = res.data.data
        var target_id = parseInt($(actor).attr('item-id'))
        var data = this.map[this.chapter]
        var found = false

        for(var i in data.actors){
          if(data.actors[i].id===target_id){
            found = true;
            data.actors[i].id = res.id
            data.actors[i].text = res.text
          }
        }

        if(!found){
          var data = this.map[this.chapter].scenes[this.scene-1]
          if(data){
            for(var i in data.actors){
              if(data.actors[i].id === target_id){
                found = true;
                data.actors[i].id = res.id
                data.actors[i].text = res.text
              }
            }
          }
        }

        var forward = true;
        $('.actor').each(function(){
          if(!$(this).hasClass('saved')){
            forward = false;
          }
        })

        console.log("forward:" + forward)
        this.forward = forward
        cache.map = this.map 
        localStorage.setItem("cache", JSON.stringify(cache))
      }, function(error){
        console.log(error.statusText)
      })      
    }
  },
  data: function() {
    return{
      forward:false,
      data:{},
      movie:0,
      chapter:0,
      scene:0,
      map:{},
      ins:[],
      insi:0,
      targets:[],
      actors:[],
      insInterval:10000,
      nextstep:null,
    }
  }
}

const Account = {
  template: '#account',
  name: 'account',
  mounted: function(){
    this.loadingText = "Searching refocuses..."
    this.$http.post('/api/v2/account/refocuses', {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      if(!this.length) this.loadingText = "You haven't refocused yet."
      var that = this
      setTimeout(function(){
        $('.belief textarea').each(function(){
          var size = filters.getBeliefTextSize($(this).val().trim())
          $(this).css({"font-size":size+'px'})
        })
      },100)      
      //helper.is_loaded()
    }, function(error){
      console.log(error.loadingText)
    })  
  },
  methods: {
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    },
    mark: function(selected){
      this.selected = selected
    },
    archive: function(item){
      var that = this
      var guid = item.guid
      $.server({
        url: '/api/v2/account/refocuses/status/' + guid,
        data: JSON.stringify({enabled:0}),
        success: function(res) {
          that.loading = false
          that.messageType = 'is-success'
          that.message = "Refocus successfully shared by e-mail."          
          $('.'+guid).fadeOut('fast', function(){
            $(this).remove()
            if(!$('.media').length){
              that.data = null
            }
          })
        },
        error: function(xhr) {            
          that.loading = false
          that.messageType = 'is-danger'
          that.message = "Refocus could not be shared."
        }
      })
    },
    share: function({type,target}){
      var data = {}
      var that = this 

      this.loading = true
      this.messageType = ''
      this.message = ""

      $.map( $(target).serializeArray(), function( i ) {
        data[i.name] = i.value
      })

      $.server({
        url: '/api/v2/account/share/' + $(target).attr('guid'),
        data: JSON.stringify(data),
        success: function(res) {
          that.loading = false
          that.messageType = 'is-success'
          that.message = "Refocus successfully shared by e-mail."          
        },
        error: function(xhr) {            
          that.loading = false
          that.messageType = 'is-danger'
          that.message = "Refocus could not be shared."
        }
      })
    },
    download:function(guid){
      var token = this.token()
      $.ajax({
        type:'post',
        url: '/api/v2/account/download/' + guid,
        beforeSend: function (xhr) { 
          xhr.setRequestHeader('Authorization', 'Bearer ' + token.token); 
        },      
        xhrFields: {
          responseType: 'blob'
        },            
        dataType: 'binary',
        success: function(res) {
          //return false
          var blob = new Blob([res], { type: 'application/pdf' });
          var link = document.createElement('a')
          link.href = window.URL.createObjectURL(blob)
          link.download = guid + ".pdf"
          document.body.appendChild(link);
          link.click()
        },
        error: function(xhr) {
          console.log("error whilst downloading refocus.")
        }
      })
    }
  },
  data: function() {
    return{
      filters : filters,
      loading:false,
      messageType:null,
      loadingText:"",
      message:null,
      selected:{},
      data:{}
    }
  }
}

const Archive = {
  template: '#archive',
  name: 'archive',
  mounted: function(){
    this.loadingText = "Searching refocuses..."
    this.$http.post('/api/v2/account/archive', {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      if(!this.length) this.loadingText = "You haven't any discarted refocus yet."
      var that = this
      setTimeout(function(){
        $('.belief textarea').each(function(){
          var size = filters.getBeliefTextSize($(this).val().trim())
          $(this).css({"font-size":size+'px'})
        })
      },100)      
      //helper.is_loaded()
    }, function(error){
      console.log(error.loadingText)
    })  
  },
  methods: {
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    },
    mark: function(selected){
      this.selected = selected
    },    
    restore: function(item){
      var that = this
      var guid = item.guid
      $.server({
        url: '/api/v2/account/refocuses/status/' + guid,
        data: JSON.stringify({enabled:1}),
        success: function(res) {
          that.loading = false
          that.messageType = 'is-success'
          that.message = "Refocus successfully restored." 
          $('.'+guid).fadeOut('fast', function(){
            $(this).remove()
            if(!$('.media').length){
              that.data = null
            }
          })

        },
        error: function(xhr) {            
          that.loading = false
          that.messageType = 'is-danger'
          that.message = "Refocus could not be restored."
        }
      })
    },
    remove: function({type,target}){
      var data = {}
      var that = this 
      var guid = $(target).attr('guid')

      this.loading = true
      this.messageType = ''
      this.message = ""

      $.server({
        url: '/api/v2/account/refocuses/status/' + guid,
        data: JSON.stringify({deleted:1}),
        success: function(res) {
          that.loading = false
          that.messageType = 'is-success'
          that.message = "Refocus successfully removed."          

          $('html').removeClass('is-clipped');
          $('.modal').each(function () {
            $(this).removeClass('is-active');
          });

          $('.'+guid).fadeOut('fast', function(){
            $(this).remove()
            if(!$('.media').length){
              that.data = null
            }
          })
        },
        error: function(xhr) {            
          that.loading = false
          that.messageType = 'is-danger'
          that.message = "Refocus could not be removed."
        }
      })
    }, 
  },
  data: function() {
    return{
      filters : filters,
      loading:false,
      messageType:null,
      loadingText:"",
      message:null,
      selected:{},
      data:{}
    }
  }
}

const EditAccount = {
  template: '#editaccount',
  methods : {
    onFileChange(e) {
      var files = e.target.files || e.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0])
      this.uploadImage(files[0])
    },
    createImage(file,code) {
      var reader = new FileReader();
      var code = this.code
      reader.onload = (e) => {
        var $id = $('#img'+code);
        $id.css({
          'background-image': 'url(' + e.target.result + ')',
          'background-size': 'cover'
        });
        var exif = EXIF.readFromBinaryFile(new base64ToArrayBuffer(e.target.result));
        //fixExifOrientation(exif.Orientation, $id);
      };
      reader.readAsDataURL(file);
    },
    removeImage: function (e) {
      this.image = '';
    }, 
    clickImage : function(code){
      this.upload = true
      this.code = code
      $("#uploads").click()
      return false
    },
    uploadImage : function(file){
      this.upload = true
      var code = this.code
      var formData = new FormData();
      formData.append('uploads[]', file);
      var token = $.parseJSON(localStorage.getItem("token")) || {}
      var that = this

      this.loading = true
      this.message = "Uploading image..."
      this.messageType = "is-info"
      //loading
      //$('.profile'+type+'--link').text("Subiendo...");
      $.ajax({
        type:'post',
        url: '/api/v2/account/profile-picture',
        data:formData,
        beforeSend: function (xhr) { 
          xhr.setRequestHeader('Authorization', 'Bearer ' + token.token); 
        },          
        xhr: function() {
          var myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload){
            myXhr.upload.addEventListener('progress',function(e){
              if(e.lengthComputable){
                var max = e.total;
                var current = e.loaded;
                var percentage = (current * 100)/max;

                console.log("subiendo : " + parseInt(percentage))

                if(percentage >= 100) {
                  that.uploading = false
                }
              }
            }, false);
          }
          return myXhr;
        },
        cache:false,
        contentType: false,
        processData: false,
        success:function(res){
          if(res.status==='error'){
            that.message = res.error.split("\n")
            that.messageType = "is-danger"            
            console.log(res.proc[0].error)
          } else{
            var token = $.parseJSON(localStorage.getItem("token")) || {}
            token.picture = res.url
            localStorage.setItem("token", JSON.stringify(token))
            that.loading = false
            that.message = "Image has been correctly uploaded."
            that.messageType = "is-success"
          }
        },
        error: function(data){
          that.loading = false
          that.message = "Image has not been uploaded."
          that.messageType = "is-success"
          console.log("Hubo un error al subir el archivo");
        }
      })
    },    
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/account/update', data, {emulateJSON:true}).then(function(res){
          this.data = res.data.data
          var token = $.parseJSON(localStorage.getItem("token")) || {}

          token.first_name = this.data.first_name
          token.last_name = this.data.last_name
          token.email = this.data.email
          localStorage.setItem("token", JSON.stringify(token))

          this.loading = false
          this.messageType = 'is-success'
          this.message = "Account updated"

          //helper.is_loaded()
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,      
      hash : location.hash.replace('#','')
    }
  }
}

const ChangePassword = {
  template: '#changepassword',
  methods : {
    submit : function({type, target}){
      if(!this.loading){
        this.loading = true
        var data = {}

        $.map( $(target).serializeArray(), function( i ) {
          data[i.name] = i.value
        })

        this.$http.post('/api/v2/account/password', data, {emulateJSON:true}).then(function(res){
          this.loading = false
          this.messageType = res.data.messageType
          this.message = res.data.message
          //helper.is_loaded()
        }, function(error){
          this.loading = false
          this.messageType = 'is-danger'
          this.message = error.statusText
          console.log(error.statusText)
        })
      }
    },
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }
  },
  data: function() {
    return{
      loading:false,
      message:"",
      messageType:"",
      acceptTerms:false,      
      hash : location.hash.replace('#','')
    }
  }
}

const MyRefocus = {
  template: '#refocus',
  mounted: function(){
    this.$http.post('/api/v2'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      var that = this
      setTimeout(function(){
        $('.belief textarea').each(function(){
          var size = filters.getBeliefTextSize($(this).val().trim())
          $(this).css({"font-size":size+'px'})
        })
      },100)      
    }, function(error){
      console.log(error.statusText)
    })  
  },
  methods: {
    download:function(guid){
      var token = this.token()
      $.ajax({
        type:'post',
        url: '/api/v2/account/download/' + guid,
        beforeSend: function (xhr) { 
          xhr.setRequestHeader('Authorization', 'Bearer ' + token.token); 
        },      
        xhrFields: {
          responseType: 'blob'
        },            
        dataType: 'binary',
        success: function(res) {
          //return false
          var blob = new Blob([res], { type: 'application/pdf' });
          var link = document.createElement('a')
          link.href = window.URL.createObjectURL(blob)
          link.download = "refocus.pdf"
          document.body.appendChild(link);
          link.click()
        },
        error: function(xhr) {
          console.log("error whilst downloading refocus.")
        }
      })
    },    
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    }     
  },  
  data: function() {
    return{
      filters : filters,
      data:{}
    }
  }
}

const Posts = {
  template: '#posts',
  mounted: function() {
    //helper.is_loading()
    this.$http.post('/api/v2'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      //helper.is_loaded()
    }, function(error){
      console.log(error.statusText)
    })    
  },    
  data: function() {
    return{
      data:{}
    }
  }
}

const Post = {
  template: '#post',
  mounted : function(){
    //helper.is_loading()
    this.$http.post('/api/v2'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      document.title = this.data.title
      //helper.is_loaded()
    }, function(error){
      //helper.is_loaded()
      $('.section').html($.templates('#notfound').render());
      console.log(error.statusText)
    })    
  },
  data: function() {
    return{
      data: {data:{}}
    }
  }
}

const Section = {
  template: '#section',
  mounted: function() {
    this.loading = true 
    var that = this
    this.$http.post('/api/v2/sections'+location.pathname, {}, {emulateJSON:true}).then(function(res){
      this.data = res.data.data
      var sections = []
      var cache = $.parseJSON(localStorage.getItem("cache")) || {sections:{}}
      var ref = location.pathname.split('/').join('_')
      var now = new Date()

      if(!cache.sections){
        cache.sections = []
      }

      cache.sections[ref] = {
        title:this.data.title,
        heroclass:this.data.heroclass
      }

      cache.updated = now.getTime()
      
      localStorage.setItem("cache", JSON.stringify(cache))

      $('section.hero').removeClass('is-white is-black is-success is-dark is-primary is-link is-info is-success is-warning is-danger is-small')

      if(cache.sections[ref]){
        document.title = cache.sections[ref].title
        if(cache.sections[ref].heroclass){
          $('section.hero').addClass(cache.sections[ref].heroclass)
        }      
      }

      if(this.data.posts){
        setTimeout(function(){
          that.slick()
        },100)
      }

      that.loading = false
    }, function(error){
      //helper.is_loaded()
      $('.hero-body').html($.templates('#notfound').render())
      $('section.hero').addClass('is-danger')
      that.loading = false
      console.log(error.statusText)
    })  
  },  
  methods: {
    slick : function(){
      $('.slick').slick({
        slidesToShow: 1,
        dots: true
      }).removeClass('loading').addClass('fadeIn')
    }
  },
  data: function() {
    return{
      data:{},
      loading:false,
      url: this.$route.query.url
    }
  }
}

const Opener = {
  template: '#opener',
  mounted: function() {
    var that = this
    localStorage.setItem("token", this.$route.query.token);
    setTimeout(function(){
      location.href = that.url;
    },1000)
  },  
  data: function() {
    return{
      url: this.$route.query.url
    }
  }
}

const SessionEnded = {
  template: '#sessionended',
  mounted:function(){
    $('section.hero').addClass('is-success')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}

const SessionExpired = {
  template: '#sessionexpired',
  mounted:function(){
    $('section.hero').addClass('is-warning')
  },
  data: function() {
    return{
      filters:filters,
      hash : location.hash.replace('#','')
    }
  }
}

const NotFound = {
  template: '#notfound',
  mounted:function(){
    $('section.hero').addClass('is-danger')
  },
  data: function() {
    return{
    }
  }
}

const router = new VueRouter({
  mode: 'history',
  routes: [
    {path: '/posts', component: Posts,  meta : { title: 'Posts'}},
    {path: '/posts/:slug', component: Post,  meta : { title: 'Post'}},
    {path: '/sign-up', component: SignUp,  meta : { title: 'Sign up'}},
    {path: '/sign-in', component: SignIn,  meta : { title: 'Sign in'}},
    {path: '/recover-password', component: RecoverPassword,  meta : { title: 'Recover your password'}},
    {path: '/update-password', component: UpdatePassword,  meta : { title: 'Update your password'}},
    {path: '/contact-us', component: ContactUs, meta : { title: 'Contact us'}},    
    {path: '/movie/:movie/:chapter?/:scene?', component: Movie,  meta : { title: 'Refocus'}},
    {path: '/account', component: Account, meta : { title: 'My Account', requiresAuth: true}},
    {path: '/account/edit', component: EditAccount,  meta : { title: 'Edit my Account', requiresAuth: true}},
    {path: '/account/password', component: ChangePassword,  meta : { title: 'Change password', requiresAuth: true}},
    {path: '/account/archive', component: Archive,  meta : { title: 'Archive', requiresAuth: true}},
    {path: '/account/refocuses/:id', component: MyRefocus,  meta : { title: 'My Refocus', requiresAuth: true}},
    {path: '/opener', component: Opener, meta : { title: 'Redirecting...'}},
    {path: '/session-ended', component: SessionEnded, meta : { title: 'Session Ended'}},
    {path: '/session-expired', component: SessionExpired, meta : { title: 'Session Expired'}},
    {path: "*", component: Section, meta : { title: ''}}
  ]
});

router.beforeEach(function (to, from, next) { 
  document.title = to.meta.title;

  var ref = to.path.split('/').join('_')
  var cache = $.parseJSON(localStorage.getItem("cache")) || {}
  var token = $.parseJSON(localStorage.getItem("token")) || {}

  $('section.hero').removeClass('is-white is-black is-success is-dark is-primary is-link is-info is-success is-warning is-danger is-small')

  if(token.token){
    filters.refreshToken()
  }

  if(cache.sections && cache.sections[ref]){
    document.title = cache.sections[ref].title
    if(cache.sections[ref].heroclass){
      $('section.hero').addClass(cache.sections[ref].heroclass)
    }      
  }

  setTimeout(function() {
    window.scrollTo(0, 0)
  }, 100)

  if(to.meta.requiresAuth) {
    if(token.token) {
      next()
    } else {
      next('/')
    }    
  } else {
    next()
  }
})

router.afterEach(function (to, from, next) {
  setTimeout(function() {
    var ref = to.path.split('/').join('_')
    var token = $.parseJSON(localStorage.getItem("token")) || {}

    if(ref.substr(0,6)==='_movie'){
      if($('.tabs .navbar-item').is(':visible')){
        $('.tabs .navbar-item').fadeOut()
      }
      if($('.hero-status .compose').is(':visible')){
        $('.hero-status .compose').fadeOut()
      }
    } else {
      if($('.tabs .navbar-item').is(':hidden')){
        $('.tabs .navbar-item').fadeIn()
      }
    }

    if(ref.substr(0,8)==='_sign-in'){
      if($('.hero-status .signin').is(':visible')){
        $('.hero-status .signin').fadeOut()
      }
    } else {
      if($('.hero-status .signin').is(':hidden') && !token.token){
        $('.hero-status .signin').fadeIn()
      }
    }

    if(ref.substr(0,8)==='_account'){
      if($('.hero-status .account').is(':visible')){
        $('.hero-status .account').fadeOut()
      }
    }

    $('.navbar-end .tabs li').removeClass('is-active')
    $('.navbar-end .tabs ul').find('a[href="' + to.path + '"]').parent().addClass('is-active')
    $('.navbar-menu, .navbar-burger').removeClass('is-active')
  }, 10)
})

Vue.http.interceptors.push(function(request, next) {
  var token = $.parseJSON(localStorage.getItem("token")) || {}
  //request.headers.set('Access-Control-Allow-Credentials', true)
  request.headers.set('Authorization', 'Bearer '+ token.token)
  request.headers.set('Content-Type', 'application/json')
  request.headers.set('Accept', 'application/json')
  next()
})

const app = new Vue({ router: router,
  created: function () {
    var cache = $.parseJSON(localStorage.getItem("cache")) || {}
    if(cache){
      var now = new Date()
      var diff = (now.getTime() - cache.updated) / 1000 / 60
      var dayago = 60 * 24 * 1
      var hourago = 60 * 1
      var minago = 1

      if(diff > hourago){
        console.log("clearing old cache")
        localStorage.removeItem("cache")
      } else {
        console.log("reusing old cache")
      }
    }

    $('.hidden-loading').removeClass('hidden-loading')

    this.$http.post('/api/v2/navitems', {}, {emulateJSON:true}).then(function(res){
      $('.navbar-end .tabs ul').prepend($.templates('#navbaritem').render(res.data))
      $('.navbar-end .tabs ul').find('a[href="' + location.pathname + '"]').parent().addClass('is-active')
    }, function(error){
      console.log("Error while retrieving navitems.")
    }) 
  },
  methods : {
    token: function(){
      return $.parseJSON(localStorage.getItem("token")) || {}
    },
    cache: function(){
      return $.parseJSON(localStorage.getItem("cache")) || {}
    }
  }
}).$mount('#app')