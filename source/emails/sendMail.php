<?php

//send email from info
function sendmail($info)
{
    $config = require "./config.php";
    $info = json_decode($info, true);
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);   

    try
    {
        //set the info for PHPMailer
        $mail->setFrom($info['from'], $info['mailer']);
        $toaddress = '';
        if (count(explode('<',$info['to'])) > 1)
            $toaddress = str_replace(">","",explode('<',$info['to'])[1]);
        else
            $toaddress = $info['to'];

        $mail->addAddress($toaddress);   

        $mail->addReplyTo($info['from']);

        foreach($info['atts'] as $att)
            $mail->addAttachment($config['maildir']."/".$info['from']."/".$info['atthash']."/".$att);         

        $mail->isHTML(true);                               
        $mail->Subject = $info['subject'];
        $mail->Body    = $info['text'];
        $mail->AltBody = strip_tags($info['text']);

        if ($info['inreplyto'] != false)
        {
            $info['references'][] = $info['inreplyto'];
            $mail->addCustomHeader('References', implode(" ",$info['references']));
            $mail->addCustomHeader('In-Reply-To', $info['inreplyto']);
        }

        //prepare the message
        $mail->preSend();

        //get the message as file, to save to as sent
        decide($mail->getSentMIMEMessage());
        if (strpos($info['to'], $config['domain']) === false)
        //actually send the message
            $mail->postSend();

        return '{ "success" : true, "message" : "Message sent successfully" } ';
    }
    catch (Exception $e) 
    {
        //handles all sent errors
        return '{ "success" : false, "message" : "Message could not be sent. Mailer Error : '.$mail->ErrorInfo.'"}';
    }

}

?>
