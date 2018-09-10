// The following code is based off a toggle menu by @Bradcomp
// source: https://gist.github.com/Bradcomp/a9ef2ef322a8e8017443b626208999c1
(function() {
    var burger = document.querySelector('.burger');
    var menu = document.querySelector('#'+burger.dataset.target);
    var link = document.querySelector('a:not([href*=":"])');
    //var quickviews = bulmaQuickview.attach();
    link.addEventListener('click', function() {
	    const url = new URL(this.href)
	    const to = url.pathname

	    if (window.location.pathname !== to) {
	      app.$router.push(to)
	    }

	    event.preventDefault()    	
    });
    burger.addEventListener('click', function() {
        burger.classList.toggle('is-active');
        menu.classList.toggle('is-active');
    });
})();
