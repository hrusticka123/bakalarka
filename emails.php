<html>
<head>
<script src="vue.js"></script>
<script src="elasticsearch.js"></script>
<script src="elasticsearch.min.js"></script>
</head>
<body>

<?php

session_start();

if(!isset($_SESSION['username'])) {
    session_destroy();
    echo ("<script LANGUAGE='JavaScript'>
    window.alert('No one logged in');
    window.location.href='index.php';
    </script>");
}

include 'daemon.php';

$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';

$edb = new DBElastic($hostname, $_SESSION['username'], $_SESSION['password']);

$edb->SaveMail();
$subjects = $edb->GetHeaders(5);

?> 

<div id="search">
    <input v-model="input">
    <br>
    <button v-on:click="send">SEARCH</button>
    <br>
    {{ result }}
    <br>
</div>

<ul id="emailsPreview">
  <li v-for="value in emails">
    {{ value }}
  </li>
</ul>

<script type="text/javascript">

var client = new elasticsearch.Client({
    host: 'http://localhost/es/',
    log: 'trace'
});

var elastic = new Vue({
    el: '#search',
    data: {
        result: 'Total found',
        input: '',
        hits: []
    },
    methods:
    {
        send: function ()
        {
            client.search({
            index: 'emailsdb',
            type: '<?php echo $_SESSION['username'] ?>',
            q: elastic.input
            }).then(function (resp) {
                var hitscount = resp.hits.total;
                if (hitscount != 0)
                {
                    elastic.result = hitscount;
                    enumHeaders.emails = [];
                    resp.hits.hits.forEach(function(hit) 
                    {
                        enumHeaders.emails.push(hit._source.subject);
                    });
                }
                else
                {
                    elastic.result = 'Nothing found';
                    enumHeaders.emails = {};
                }
            }, function (err) {
                console.trace(err.message);
            });
        }
    }
})

var enumHeaders = new Vue({
    el: '#emailsPreview',
    data: {
        emails : <?php echo $subjects ?>
    }
})

</script>

<a href = "logout.php">Logout</a>
</body>
</html>