
window.onload = function()
{
    var currentkey = window.localStorage.getItem('loginkey');
    if (currentkey != null)
    {
        var data = {
                    loginkey: currentkey
                };
        $.ajax({
                url: 'api/client/checklogged',
                type: 'POST',
                data: data
            }).then(function(data)
            {
                var result = JSON.parse(data);
                if (result.user)
                    window.location.href = 'emails.html';
            });
    }
}

var log = new Vue({
    el: '#loginForm',
    data: {
        username: '',
        password: ''
    },
    methods:
    {
        login: function()
        { 
            var data = {
                    username: log.username,
                    password: log.password
                };
            $.ajax({
                url: 'api/client/login',
                type: 'POST',
                data: {
                    username: log.username,
                    password: log.password
                },
            }).then(function(data)
            {  var result = JSON.parse(data);
                if (result.success)
                {
                    window.localStorage.setItem('loginkey', result.loginkey);
                    window.location.href = 'emails.html';
                }
                else
                    alert('Invalid Username/Password');
            });
        }
    }
  })