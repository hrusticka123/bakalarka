var sign = new Vue(
{
    el: '#signupForm',
    data: {
        username: '',
        password: ''
    },
    methods:
    {
        signup: function()
        { 
            var data = {
                    username: sign.username,
                    password: sign.password
                };
            $.ajax({
                url: 'api/client/signup',
                type: 'POST',
                data: data
            }).then(function(data)
            {
                var result = JSON.parse(data);
                if (result.success)
                {
                    alert("Registration successful");
                    window.location.href = "index.html";
                }
                else
                    alert("Registration not successful\n"+result.reason);
            });
        }
    }
});   