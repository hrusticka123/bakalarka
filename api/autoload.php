<?php

require 'client/checklogged.php';
require 'emails/getMail.php';
require 'client/login.php';
require 'client/logout.php';
require 'client/signup.php';
require 'elastic/search.php';
require 'emails/sendMail.php';
require 'emails/changetags.php';
require 'elastic/updatetags.php';
require 'emails/removeMail.php';
require 'elastic/removeMail.php';
require 'emails/downloadAtt.php';
require 'emails/upload.php';
require 'emails/mailparse/autoload.php';
require 'emails/mailsend/PHPMailer.php';
require 'emails/mailsend/Exception.php';
require 'emails/mailsend/SMTP.php';
require 'emails/decide.php';
require 'elastic/saveMail.php';
require 'emails/saveMail.php';
require 'emails/removeAtts.php';
require 'client/usertags.php';
require 'client/mailer.php';
require 'client/password.php';

?>