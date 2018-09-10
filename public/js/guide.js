const Home = {
  template: `
    <div class="container">
      <h1>{{msg}}</h1> 
    </div>
  `,
  data(){
    return{
      msg: 'Bienvenido!'
    };
  },
}


const Admin = {
  template: `
     <div class="container">
      <h1>{{msg}}</h1> 
  </div>
  `,
  data(){
    return{
      msg: 'This is Admin page'
    };
  },
}


const Users = {
  template: `
     <div class="container">
      <h1>{{msg}}</h1> 
  </div>
  `,
  data(){
    return{
      msg: 'This is Users page'
    };
  },
}



const router = new VueRouter({
  mode: 'history',
  routes: [
    {path: '/', component: Home},
    {path: '/users', component: Users},
    {path: '/admin', component: Admin}
  ]
})

const app = new Vue({ router }).$mount('#app')
/*
const NotFound = { template: '<p>Page not found</p>' }
const Home = { template: '<p>home page</p>' }
const About = { template: '<p>about page</p>' }

const routes = {
  '/': Home,
  '/guide': About
}

new Vue({
  el: '#app',
  data: {
    currentRoute: window.location.pathname
  },
  computed: {
    ViewComponent () {
      return routes[this.currentRoute] || NotFound
    }
  },
  render (h) { return h(this.ViewComponent) }
})


var app = new Vue({
  el: '#songs', // specify where the magic happens
  created: function() { // equals onReady
    this.getLastPlayed(); // this -> methods
  },
  data: {
    songs: [], // initialise empty data
    searchText: ""
  },
  computed: { // computed values change toghether with the ones they use
    filteredSongs: function() {
      var self = this // clone "this" as it will change in the next function()
      return self.songs.filter(function(song) { // filter songs on a given function
        return song.artist.toLowerCase().indexOf(self.searchText.toLowerCase()) !== -1 //use toLower to make it incasesensitive
      })
    }
  },
  methods: {
    getLastPlayed: function() {
      this.$http.get('https://itframe.innovatete.ch/nowplaying/hit1fm') // does a HTTP GET request
        .then(function(response) {
          this.songs = response.body // pushes the JSON parsed body to the data
        })
    },
    deleteSong: function(index) {
      this.songs.splice(index, 1);
    }
  }
})*/


import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

const Home = {
  template: `
    <div class="home">
      <h2>Home</h2>
      <p>hello</p>
    </div>
  `
}

const Parent = {
  data () {
    return {
      transitionName: 'slide-left'
    }
  },
  beforeRouteUpdate (to, from, next) {
    const toDepth = to.path.split('/').length
    const fromDepth = from.path.split('/').length
    this.transitionName = toDepth < fromDepth ? 'slide-right' : 'slide-left'
    next()
  },
  template: `
    <div class="parent">
      <h2>Parent</h2>
      <transition :name="transitionName">
        <router-view class="child-view"></router-view>
      </transition>
    </div>
  `
}

const Default = { template: '<div class="default">default</div>' }
const Foo = { template: '<div class="foo">foo</div>' }
const Bar = { template: '<div class="bar">bar</div>' }

const router = new VueRouter({
  mode: 'history',
  base: __dirname,
  routes: [
    { path: '/', component: Home },
    { path: '/parent', component: Parent,
      children: [
        { path: '', component: Default },
        { path: 'foo', component: Foo },
        { path: 'bar', component: Bar }
      ]
    }
  ]
})

new Vue({
  router,
  template: `
    <div id="app">
      <h1>Transitions</h1>
      <ul>
        <li><router-link to="/">/</router-link></li>
        <li><router-link to="/parent">/parent</router-link></li>
        <li><router-link to="/parent/foo">/parent/foo</router-link></li>
        <li><router-link to="/parent/bar">/parent/bar</router-link></li>
      </ul>
      <transition name="fade" mode="out-in">
        <router-view class="view"></router-view>
      </transition>
    </div>
  `
}).$mount('#app')

