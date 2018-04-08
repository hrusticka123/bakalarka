<?php

function sendmail($info)
{
    $config = require "./config.php";
    $info = json_decode($info, true);
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);   

    try
    {
        $mail->setFrom($info['from'], $info['mailer']);
        $toaddress = '';
        if (count(explode('<',$info['to'])) > 1)
            $toaddress = str_replace(">","",explode('<',$info['to'])[1]);
        else
            $toaddress = $info['to'];

        $mail->addAddress($toaddress);   

        $mail->addReplyTo($info['from'], $info['mailer']);

        foreach($info['atts'] as $att)
            $mail->addAttachment($config['maildir']."/temp/".$att);         

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

        $mail->preSend();

        return decide($mail->getSentMIMEMessage());
        if (strpos($info['to'], '@hruska.blesmrt.cf') === false)
            $mail->postSend();

        foreach($info['atts'] as $att)
            unlink($config['maildir']."/temp/".$att); 

        return '{ "success" : true, "message" : "Message sent successfully" } ';
    }
    catch (Exception $e) 
    {
        return '{ "success" : false, "message" : "Message could not be sent. Mailer Error : '.$mail->ErrorInfo.'"}';
    }

}

?>
