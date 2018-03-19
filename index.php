<?php

session_start();

if(isset($_SESSION['username']))
{
    header('Location: emails.php');
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Intro</title>
<script src="vue.js"></script>
<script src="jquery.js"></script>
</head>
<body>


<div id="loginForm">
    <input v-model="username">
    <br>
    <input v-model="password">
    <br>
    <button v-on:click="login">LOGIN</button>
</div>
<script type="text/javascript">
    var log = new Vue(
    {
        el: '#loginForm',
        data: {
            username: '',
            password: ''
        },
        methods:
        {
            login: function()
            {
                $.ajax({
                    url: 'login.php',
                    type: 'POST',
                    data: 
                    {
                        username: log.username,
                        password: log.password
                    }
                }).then(function(data)
                {
                    result = JSON.parse(data);
                    if (result.success)
                        window.location.href = 'emails.php';
                    else
                        alert('Invalid Username/Password');
                });
            }
        }
    }
)
</script>

</body>
</html>