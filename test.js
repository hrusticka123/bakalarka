Vue.component('single',{
  props: ['title'],
  template: '<h1>{{title}}</h1>'
})

Vue.component('duble',{
  props: ['title'],
  template: '<h1>{{title}}</h1>'
})

new Vue({
  el: "#app"
})